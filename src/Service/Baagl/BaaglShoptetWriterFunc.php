<?php
// src\Service\Baagl\BaaglShoptetWriterFunc.php
namespace App\Service\Baagl;

use SimpleXMLElement;

final class BaaglShoptetWriterFunc {

    private const MAINWH = 'Výchozí sklad';
    private const COMPANY = 'BAAGL';

    public function getVat(string $vat){
        switch ($vat){
            case 'high':
                $result = 21;
                break;
            case 'low':
                $result = 12;
                break;
            default:
                $result = 0;
        }
        return $result;
    }

    public function getAvailability(int $stockMainWh, int $stockExtWh){
        $visibility = null;
        $availability = null;

        if($stockMainWh > 0){
            $availability = 'Skladem na prodejně';
        } 

        if(($stockMainWh + $stockExtWh) <= 0){
            $visibility = 'hidden';
        }       
        return [
            'availability' => $availability,
            'visibility' => $visibility,            
        ];            
    }

    public function getWhStock(array $row, string $warehouse){
        foreach($row["STOCK"]->WAREHOUSES->WAREHOUSE as $item){
            if($item->NAME == $warehouse){
                $result = (int) $item->VALUE;
                break; 
            }
        }
        return $result;
    }

    public function getCategory(string $category){
        $result = new SimpleXMLElement('<CATEGORIES/>');
        $result->addChild('CATEGORY', $category);
        $result->addChild('DEFAULT_CATEGORY', $category);
        return $result;
    }

    public function getWhArray(int $stock, string $func, ?array $shoptetRow = null, ?string $location = null) : array {
        
        $result = new SimpleXMLElement('<WAREHOUSES/>');
            
        switch($func){
            case 'inbound':
                $stockMainWh = (int)($stock + (int)$this->getWhStock((array) $shoptetRow , self::MAINWH));
                $stockExtWh = (int)$this->getWhStock((array) $shoptetRow , self::COMPANY); 
                // 1) Výchozí sklad
                $w0 = $result->addChild('WAREHOUSE');
                $w0->addChild('NAME', self::MAINWH);
                $w0->addChild('VALUE', $stockMainWh); 
                $w0->addChild('LOCATION',$location ?? ''); 
                $stockMainWh = 
                // 2) Dynamický sklad
                $w1 = $result->addChild('WAREHOUSE');
                $w1->addChild('NAME', self::COMPANY);
                $w1->addChild('VALUE', $stockExtWh);
                $w1->addChild('LOCATION',$location ?? '');
                break;
            case 'update':
                $stockMainWh = (int)$this->getWhStock((array) $shoptetRow , self::MAINWH);
                $stockExtWh = (int)$this->getWhStock((array) $shoptetRow , self::COMPANY); 
                // 1) Výchozí sklad
                $w0 = $result->addChild('WAREHOUSE');
                $w0->addChild('NAME', self::MAINWH);
                $w0->addChild('VALUE', $stockMainWh);
                $w0->addChild('LOCATION',$location ?? ''); 

                // 2) Dynamický sklad
                $w1 = $result->addChild('WAREHOUSE');
                $w1->addChild('NAME', self::COMPANY);
                $w1->addChild('VALUE', (string)$stock);
                $w1->addChild('LOCATION',$location ?? '');
                break;
            case 'add':
                $stockMainWh = 0;
                $stockExtWh = $stock;
                // 1) Výchozí sklad
                $w0 = $result->addChild('WAREHOUSE');
                $w0->addChild('NAME', self::MAINWH);
                $w0->addChild('VALUE', '0');
                $w0->addChild('LOCATION',$location ?? ''); 

                // 2) Dynamický sklad
                $w1 = $result->addChild('WAREHOUSE');
                $w1->addChild('NAME', self::COMPANY);
                $w1->addChild('VALUE', '0');
                $w1->addChild('LOCATION',$location ?? '');
                break;
        }        
        return [
                'result' => $result,
                'stockMainWh' => (int)$stockMainWh,
                'stockExtWh' => (int)$stockExtWh
            ];
    }


}