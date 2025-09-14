<?php
// src/Service/Baagl/LegacyCategoryResolver.php

namespace App\Service\Baagl;

final class LegacyCategoryResolver
{
    public function __construct(
        private readonly string $legacyFilePath 
    ) {}

    /**
     * Vrací ['shoptet_id' => string, 'cat_name' => string] nebo null.
     */
    public function resolve(string $company, string $extId, string $name): ?array
    {
        static $loaded = false;
        if (!$loaded) {
            if (!is_file($this->legacyFilePath)) {
                throw new \RuntimeException(sprintf('Soubor s legacy mapou nenalezen: %s', $this->legacyFilePath));
            }
            require_once $this->legacyFilePath;
            $loaded = true;
            if (!\function_exists('getCategoryId')) {
                throw new \RuntimeException('V legacy souboru chybí funkce getCategoryId().');
            }
        }

        /** @var callable $fn */
        $fn = 'getCategoryId';
        $res = $fn($company, $extId, $name);
        if (is_array($res) && isset($res['shoptet_id'])) {
            return $res;
        }
        return null;
    }
}
