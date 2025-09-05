<?php
// src\Service\Baagl\BaaglShoptetMatcher.php
namespace App\Service\Baagl;

use SimpleXMLElement;

final class BaaglShoptetMatcher
{
    /**
     * @param SimpleXMLElement $xmlShoptet Root s <SHOPITEM>
     * @param iterable<array|object> $items  položky z normalizeru (musí mít 'code')
     * @return array{
     *   matched: array<string, array{item: array|object, shopitem: SimpleXMLElement}>,
     *   missing: array<int, array|object>,
     *   extra:   array<string, SimpleXMLElement>
     * }
     */
    public function match(SimpleXMLElement $xmlShoptet, iterable $items): array
    {
        // Index Shoptetu: CODE => SHOPITEM
        $byCode = [];
        foreach ($xmlShoptet->SHOPITEM as $shopitem) {
            $code = (string) $shopitem->CODE;
            if ($code !== '') {
                $byCode[$code] = $shopitem;
            }
        }

        $matched = [];
        $missing = [];

        foreach ($items as $row) {
            $code = (string) $this->get($row, 'registracni_cislo', '');
            if ($code === '' || !isset($byCode[$code])) {
                $missing[] = $row;
                continue;
            }
            $matched[$code] = ['item' => $row, 'shopitem' => $byCode[$code]];
            unset($byCode[$code]);
        }

        return [
            'matched' => $matched,
            'missing' => $missing,
            'extra'   => $byCode, 
        ];
    }

    private function get(array|object $row, string $key, mixed $default = null): mixed
    {
        if (is_array($row))  { return $row[$key] ?? $default; }
        if (is_object($row)) { return $row->$key ?? $default; }
        return $default;
    }
}
