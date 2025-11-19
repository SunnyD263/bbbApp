<?php
// src\Service\Activa\ActivaShoptetMatcher.php
namespace App\Service\Activa;

use SimpleXMLElement;

final class ActivaShoptetMatcher
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
    // index Shoptet: CODE -> SHOPITEM (neodstraňuj z indexu)
    $byCode = [];
    foreach ($xmlShoptet->SHOPITEM as $shopitem) {
        $code = $this->canon((string)$shopitem->CODE);
        if ($code !== '') {
            $byCode[$code] = $shopitem;
        }
    }

    $matched    = []; // první výskyt
    $duplicates = []; // další výskyty stejného kódu v importu
    $missing    = [];

    foreach ($items as $row) {
        $code = $this->canon((string)$this->get($row, 'ITEM_ID', ''));
        if ($code === '' || !isset($byCode[$code])) {
            $missing[$code] = ['item' => $row];
            continue;
        }

        if (isset($matched[$code])) {
            // už jednou spárováno → tento řádek je duplicita importu
            $duplicates[$code][] = $row;
            continue;
        }

        $matched[$code] = ['item' => $row, 'shopitem' => $byCode[$code]];
        // POZN: Nevoláme unset($byCode[$code]); ať extra zůstane „skutečně navíc“
    }

    // položky ze Shoptetu, které nebyly vůbec v importu
    $extra = array_diff_key($byCode, $matched);

    return [
        'matched'    => $matched,
        'duplicates' => $duplicates,
        'missing'    => $missing,
    ];
}

    private function get(array|object $row, string $key, mixed $default = null): mixed
    {
        if (is_array($row))  { return $row[$key] ?? $default; }
        if (is_object($row)) { return $row->$key ?? $default; }
        return $default;
    }

    private function canon(?string $v): string
{
    if ($v === null) return '';
    $s = (string)$v;

    // oříznout whitespace vč. NBSP
    $s = trim(str_replace("\xC2\xA0", ' ', $s));

    // sjednotit lomítko – kdyby tam byl fraction slash (U+2044)
    $s = strtr($s, ["\xE2\x81\x84" => "/"]);

    // odstranit kontrolní a neviditelné znaky
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s);

    // sjednotit case (jen kdyby kódy obsahovaly písmena)
    $s = strtoupper($s);

    return $s;
}
}
