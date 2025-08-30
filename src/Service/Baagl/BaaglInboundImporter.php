<?php

namespace App\Service\Baagl;

use App\Service\Db;

final class BaaglInboundImporter
{
    public function __construct(private readonly Db $db) {}

    /**
     * Zbourá tabulku, znovu ji vytvoří (snake_case) a hromadně vloží data.
     * @param array<int,array<string,mixed>> $items
     */
    public function rebuildAndInsert(array $items): int
    {
        // DDL – mimo transakci (MySQL DDL = implicitní commit)
        $this->db->execute('DROP TABLE IF EXISTS baagl_inbound');

        $this->db->execute(<<<'SQL'
            CREATE TABLE baagl_inbound (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50),
                nazev TEXT,
                uom VARCHAR(10),
                stav INT,
                mena VARCHAR(10),
                nakup_bez_dph DECIMAL(10,2),
                nakup_dph DECIMAL(10,2),
                INDEX idx_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            SQL);

        // DML – v transakci
        return $this->db->transactional(function (Db $db): int {
            $inserted = 0;
            $sql = 'INSERT INTO baagl_inbound
                    (code, nazev, uom, stav, mena, nakup_bez_dph, nakup_dph)
                    VALUES (?, ?, ?, ?, ?, ?, ?)';

            foreach ($this->buffer as $params) {
                $inserted += $db->insert($sql, $params);
            }
            return $inserted;
        });
    }

    /** Jednoduchý builder: nasypeš položky, pak zavoláš rebuildAndInsertFromBuffer() */
    private array $buffer = [];

    /** @param array<string,mixed> $item */
    public function addItem(array $item): void
    {
        $qty   = (int)($item['quantity']        ?? $item['qty']             ?? 0);
        $wov   = (float)($item['withoutVatPrice'] ?? $item['priceWithoutVat'] ?? 0.0);
        $pv    = (float)($item['priceVat']      ?? 0.0);

        $this->buffer[] = [
            (string)($item['code']     ?? ''),
            (string)($item['name']     ?? ''),
            (string)($item['uom']      ?? ''),
            $qty,
            (string)($item['currency'] ?? ''),
            sprintf('%.2f', $wov),
            sprintf('%.2f', $pv),
        ];
    }

    /** Shortcut: přijme celé pole položek, naplní buffer a provede DROP/CREATE + INSERTy */
    public function rebuildAndInsertFromItems(array $items): int
    {
        $this->buffer = [];
        foreach ($items as $i) {
            $this->addItem($i);
        }
        return $this->rebuildAndInsert($this->buffer);
    }
}
