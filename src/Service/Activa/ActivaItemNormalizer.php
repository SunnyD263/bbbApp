<?php
// src/Service/Activa/ActivaItemNormalizer.php

namespace App\Service\Activa;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class ActivaItemNormalizer
{
    private const PRICE_KOEFICIENT = 1.55;

    public function __construct(
        private readonly LegacyCategoryResolver $categoryResolver,
        private readonly string $regnumPath,
        private readonly int $imageSlots = 21,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function normalize(SimpleXMLElement $xml, string $callFrom): SimpleXMLElement
    {



        $regNums = is_file($this->regnumPath)
        ? array_filter(array_map(
            fn($l) => mb_strtoupper(trim($l), 'UTF-8'),
            file($this->regnumPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        ))
        : [];
    
        $items = $xml->xpath('./SHOPITEM') ?? [];

        foreach ($items as $item) {
            $regnum = mb_strtoupper((string)($item->ITEM_ID ?? ''), 'UTF-8');
            $extId  = (string) ($item->ITEMGROUP_ID ?? '');
            $title  = (string) ($item->PRODUCTNAME ?? '');

            $remove = false;

            if (!$remove && !in_array($regnum, $regNums, true)) {
                $remove = true;
            }

            // if (!$remove) {
            //     $cat = $this->categoryResolver->resolve('Activa', $extId, $title);
            //     if ($cat === null) {
            //         if ($callFrom == 'import'){
            //             $this->logger?->info(sprintf(
            //                 'Kategorie nenalezena: "%s" (ExtId: %s, Regnum: %s). Položka bude zahozená.',
            //                 $title, $extId, $regnum
            //             ));
            //         }
            //         $remove = true;
            //     } else {
            //         $catId   = (string) ($cat['shoptet_id'] ?? '');
            //         $catName = (string) ($cat['cat_name'] ?? '');

            //         $this->setOrAddChild($item, 'category_id', $catId);
            //         $this->setOrAddChild($item, 'category_name', $catName);

            //         // přepočet ceny
            //         $cena = round((float)($item->nakupni_cena ?? 0) * self::PRICE_KOEFICIENT, 0);
            //         $this->setOrAddChild($item, 'cena', (string)$cena);
            //     }
            // }

            if ($remove) {
                $this->removeNode($item);
                continue; // na další snapshotový item
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
