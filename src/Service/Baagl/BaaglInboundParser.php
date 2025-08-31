<?php

namespace App\Service\Baagl;

class BaaglInboundParser
{
    public function parseHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query("//table//tr");

        $items = [];
        $sumQty = 0;
        $sumPrice = 0.0;

        foreach ($rows as $row) {
            /** @var \DOMElement $row */
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 6) {
                continue;
            }

            $rawText = trim($cells->item(0)->nodeValue);
            if (!preg_match('/^([^\s]+)\s+-\s+(.*)$/u', $rawText, $m)) {
                continue;
            }
            $code = mb_strtoupper((string)$m[1]);
            $name = trim($m[2]);

            // 2. sloupec – bez DPH „1 234,00 Kč“
            $withoutVatPriceText = trim($cells->item(1)->nodeValue);
            $withoutVatPrice = 0.0;
            $withoutVatCurrency = '';
            if (preg_match('/^([\d\s]+,\d{2})\s*([^\d\s]+)$/u', $withoutVatPriceText, $mm)) {
                $withoutVatPrice = (float) str_replace([',',' '], ['.',''], $mm[1]);
                $withoutVatCurrency = trim($mm[2]);
            }

            // cena s DPH (jednotková)
            $priceVatText = trim($cells->item(2)->nodeValue);
            $priceVat = 0.0;
            $priceVatCurrency = '';
            if (preg_match('/^([\d\s]+,\d{2})\s*([^\d\s]+)$/u', $priceVatText, $mm)) {
                $priceVat = (float) str_replace([',',' '], ['.',''], $mm[1]);
                $priceVatCurrency = trim($mm[2]);
            }

            // množství – např. „12 ks“
            $qtyText = trim($cells->item(3)->nodeValue);
            $qtyValue = 0;
            $qtyUom = '';
            if (preg_match('/^(-?\d+)\s*([^\d\s]+)?$/u', $qtyText, $mm)) {
                $qtyValue = (int) $mm[1];
                $qtyUom = isset($mm[2]) ? trim($mm[2]) : '';
            }

            $taxText = trim($cells->item(4)->nodeValue);
            $tax = 0;

            if (preg_match('/^([\d\s]+)\s*([^\d\s]+)$/u', $taxText, $match)) {
                $tax = (int) str_replace(' ', '', $match[1]);
            }

            // cena s DPH (součet)
            $priceVatSumText = trim($cells->item(5)->nodeValue);
            $priceVatSum = 0.0;
            $priceVatSumCurrency = '';
            if (preg_match('/^([\d\s]+,\d{2})\s*([^\d\s]+)$/u', $priceVatSumText, $mm)) {
                $priceVatSum = (float) str_replace([',',' '], ['.',''], $mm[1]);
                $priceVatSumCurrency = trim($mm[2]);
            }

            $items[] = [
                'code' => $code,
                'name' => $name,
                'uom' => $qtyUom,
                'qty' => $qtyValue,
                'currency' => $priceVatCurrency ?: $withoutVatCurrency ?: $priceVatSumCurrency,
                'tax' => $tax,
                'priceWithoutVat' => $withoutVatPrice,
                'priceVat' => $priceVat,
                'priceVatSum' => $priceVatSum,
            ];

            $sumQty += $qtyValue;
            $sumPrice += $priceVatSum;
        }

        return [
            'items' => $items,
            'sumQty' => $sumQty,
            'sumPrice' => $sumPrice,
        ];
    }
}
