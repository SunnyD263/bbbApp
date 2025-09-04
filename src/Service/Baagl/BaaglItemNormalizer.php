<?php
// src/Service/Baagl/BaaglItemNormalizer.php

namespace App\Service\Baagl;

use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BaaglItemNormalizer extends AbstractController
{
    public function __construct(
        private readonly LegacyCategoryResolver $categoryResolver,
        private readonly string $ignoreRegnumPath,
        private readonly int $imageSlots = 21,
    ) {}

    public function normalize(SimpleXMLElement $xml): array
    {
        $suffixes = ['-SK', '-EN', '-DE'];
        $blacklistSkupin = ['024','043','130','132','133','145','146','147','153','154','169'];

        $ignoredRegNums = is_file($this->ignoreRegnumPath)
            ? array_filter(array_map('trim', file($this->ignoreRegnumPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
            : [];

        // Podpora obou struktur: <items><item/></items> i přímo <item/>
        $itemsRoot = isset($xml->items) ? $xml->items : $xml;

        $toRemove = [];

        foreach ($itemsRoot->item as $i => $item) {
            $regnum = (string) mb_strtoupper((string)($item->registracni_cislo ?? ''), 'UTF-8');
            $extId  = (string) ($item->skupzbo ?? '');
            $title  = (string) ($item->nazev ?? '');

            $remove = false;

            // 1) blacklist skupin
            if (in_array($extId, $blacklistSkupin, true)) {
                $remove = true;
            }

            // 2) ignorované regnum
            if (!$remove && in_array($regnum, $ignoredRegNums, true)) {
                $remove = true;
            }

            // 3) suffix v regnum
            if (!$remove) {
                foreach ($suffixes as $suffix) {
                    if (mb_strpos($regnum, $suffix) !== false) {
                        $remove = true;
                        break;
                    }
                }
            }

            if ($remove) {
                $toRemove[] = (int) $i;
                continue;
            }

            // 4) primární skupina (fallback 999)
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
                $missId = new SimpleXMLElement('<skupina></skupina>');
                $missId->addChild('id', '999');
                $missId->addAttribute('primary', 'true');
                $skupina = $missId;
            }

            // 5) kategorie přes tvůj původní resolver
            $cat = $this->categoryResolver->resolve('Baagl', $extId, $title);
        if ($cat === null) {
                $this->addFlash('success', 
                sprintf("Kategorie pro zboží %s - ExtId: %d - Code: %s nenalezena.",
                $title, $extId, $regnum ));
                $toRemove[] = (int) $i;
                continue;
            }

            // 6) doplnění category_id / category_name (bez duplicit)
            $catId   = (string) ($cat['shoptet_id'] ?? '');
            $catName = (string) ($cat['cat_name'] ?? '');

            if (isset($item->category_id)) { $item->category_id = $catId; }
            else { $item->addChild('category_id', $catId); }

            if (isset($item->category_name)) { $item->category_name = $catName; }
            else { $item->addChild('category_name', $catName); }
        
        }

        rsort($toRemove);
        foreach ($toRemove as $idx) {
            unset($itemsRoot->item[$idx]);
        }

        return (array) $xml;
    }
}
