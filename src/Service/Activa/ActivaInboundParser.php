<?php
namespace App\Service\Activa;

use App\Utility\PdfTextExtractor;

final class ActivaInboundParser
{
    public function __construct(private readonly PdfTextExtractor $extractor) {}

    public function parseFromPath(string $pdfPath): array
    {
        $text = $this->extractor->extract($pdfPath);

        if (!preg_match('/Číslo\s+Popis.+?Cena\s+celkem\s*(?P<body>.+?)Celkem\s+CZK\s+bez\s+DPH/usi', $text, $mm)) {
            $mm['body'] = $text;
        }

        $items = [];
        $sumQty = 0.0;
        $sumPrice = 0.0;

        foreach (preg_split('/\R/u', $mm['body']) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line === '') continue;

            if (preg_match(
                '/^(?P<code>\d{4}\/\d{7})\s+(?P<name>.+?)\s+(?P<qty>\d+(?:[.,]\d+)?)\s+(?P<unit>[0-9.,]+)\s+(?P<nodpH>[0-9.,]+)\s+(?P<rate>\d{1,2})\s+(?P<vat>[0-9.,]+)\s+(?P<with>[0-9.,]+)$/u',
                $line, $m
            )) {
                $qty   = self::czToFloat($m['qty']);
                $unit  = self::czToFloat($m['unit']);
                $noVat = self::czToFloat($m['nodpH']);
                $vat   = self::czToFloat($m['vat']);
                $priceVatSum  = self::czToFloat($m['with']);

                $items[] = [
                    'code'           => $m['code'],
                    'name'           => $m['name'],
                    'qty'            => $qty,
                    'unitPrice'      => $unit,
                    'priceWithoutVat' => $noVat,
                    'vat'        => (float)$m['rate'],
                    'vatSum'         => $vat,
                    'priceVatSum'    => $priceVatSum,
                ];
                $sumQty   += $qty;
                $sumPrice += $priceVatSum;
            }
        }

        return ['items' => $items, 'sumQty' => $sumQty, 'sumPrice' => $sumPrice];
    }

    private static function czToFloat(string $s): float
    {
        $s = str_replace(["\xC2\xA0",' '], '', $s);
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
        return (float)$s;
    }
}
