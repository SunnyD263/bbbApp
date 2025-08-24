<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;

final class Db
{
    public function __construct(
        private readonly Connection $conn,
        private readonly int $maxAttempts = 3,
        private readonly int $retryDelay = 5,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function select(string $sql, array $params = []): array
    {
        $attempt = 0;
        while (true) {
            try {
                return $this->conn->fetchAllAssociative($sql, $params);
            } catch (DBALException $e) {
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
            try {
                return $this->conn->executeStatement($sql, $params);
            } catch (DBALException $e) {
                if (++$attempt < $this->maxAttempts) {
                    sleep($this->retryDelay);
                    continue;
                }
                throw $e;
            }
        }
    }

    // 1) Vložení jednoho řádku přes asociativní pole
    public function insertRow(string $table, array $data): int
    {
        $cols   = array_keys($data);
        $params = [];
        $values = [];

        foreach ($cols as $i => $col) {
            $ph = ':p'.$i;
            $params[$ph] = $data[$col];
            $values[] = $ph;
            $cols[$i] = $col; // jistota, že se nepokazí pořadí
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(',', $cols),
            implode(',', $values)
        );

        return $this->execute($sql, $params);
    }

    // 2) Vrátí jediný řádek (nebo null)
    public function selectOne(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);
        return $rows[0] ?? null;
    }

    // 3) Vrátí jednu skalární hodnotu (první sloupec prvního řádku)
    public function scalar(string $sql, array $params = []): mixed
    {
        $row = $this->selectOne($sql, $params);
        return $row ? array_values($row)[0] : null;
    }

    // 4) Bezpečná transakce
    public function transactional(callable $fn): mixed
    {
        $this->conn->beginTransaction();
        try {
            $result = $fn($this);
            $this->conn->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
