<?php

namespace App\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Psr\Log\LoggerInterface;

final class Db
{
    public function __construct(
        private readonly Connection $conn,
        private readonly int $maxAttempts = 3,
        private readonly int $retryDelay = 5,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function select(string $sql, array $params = []): array
    {
        $attempt = 0;
        while (true) {
            $start = microtime(true);
            try {
                $rows = $this->conn->fetchAllAssociative($sql, $params);
                $this->logDebug($sql, $params, $start);
                return $rows;
            } catch (DBALException $e) {
                $this->logError($sql, $params, $start, $e);
                if (++$attempt < $this->maxAttempts) {
                    sleep($this->retryDelay);
                    continue;
                }
                throw $e;
            }
        }
    }

    public function insert(string $sql, array $params = []): int
    {
        return $this->executeAffecting($sql, $params);
    }

    public function update(string $sql, array $params = []): int
    {
        return $this->executeAffecting($sql, $params);
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->executeAffecting($sql, $params);
    }

    private function executeAffecting(string $sql, array $params = []): int
    {
        $attempt = 0;
        while (true) {
            $start = microtime(true);
            try {
                $n = $this->conn->executeStatement($sql, $params);
                $this->logDebug($sql, $params, $start);
                return $n;
            } catch (DBALException $e) {
                $this->logError($sql, $params, $start, $e);
                if (++$attempt < $this->maxAttempts) {
                    sleep($this->retryDelay);
                    continue;
                }
                throw $e;
            }
        }
    }

    /** Vložení jednoho řádku přes asociativní pole */
    public function insertRow(string $table, array $data): int
    {
        if ($data === []) return 0;

        $platform = $this->conn->getDatabasePlatform();

        $cols  = array_keys($data);
        $qCols = array_map($platform->quoteIdentifier(...), $cols);

        // placeholdery :p0, :p1… ; v poli parametrů klíče bez dvojtečky
        $ph = [];
        $params = [];
        foreach (array_values($cols) as $i => $col) {
            $ph[] = ':p'.$i;
            $params['p'.$i] = $data[$col] ?? null;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $platform->quoteIdentifier($table),
            implode(',', $qCols),
            implode(',', $ph)
        );

        return $this->execute($sql, $params);
    }

    /**
     * Rychlý hromadný INSERT (batch). Sloupce musí být ve všech řádcích stejné.
     * Vrací celkový počet vložených řádků.
     *
     * @param array<int, array<string,mixed>> $rows
     */
    public function insertMany(string $table, array $rows, int $chunkSize = 500): int
    {
        if ($rows === []) {
            return 0;
        }

        $platform = $this->conn->getDatabasePlatform();
        $qTable   = $platform->quoteIdentifier($table);

        // konzistence sloupců podle prvního řádku
        $cols  = array_keys($rows[0]);
        $qCols = array_map($platform->quoteIdentifier(...), $cols);

        // šablona: "(?, ?, ...)"
        $tpl = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';

        $total = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            // SQL: INSERT INTO table (c1,c2,...) VALUES (..),(..),(...)
            $valuesSql = implode(',', array_fill(0, count($chunk), $tpl));
            $sql = sprintf('INSERT INTO %s (%s) VALUES %s', $qTable, implode(',', $qCols), $valuesSql);

            // linearizace parametrů v pořadí sloupců
            $params = [];
            foreach ($chunk as $r) {
                foreach ($cols as $c) {
                    $params[] = $r[$c] ?? null;
                }
            }

            $start = microtime(true);
            try {
                $total += $this->conn->executeStatement($sql, $params);
                $this->logDebug($sql, $params, $start);
            } catch (\Throwable $e) {
                $this->logError($sql, $params, $start, $e);
                throw $e;
            }
        }

        return $total;
    }

    /** Vrátí jediný řádek (nebo null) */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);
        return $rows[0] ?? null;
    }

    /** Vrátí jednu hodnotu (první sloupec prvního řádku) */
    public function scalar(string $sql, array $params = []): mixed
    {
        $row = $this->selectOne($sql, $params);
        return $row ? array_values($row)[0] : null;
    }

    /** Bezpečná transakce – DBAL 4/3 + fallback */
    public function transactional(callable $fn): mixed
    {
        if (method_exists($this->conn, 'wrapInTransaction')) {
            // DBAL 4
            return $this->conn->wrapInTransaction(fn() => $fn($this));
        }
        if (method_exists($this->conn, 'transactional')) {
            // DBAL 3
            return $this->conn->transactional(fn() => $fn($this));
        }

        // fallback pro velmi staré verze
        $this->conn->beginTransaction();
        try {
            $res = $fn($this);
            $this->conn->commit();
            return $res;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Alias – když někde voláš $db->transaction(), přesměruje na transactional() */
    public function transaction(callable $fn): mixed
    {
        return $this->transactional($fn);
    }

    /** Portable TRUNCATE (MySQL/Maria: FK off → TRUNCATE; jinak DBAL SQL) */
    public function truncate(string $table): void
    {
        $platform = $this->conn->getDatabasePlatform();
        $qTable   = $platform->quoteIdentifier($table);

        // 1) Když tabulka neexistuje, prostě skonči bez chyby
        $sm = $this->conn->createSchemaManager();
        if (!$sm->tablesExist([$table])) {
            $this->logDebug("TRUNCATE skipped, table not exists: {$qTable}", [], microtime(true));
            return;
        }

        $start = microtime(true);
        try {
            if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
                $this->conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
                $this->conn->executeStatement("TRUNCATE TABLE {$qTable}");
                $this->conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            } else {
                $sql = $platform->getTruncateTableSQL($table);
                $this->conn->executeStatement($sql);
            }
            $this->logDebug("TRUNCATE {$qTable}", [], $start);
        } catch (\Throwable $e) {
            $this->logError("TRUNCATE {$qTable}", [], $start, $e);
            throw $e;
        }
    }

    // --- logging helpers ---

    private function logDebug(string $sql, array $params, float $start): void
    {
        if (!$this->logger) return;
        $this->logger->debug('SQL executed', [
            'sql' => $sql,
            'params' => $params,
            'time_ms' => (int) ((microtime(true) - $start) * 1000),
        ]);
    }

    private function logError(string $sql, array $params, float $start, \Throwable $e): void
    {
        if (!$this->logger) return;
        $this->logger->error('SQL failed', [
            'sql' => $sql,
            'params' => $params,
            'time_ms' => (int) ((microtime(true) - $start) * 1000),
            'exception' => $e,
        ]);
    }
}
