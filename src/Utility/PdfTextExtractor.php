<?php
namespace App\Utility;

use App\Utility\PdfTextCleaner;
use Smalot\PdfParser\Parser;

final class PdfTextExtractor
{
    public function __construct(private readonly Parser $smalot) {}

    public function extract(string $pdfPath): string
    {
        $pdf  = $this->smalot->parseFile($pdfPath);
        $text = $pdf->getText();
        return PdfTextCleaner::clean($text);
    }
}
