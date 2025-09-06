<?php
// src/Service/Baagl/BaaglItemNormalizer.php

namespace App\Service\Baagl;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class BaaglItemNormalizer
{
    private const PRICE_KOEFICIENT = 1.55;

    public function __construct(
        private readonly LegacyCategoryResolver $categoryResolver,
        private readonly string $ignoreRegnumPath,
        private readonly int $imageSlots = 21,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function normalize(SimpleXMLElement $xml, string $callFrom): SimpleXMLElement
    {
        $suffixes = ['-SK', '-EN', '-DE'];
        $blacklistSkupin = ['024','041','043','046','130','132','133','145','146','147','153','154','169'];

        $ignoredRegNums = is_file($this->ignoreRegnumPath)
            ? array_filter(array_map(
                fn($l) => mb_strtoupper(trim($l), 'UTF-8'),
                file($this->ignoreRegnumPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            ))
            : [];

        $itemsRoot = isset($xml->items) ? $xml->items : $xml;

        $items = $itemsRoot->xpath('item') ?? [];

        foreach ($items as $item) {
            $regnum = mb_strtoupper((string)($item->registracni_cislo ?? ''), 'UTF-8');
            $extId  = (string) ($item->skupzbo ?? '');
            $title  = (string) ($item->nazev ?? '');

            $remove = false;

            // 1) blacklist skupin
            if (in_array($extId, $blacklistSkupin, true)) {
                $remove = true;
            }

            // 2) ignorované regnum (po sjednocení na UPPER)
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
                    if ($callFrom == 'import'){
                        $this->logger?->info(sprintf(
                            'Kategorie nenalezena: "%s" (ExtId: %s, Regnum: %s). Položka bude zahozená.',
                            $title, $extId, $regnum
                        ));
                    }
                    $remove = true;
                } else {
                    $catId   = (string) ($cat['shoptet_id'] ?? '');
                    $catName = (string) ($cat['cat_name'] ?? '');

                    $this->setOrAddChild($item, 'category_id', $catId);
                    $this->setOrAddChild($item, 'category_name', $catName);

                    // přepočet ceny
                    $cena = round((float)($item->nakupni_cena ?? 0) * self::PRICE_KOEFICIENT, 0);
                    $this->setOrAddChild($item, 'cena', (string)$cena);
                }
            }

            if ($remove) {
                $this->removeNode($item);
                continue; // na další snapshotový item
            }

            // 4) primární skupina (fallback 999)
            $hasPrimary = false;
            if (isset($item->skupiny_zbozi)) {
                foreach ($item->skupiny_zbozi as $skupiny_zbozi) {
                    if (isset($skupiny_zbozi->skupina[0])) {
                        foreach ($skupiny_zbozi->skupina as $sk) {
                            // Podpora atributu i elementu, true i "1"
                            $primaryAttr = (string)$sk['primary'];
                            $primaryElem = (string)$sk->primary;
                            if ($primaryAttr === 'true' || $primaryAttr === '1' || $primaryElem === 'true' || $primaryElem === '1') {
                                $hasPrimary = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if (!$hasPrimary) {
                // zajistíme existenci <skupiny_zbozi> a vložíme fallback
                $sz = isset($item->skupiny_zbozi[0]) ? $item->skupiny_zbozi[0] : $item->addChild('skupiny_zbozi');
                $sk = $sz->addChild('skupina');
                $sk->addAttribute('primary', 'true');
                $sk->addChild('id', '999');
            }
        }

        return $xml;
    }

    private function removeNode(SimpleXMLElement $node): void
    {
        $dom = dom_import_simplexml($node);
        if ($dom !== false && $dom->parentNode) {
            $dom->parentNode->removeChild($dom);
        }
    }

    private function setOrAddChild(SimpleXMLElement $parent, string $name, string $value): void
    {
        if (isset($parent->$name)) {
            $parent->$name = $value;
        } else {
            $parent->addChild($name, $value);
        }
    }
}
