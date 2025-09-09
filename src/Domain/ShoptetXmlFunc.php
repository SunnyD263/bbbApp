<?php
// src/Domain/ShoptetXmlFunc.php
namespace App\Domain;

use DOMDocument;
use DOMElement;

final class ShoptetXmlFunc
{
    // ---------- LOW-LEVEL HELPERS ----------
    public static function addText(DOMDocument $doc, DOMElement $parent, string $name, ?string $value): void
    {
        $el = $doc->createElement($name);
        $el->appendChild($doc->createTextNode((string)($value ?? '')));
        $parent->appendChild($el);
    }

    public static function addCdata(DOMDocument $doc, DOMElement $parent, string $name, ?string $html): void
    {
        $el = $doc->createElement($name);
        $el->appendChild($doc->createCDATASection((string)($html ?? '')));
        $parent->appendChild($el);
    }

    public static function f(int|float|string|null $n): string
    {
        if ($n === null || $n === '') return '';
        return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
    }

    public static function date(?\DateTimeInterface $d): ?string
    {
        return $d?->format('Y-m-d');
    }

    // ---------- BLOCK HELPERS ----------
    /** @param array<int, array{id:int,name:string,default?:bool}> $categories */
    public static function appendCategories(DOMDocument $doc, DOMElement $item, array $categories): void
    {
        if (!$categories) return;

        $wrap = $doc->createElement('CATEGORIES');
        $defaultEl = null;

        foreach ($categories as $cat) {
            $c = $doc->createElement('CATEGORY', $cat['name']);
            $c->setAttribute('id', (string)$cat['id']);
            $wrap->appendChild($c);
            if (!empty($cat['default'])) {
                $defaultEl = $cat;
            }
        }

        if ($defaultEl) {
            $def = $doc->createElement('DEFAULT_CATEGORY', $defaultEl['name']);
            $def->setAttribute('id', (string)$defaultEl['id']);
            $wrap->appendChild($def);
        }

        $item->appendChild($wrap);
    }

    /** @param array<int, array{url:string,description?:string}> $images */
    public static function appendImages(DOMDocument $doc, DOMElement $item, array $images): void
    {
        if (!$images) return;

        $wrap = $doc->createElement('IMAGES');
        foreach ($images as $img) {
            $el = $doc->createElement('IMAGE', $img['url']);
            if (!empty($img['description'])) {
                $el->setAttribute('description', $img['description']);
            }
            $wrap->appendChild($el);
        }
        $item->appendChild($wrap);
    }

    /** @param array<int, array{name:string,value:string}> $params */
    public static function appendInfoParams(DOMDocument $doc, DOMElement $item, array $params): void
    {
        if (!$params) return;

        $wrap = $doc->createElement('INFORMATION_PARAMETERS');
        foreach ($params as $p) {
            $row = $doc->createElement('INFORMATION_PARAMETER');
            self::addText($doc, $row, 'NAME', $p['name'] ?? '');
            self::addText($doc, $row, 'VALUE', $p['value'] ?? '');
            $wrap->appendChild($row);
        }
        $item->appendChild($wrap);
    }

    /** @param array<int, array{code:string,active:int}> $flags */
    public static function appendFlags(DOMDocument $doc, DOMElement $item, array $flags): void
    {
        if (!$flags) return;

        $wrap = $doc->createElement('FLAGS');
        foreach ($flags as $f) {
            $flag = $doc->createElement('FLAG');
            self::addText($doc, $flag, 'CODE', $f['code'] ?? '');
            self::addText($doc, $flag, 'ACTIVE', isset($f['active']) ? (string)$f['active'] : '0');
            $wrap->appendChild($flag);
        }
        $item->appendChild($wrap);
    }

    public static function appendLogistic(DOMDocument $doc, DOMElement $item, array $logistic): void
    {
        $log = $doc->createElement('LOGISTIC');
        foreach ($logistic as $key => $w) {
            self::addText($doc, $log, $key, (string)$w);
        }
        $item->appendChild($log);

    }

    public static function appendStock(DOMDocument $doc, DOMElement $item, array $stock): void
    {
        $whs = $doc->createElement('WAREHOUSES');
        foreach ($stock as $w) {
            $wh = $doc->createElement('WAREHOUSE');
            self::addText($doc, $wh, 'NAME', $w['name'] ?? '');
            self::addText($doc, $wh, 'VALUE', isset($w['value']) ? (string)$w['value'] : '0');
            self::addText($doc, $wh, 'LOCATION', $w['location'] ?? '');
            $whs->appendChild($wh);
        }
        $item->appendChild($whs);

    }
}
