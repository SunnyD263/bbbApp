<?php
declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class ShoptetWriterFunc
{
    /**
     * Skladové pole pro ShoptetData::warehouses
     * - $mode: add|update|inbound
     * - $shoptetRow: volitelně současný stav z Shoptetu
     * return [
     *   'result'      => [['name'=>'Výchozí sklad','value'=>int], ...],
     *   'stockMainWh' => int,
     *   'stockExtWh'  => int,
     * ]
     */ 

    public function getStock(int $stockMainWh, int $stockExtWh = null, string $mode): array
    {
            switch($mode)
            {
                case 'inbound':



                default:
            
            }    

        return  $result;
    }

    public function getParameters(
        ?string $height = null,
        ?string $width = null,
        ?string $depth = null,
        ?string $weight = null,
        ?string $material = null,
        ?string $capacity = null
    ): array {
        $info = [];
        $logistic = [];

        // Přidá jen tehdy, když hodnota NENÍ null / prázdná / „0“
        $this->addNode($info, 'Výška',   $height,   'cm');
        $this->addNode($info, 'Šířka',   $width,    'cm');
        $this->addNode($info, 'Hloubka', $depth,    'cm');
        $this->addNode($info, 'Nosnost', $capacity, 'kg');
        $this->addNode($info, 'Materiál',$material);

        $this->addNode($logistic, 'HEIGHT', strtr($height,  [',' => '.']), '', false);
        $this->addNode($logistic, 'WIDTH',  strtr($width,   [',' => '.']), '', false); 
        $this->addNode($logistic, 'DEPTH',  strtr($depth,   [',' => '.']), '', false); 
        $this->addNode($logistic, 'WEIGHT', strtr($weight,  [',' => '.']), '', false); 


        $infoNode = [];
        if ($info instanceof SimpleXMLElement || is_array($info)) {
            foreach ($info as $p) {
                $infoNode[] = [
                    'name'  => (string)($p["name"] ?? ''),
                    'value' => (string)( $p["value"] ?? ''),
                ];
            }
        }

        return [
            'infoParameter' => $infoNode,
            'logistic'      => $logistic,
        ];
    }

    private function addNode(
        array &$target, string $name, ?string $value, ?string $unit = null, bool $asAssoc = true
    ): void {
        // 1) pokud je to null → přeskočit
        if ($value === null) {
            return;
        }

        // 2) očistit a případně přeskočit i prázdno/„0“ (když nechceš 0 považovat za platnou)
        $v = trim($value);
        if ($v === '' || $v === '0' || $v === '0.0' || $v === '0.00') {
            return;
        }

        if ($unit) {
            $v .= ' ' . $unit;
        }
        if ($asAssoc){
            $target[] = ['name' => $name, 'value' => str_replace(',', '.', $v)];
        } else {    
            $target[$name] = str_replace(',', '.', $v);
        }

    }


    public function getWhArray(int $stockMainWh, int $stockExtWh, $warehouse = null, $location = ''): array
    {
            if (isset($warehouse))
            {
                $stock = [
                    ['name' => 'Výchozí sklad', 'value' => $stockMainWh, 'location' => $location],
                    ['name' => $warehouse, 'value' => $stockExtWh, 'location' => '']
                ];
            } else {
                    $stock = [
                    ['name' => 'Výchozí sklad', 'value' => $stockMainWh,'location' => $location],
                ];
            }

        $total = $stockMainWh + $stockExtWh;
        if ($stockMainWh > 0){
            $deposit = [
                'availability' => 'Skladem na prodejně', 
                'visibility'   => 'visible',
            ];        
        } elseif ($total > 0) {
            $deposit = [
                'availability' => 'Skladem ve skladu e-shopu',
                'visibility'   => 'visible',
            ];
        } else {
            $deposit = [
                'availability' => 'Skladem na prodejně',
                'visibility'   => 'hidden',
            ];
        }

        if ($stock instanceof SimpleXMLElement || is_array($stock)) {
            foreach ($stock as $p) {
                $stockNode[] = [
                    'name'  => (string)($p["name"] ?? ''),
                    'value' => (string)($p["value"] ?? ''),
                    'location' => (string)($p["location"] ?? ''),
                ];
            }
        }

    
        return [
            'stock' => $stockNode,
            'deposit' => $deposit
        ];

    }

    /** Vrací integer DPH (např. 21) z libovolného vstupu (21, "21", "21%") */
    public function getVAT(mixed $raw): int
    {
        if ($raw === null || $raw === '') return 21;
        if (is_numeric($raw)) return (int)$raw;
        $raw = (string)$raw;
        $raw = str_replace('%', '', $raw);
        return is_numeric($raw) ? (int)$raw : 21;
    }

    /**
     * Kategorie ve tvaru:
     * [['id'=>int, 'name'=>string, 'default'=>true], ...]
     * Uprav mapování ID dle své tabulky.
     */
    public function getCategory(string $categoryPath): array
    {
        $id = $this->mapCategoryId($categoryPath);
        return [
            ['id' => $id, 'name' => $categoryPath, 'default' => true],
        ];
    }

    // ------------------- MAPOVÁNÍ / KONVERZE -------------------

    /** Převod na float nebo null */
    public function toFloatOrNull(null|string|int|float $v): ?float
    {
        if ($v === null || $v === '') return null;
        return is_numeric($v) ? (float)$v : null;
    }

    /** Feed: SimpleXMLElement|array|string -> [['url'=>..., 'description'=>null], ...] */
    public function mapFeedImages(mixed $images, mixed $alternativeImages = null): array
    {
        $out = [];
        if ($images instanceof SimpleXMLElement || is_array($images)) {
            foreach ($images as $img) {
                $url = (string)$img;
                if ($url !== '') {
                    $out[] = ['url' => $url];
                }
            }
        } elseif (is_string($images) && $images !== '') {
            $out[] = ['url' => $images];
        }
        return $out;
    }

    /** Shoptet XML -> [['url'=>..., 'description'=>?], ...] */
    public function mapShoptetImages(mixed $imageNode, string $codeForLog = '', ?LoggerInterface $logger = null): array
    {
        $out = [];
        if ($imageNode instanceof SimpleXMLElement || is_array($imageNode)) {
            foreach ($imageNode as $img) {
                $url = (string)$img;
                if ($url === '') continue;
                $desc = null;
                if ($img instanceof SimpleXMLElement && isset($img['description'])) {
                    $desc = (string)$img['description'];
                }
                $out[] = ['url' => $url, 'description' => $desc ?: null];
            }
        } else {
            $logger?->warning('Missing IMAGES for code='.$codeForLog);
        }
        return $out;
    }

    /** FLAGS -> [['code'=>..., 'active'=>...], ...] */
    public function mapShoptetFlags(mixed $flagsNode): array
    {
        $out = [];
        if ($flagsNode instanceof SimpleXMLElement || is_array($flagsNode)) {
            foreach ($flagsNode as $flag) {
                $out[] = [
                    'code'   => (string)($flag->CODE ?? ''),
                    'active' => (int)($flag->ACTIVE ?? 0),
                ];
            }
        }
        return $out;
    }

    /** CATEGORIES -> [['id'=>..., 'name'=>..., 'default'?:true], ...] */
    public function mapShoptetCategories(mixed $categoriesNode, string $codeForLog = '', ?LoggerInterface $logger = null): array
    {
        $out = [];
        if (!$categoriesNode) {
            $logger?->warning('Missing CATEGORY for code='.$codeForLog);
            return $out;
        }

        if (isset($categoriesNode->CATEGORY)) {
            foreach ($categoriesNode->CATEGORY as $c) {
                $out[] = [
                    'id'   => (int)($c['id'] ?? 0),
                    'name' => (string)$c,
                ];
            }
        }

        if (isset($categoriesNode->DEFAULT_CATEGORY)) {
            $def = $categoriesNode->DEFAULT_CATEGORY;
            $id  = (int)($def['id'] ?? 0);
            $name= (string)$def;

            // označení default
            $found = false;
            foreach ($out as &$row) {
                if (($row['id'] ?? null) === $id) {
                    $row['default'] = true;
                    $found = true;
                    break;
                }
            }
            if ($id && !$found) {
                $out[] = ['id'=>$id, 'name'=>$name, 'default'=>true];
            }
        }

        return $out;
    }

    /** INFORMATION_PARAMETERS -> [['name'=>..., 'value'=>...], ...] */
    public function mapShoptetInfoParams(mixed $paramsNode): array
    {
        $out = [];
        if ($paramsNode instanceof SimpleXMLElement || is_array($paramsNode)) {
            foreach ($paramsNode as $p) {
                $out[] = [
                    'name'  => (string)($p->NAME ?? ''),
                    'value' => (string)($p->VALUE ?? ''),
                ];
            }
        }
        return $out;
    }

    // ------------------- PRIVÁTNÍ MAPOVÁNÍ -------------------

    private function mapCategoryId(string $categoryPath): int
    {
        // TODO: reálné mapování podle tvojí tabulky
        return match ($categoryPath) {
            'Školní potřeby > Aktovky' => 1159,
            default => 9999,
        };
    }
}
