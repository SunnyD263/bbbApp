<?php

namespace App\Service\Baagl;

use App\Db\Db;

final class BaaglInboundImporter
{
    public function __construct(private readonly Db $db) {}

    /** TRUNCATE před načtením stránky */
    public function rebuild(): void
    {
        $this->db->truncate('baagl_inbound');
    }

    /** Vložení po uploadu */
    public function insertFromItems(array $items): int
    {
        if ($items === []) return 0;

        // namapuj parser výstup -> DB sloupce
        $rows = array_map(function(array $r) {
            return [
                'code'          => $r['code']            ?? null,
                'nazev'         => $r['name']            ?? null,
                'uom'           => $r['uom']             ?? null,
                'stav'          => isset($r['qty']) ? (int)$r['qty'] : null,
                'tax'           => isset($r['tax']) ? (int)$r['tax'] : null,
                'nakup_bez_dph' => isset($r['priceWithoutVat']) ? self::toDecimal($r['priceWithoutVat']) : null,
                'nakup_dph'     => isset($r['priceVat'])        ? self::toDecimal($r['priceVat'])        : null,
                'mena'          => $r['currency']        ?? null,
            ];
        }, $items);

        // transakce (bezpečné vkládání)
        return $this->db->transaction(fn() => $this->db->insertMany('baagl_inbound', $rows, 300));
    }

    private static function toDecimal(mixed $v): string
    {
        if (is_string($v)) $v = str_replace([' ', ','], ['', '.'], $v);
        return (string) (is_numeric($v) ? $v : 0);
    }
}