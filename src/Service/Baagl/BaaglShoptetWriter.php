<?php
// src\Service\Baagl\BaaglShoptetWriter.php
namespace App\Service\Baagl;

use App\Domain\ShoptetData;

final class BaaglShoptetWriter
{
    private const COMPANY = 'BAAGL';

    public function add(array $row, string $warehouse)
    {
        return new ShoptetData(

            name: (string) $row["nazev"],
            description:(string) $row["popis"],
            code: (string) $row["registracni_cislo"],
            manufacturer: (string) self::COMPANY,
            supplier: (string) self::COMPANY,
            category: (string) $row["category_name"],
            defaultCategory: (string) $row["category_name"],
            images: (array) $row["obrazky"]->obr,
            unit: (string) $row["merna_jednotka"],
            stock: (int) $row["stav"],
            vat: $this->getVAT($row["dph"]),
            currency:(string) $row["mena"],
            priceVat: (float) $row["cena"],
        );
    }

    public function inbound(array $shoptetRow, array $row, string $warehouse)
    {
        return new ShoptetData(

            stock: (int) $row["qty"] + (int) $this->getWhStock($warehouse),
        );
    } 
    
    public function update(array $shoptetRow, array $row, string $warehouse)
    {
        return new ShoptetData(

            name: (string) $shoptetRow["NAME"],
            description:(string) $shoptetRow["DESCRIPTION"],
            manufacturer: (string) self::COMPANY,
            supplier: (string) self::COMPANY,
            adult: (int) $shoptetRow["ADULT"],
            itemType: (string) $shoptetRow["ITEM_TYPE"],
            category: (string) $shoptetRow["CATEGORIES"]->CATEGORY,
            defaultCategory: (string) $shoptetRow["CATEGORIES"]->DEFAULT_CATEGORY,
            images: (array) $shoptetRow["IMAGES"]->IMAGE,
            informationParameter: (array) $shoptetRow["INFORMATION_PARAMETERS"]->INFORMATION_PARAMETER,
            flag: (array) $shoptetRow["FLAGS"]->FLAG, 
            visibility: (string) $shoptetRow["VISIBILITY"],
            seoTitle: (string) $shoptetRow["SEO_TITLE"],
            allowsIplatba: (int) $shoptetRow["ALLOWS_IPLATBA"],
            allowsPayOnline: (int) $shoptetRow["ALLOWS_PAY_ONLINE"],
            freeShipping: (int) $shoptetRow["FREE_SHIPPING"],
            freeBilling: (int) $shoptetRow["FREE_BILLING"],
            unit: (string) $shoptetRow["UNIT"], 
            code: (string) $shoptetRow["CODE"],
            ean: (string) $shoptetRow["EAN"],
            atypicalShipping: (int) $shoptetRow["ATYPICAL_PRODUCT"]->ATYPICAL_SHIPPING,
            atypicalBilling: (int) $shoptetRow["ATYPICAL_PRODUCT"]->ATYPICAL_BILLING,
            currency: (string) $shoptetRow["CURRENCY"],     
            vat: (int) $this->getVAT($row["dph"]),
            currency:(string) $row["mena"],
            priceVat: (float) $row["cena"],
            purchasePrice: $row["nakupni_cena"],            
            standardPrice: $row["cena"],
            minimalAmount: $shoptetRow["STOCK"]->MINIMAL_AMOUNT,
            maximalAmount: $shoptetRow["STOCK"]->MAXIMAL_AMOUNT,
            stock: (int) $row["stav"],            
        );
    }
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

    public function getWhStock( string $warehouse){
        foreach($this->$shoptetRow["STOCK"]->WAREHOUSES->WAREHOUSE as $item){
            if($item->NAME == $warehouse){
                $result = (int) $item->VALUE;
                break; 
            }
        }
        return $result;
    }

}

