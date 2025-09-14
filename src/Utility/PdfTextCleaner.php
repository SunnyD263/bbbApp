<?php

namespace App\Utility;

final class PdfTextCleaner
{
    public static function clean(string $s): string
    {
        // NBSP -> space
        $s = str_replace("\xC2\xA0", ' ', $s);
        // odstranění řídicích znaků (Cc, Cf) kromě \n a \t
        $s = preg_replace('/[\p{Cc}\p{Cf}&&[^\n\t]]/u', '', $s);
        // sloučení vícenásobných mezer
        $s = preg_replace('/[ \t]+/u', ' ', $s);
        // normalizace konců řádků
        $s = preg_replace('/\r\n?/', "\n", $s);
        return trim($s);
    }
}
