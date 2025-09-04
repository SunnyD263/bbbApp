<?php
namespace App\Service\Baagl;

use SimpleXMLElement;

final class BaaglShoptetWriter
{
    public function __construct(private readonly string $defaultWarehouseName = 'Výchozí sklad') {}

    public function updateShopitem(SimpleXMLElement $shopitem, array|object $row): void
    {
        $price = $this->get($row, 'cena');
        if ($price !== null && $price !== '') {
            $this->setText($shopitem, 'PRICE_VAT', (string)$price);
            $this->setText($shopitem, 'STANDARD_PRICE', (string)$price);
        }

        $stock = (int)($this->get($row, 'stav', 0));
        $this->ensureWarehouseStock($shopitem, $company, $stock);
    }

    public function buildShopitem(SimpleXMLElement $root, array|object $row, string $company, ?callable $promoResolver = null): SimpleXMLElement
    {
        $item = $root->addChild('SHOPITEM');

        $nazev   = (string)$this->get($row, 'nazev', '');
        $catName = (string)$this->get($row, 'catName', '');
        $catId   = (string)$this->get($row, 'catId', '');
        $code    = (string)$this->get($row, 'code', '');

        $this->addText($item, 'NAME', $nazev);

        // SHORT_DESCRIPTION s volitelným iframe
        $short = $promoResolver ? $promoResolver($company, $nazev, $catName) : null;
        $this->addText($item, 'SHORT_DESCRIPTION', $short
            ? "<p style='text-align: center;'><iframe width='560' height='314' src={$short} allowfullscreen='allowfullscreen'></iframe></p>"
            : ''
        );

        $this->addText($item, 'DESCRIPTION', (string)$this->get($row, 'popis', ''));
        $this->addText($item, 'MANUFACTURER', $company);
        $this->addText($item, 'WARRANTY', '2 roky');
        $this->addText($item, 'SUPPLIER', $company);
        $item->addChild('ADULT', 0);
        $this->addText($item, 'ITEM_TYPE', 'product');

        // CATEGORIES
        $categories = $item->addChild('CATEGORIES');
        $cat = $categories->addChild('CATEGORY', $this->esc($catName));
        if ($catId !== '') { $cat->addAttribute('id', $catId); }
        $def = $categories->addChild('DEFAULT_CATEGORY', $this->esc($catName));
        if ($catId !== '') { $def->addAttribute('id', $catId); }

        // IMAGES obrazek1..20
        $images = $item->addChild('IMAGES');
        $first = (string)$this->get($row, 'obrazek1', '');
        if ($first !== '') { $images->addChild('IMAGE', $this->esc($first)); }
        for ($i = 2; $i <= 20; $i++) {
            $k = 'obrazek'.$i;
            $url = (string)$this->get($row, $k, '');
            if ($url !== '') { $images->addChild('IMAGE', $this->esc($url)); }
        }

        // INFORMATION_PARAMETERS
        $info = $item->addChild('INFORMATION_PARAMETERS');
        $this->maybeDim($info, 'Výška',   $this->get($row,'vyska'),   'cm');
        $this->maybeDim($info, 'Šířka',   $this->get($row,'sirka'),   'cm');
        $this->maybeDim($info, 'Hloubka', $this->get($row,'hloubka'), 'cm');
        $this->maybeDim($info, 'Nosnost', $this->get($row,'nosnost'), 'kg');

        $material = (string)$this->get($row, 'material', '');
        if ($material !== '') {
            $ip = $info->addChild('INFORMATION_PARAMETER');
            $this->addText($ip, 'NAME', 'Materiál');
            $this->addText($ip, 'VALUE', $material);
        }

        $this->addText($item, 'VISIBILITY', 'visible');
        $this->addText($item, 'SEO_TITLE', $nazev);
        $item->addChild('ALLOWS_IPLATBA', 1);
        $item->addChild('ALLOWS_PAY_ONLINE', 1);

        $this->addText($item, 'UNIT', (string)$this->get($row, 'uom', 'ks'));
        $this->addText($item, 'CODE', $code);
        $this->addText($item, 'EAN', (string)$this->get($row, 'ean', ''));

        // LOGISTIC
        $logistic = $item->addChild('LOGISTIC');
        $logistic->addChild('WEIGHT', (string)$this->get($row, 'hmotnost', ''));
        $logistic->addChild('HEIGHT', (string)$this->get($row, 'vyska', ''));
        $logistic->addChild('WIDTH',  (string)$this->get($row, 'sirka', ''));
        $logistic->addChild('DEPTH',  (string)$this->get($row, 'hloubka', ''));

        $atyp = $item->addChild('ATYPICAL_PRODUCT');
        $atyp->addChild('ATYPICAL_SHIPPING', 0);
        $atyp->addChild('ATYPICAL_BILLING', 0);

        // CENY
        $this->addText($item, 'CURRENCY', (string)$this->get($row, 'mena', 'CZK'));
        $item->addChild('VAT', (string)$this->get($row, 'dph', '21'));
        $price = (string)$this->get($row, 'cena', '0');
        $this->addText($item, 'PRICE_VAT', $price);
        $this->addText($item, 'PURCHASE_PRICE', (string)$this->get($row, 'nakupni_cena', '0'));
        $this->addText($item, 'STANDARD_PRICE', $price);

        // STOCK
        $stockNode = $item->addChild('STOCK');
        $warehouses = $stockNode->addChild('WAREHOUSES');

        $w = $warehouses->addChild('WAREHOUSE');
        $this->addText($w, 'NAME', $this->defaultWarehouseName);
        $w->addChild('VALUE', 0);
        $w->addChild('LOCATION');

        $w2 = $warehouses->addChild('WAREHOUSE');
        $this->addText($w2, 'NAME', $company);
        $w2->addChild('VALUE', (int)$this->get($row, 'stav', 0));
        $w2->addChild('LOCATION');

        $stockNode->addChild('MINIMAL_AMOUNT');
        $stockNode->addChild('MAXIMAL_AMOUNT');

        $this->addText($item, 'AVAILABILITY_OUT_OF_STOCK', 'Momentálně nedostupné');
        $this->addText($item, 'AVAILABILITY_IN_STOCK', 'Skladem ve skladu e-shopu');

        $item->addChild('VISIBLE', 1);
        $this->addText($item, 'PRODUCT_NUMBER', $code);
        $item->addChild('FIRMY_CZ', 1);
        $item->addChild('HEUREKA_HIDDEN', 0);
        $item->addChild('HEUREKA_CART_HIDDEN', 0);
        $item->addChild('ZBOZI_HIDDEN', 0);
        $item->addChild('ARUKERESO_HIDDEN', 0);
        $item->addChild('ARUKERESO_MARKETPLACE_HIDDEN', 0);
        $item->addChild('DECIMAL_COUNT', 0);
        $item->addChild('NEGATIVE_AMOUNT', 0);
        $item->addChild('PRICE_RATIO', 1);
        $item->addChild('MIN_PRICE_RATIO', 0);
        $this->addText($item, 'ACTION_PRICE', $price);
        $this->addText($item, 'ACTION_PRICE_FROM', '1999-01-01');
        $this->addText($item, 'ACTION_PRICE_UNTIL','1999-01-01');
        $item->addChild('APPLY_LOYALTY_DISCOUNT', 1);
        $item->addChild('APPLY_VOLUME_DISCOUNT', 0);
        $item->addChild('APPLY_QUANTITY_DISCOUNT', 1);
        $item->addChild('APPLY_DISCOUNT_COUPON', 0);

        return $item;
    }

