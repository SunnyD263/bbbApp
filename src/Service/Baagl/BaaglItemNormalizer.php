<?php
// src/Service/Baagl/BaaglItemNormalizer.php

namespace App\Service\Baagl;

use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BaaglItemNormalizer
{
    private const PRICE_KOEFICIENT = 1.55;

    public function __construct(
        private readonly LegacyCategoryResolver $categoryResolver,
        private readonly string $ignoreRegnumPath,
        private readonly int $imageSlots = 21,
        // ideálně sem přidej LoggerInterface $logger a používej ho
    ) {}

    public function normalize(SimpleXMLElement $xml): SimpleXMLElement
    {
        $suffixes = ['-SK', '-EN', '-DE'];
        $blacklistSkupin = ['024','043','130','132','133','145','146','147','153','154','169'];

        $ignoredRegNums = is_file($this->ignoreRegnumPath)
            ? array_filter(array_map('trim', file($this->ignoreRegnumPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
            : [];

        $itemsRoot = isset($xml->items) ? $xml->items : $xml;

        foreach ($itemsRoot->item as $item) {
            $regnum = (string) mb_strtoupper((string)($item->registracni_cislo ?? ''), 'UTF-8');
            $extId  = (string) ($item->skupzbo ?? '');
            $title  = (string) ($item->nazev ?? '');

            $remove = false;

            // 1) blacklist
            if (in_array($extId, $blacklistSkupin, true)) {
                $remove = true;
            }

            // 2) ignorované regnum
            if (!$remove && in_array($regnum, $ignoredRegNums, true)) {
                $remove = true;
            }

            // 3) suffix kdekoliv v regnum
            if (!$remove) {
                foreach ($suffixes as $suffix) {
                    if (mb_strpos($regnum, $suffix) !== false) {
                        $remove = true;
                        break;
                    }
                }
            }

            if (!$remove) {
                $cat = $this->categoryResolver->resolve('Baagl', $extId, $title);
                if ($cat === null) {
                    // service: logovat, ne addFlash
                    // $this->logger->warning(sprintf('Kategorie pro zboží %s - ExtId: %s - Code: %s nenalezena.', $title, $extId, $regnum));
                    $remove = true;
                } else {
                    $catId   = (string) ($cat['shoptet_id'] ?? '');
                    $catName = (string) ($cat['cat_name'] ?? '');

                    if (isset($item->category_id)) { $item->category_id = $catId; }
                    else { $item->addChild('category_id', $catId); }

                    if (isset($item->category_name)) { $item->category_name = $catName; }
                    else { $item->addChild('category_name', $catName); }

                    // přepočet ceny
                    $item->cena = round((float)($item->nakupni_cena ?? 0) * self::PRICE_KOEFICIENT, 0);
                }
            }

            if ($remove) {
                // Bezpečné smazání přes DOM:
                $node = dom_import_simplexml($item);
                if ($node !== false && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
                // pokračuj na další item
                continue;
            }

            // 4) primární skupina (fallback 999)
            $skupina = null; // <<< inicializace!
            if (isset($item->skupiny_zbozi)) {
                foreach ($item->skupiny_zbozi as $skupiny_zbozi) {
                    if (isset($skupiny_zbozi->skupina[0])) {
                        foreach ($skupiny_zbozi->skupina as $skupinaCheck) {
                            if ((string)$skupinaCheck->primary === 'true') {
                                $skupina = $skupinaCheck;
                                break 2;
                            }
                        }
                    }
                }
            }
            if ($skupina === null) {
                $missId = new SimpleXMLElement('<skupina primary="true"><id>999</id></skupina>');
                $skupina = $missId;
            }

        }

        return $xml->item;
    }
}