    // ===== helpers =====

    private function setText(SimpleXMLElement $node, string $child, string $value): void
    {
        $value = str_replace(',', '.', $value);
        if (isset($node->{$child})) { $node->{$child} = $value; }
        else { $node->addChild($child, htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); }
    }

    private function addText(SimpleXMLElement $node, string $name, string $value): void
    {
        $node->addChild($name, htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function maybeDim(SimpleXMLElement $info, string $name, mixed $value, string $unit): void
    {
        $v = (string)($value ?? '');
        if ($v !== '' && $v !== '0.0' && $v !== '0.00') {
            $ip = $info->addChild('INFORMATION_PARAMETER');
            $this->addText($ip, 'NAME', $name);
            $this->addText($ip, 'VALUE', $v.' '.$unit);
        }
    }

    private function ensureWarehouseStock(SimpleXMLElement $shopitem, string $company, int $stock): void
    {
        if (!isset($shopitem->STOCK)) { $shopitem->addChild('STOCK'); }
        if (!isset($shopitem->STOCK->WAREHOUSES)) { $shopitem->STOCK->addChild('WAREHOUSES'); }

        $found = false;
        foreach ($shopitem->STOCK->WAREHOUSES->WAREHOUSE as $wh) {
            if ((string)$wh->NAME === $company) {
                $wh->VALUE = $stock;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $w = $shopitem->STOCK->WAREHOUSES->addChild('WAREHOUSE');
            $this->addText($w, 'NAME', $company);
            $w->addChild('VALUE', $stock);
            $w->addChild('LOCATION');
        }
    }

    private function get(array|object $row, string $key, mixed $default = null): mixed
    {
        if (is_array($row))  { return $row[$key] ?? $default; }
        if (is_object($row)) { return $row->$key ?? $default; }
        return $default;
    }
}
