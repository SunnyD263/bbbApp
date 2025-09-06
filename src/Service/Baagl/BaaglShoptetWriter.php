<?php
// src\Service\Baagl\BaaglShoptetWriter.php
namespace App\Service\Baagl;

use App\Domain\ShoptetData;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class BaaglShoptetWriter
{
    private const MAINWH = 'Výchozí sklad';
    private const COMPANY = 'BAAGL';

    public function __construct(
        private LoggerInterface $logger, 
        private BaaglShoptetWriterFunc $fn,
    ) {}

    //*************************** ADD ********************************/
    public function add(array $row, string $warehouse)
    {
        $dimensions = [
            'Výška'   => ['value' => $row['vyska'] ?? '', 'unit' => 'cm'],
            'Šířka'   => ['value' => $row['sirka'] ?? '', 'unit' => 'cm'],
            'Hloubka' => ['value' => $row['hloubka'] ?? '', 'unit' => 'cm'],
            'Nosnost' => ['value' => $row['nosnost'] ?? '', 'unit' => 'kg'],
        ];

        foreach ($dimensions as $name => $data) {
            $value = $data['value'];
            $unit = $data['unit'];
            $informationParameter = [];
            if ($value !== '0.00' && $value !== '0.0' && $value !== '') {
                $informationParameter[] = [
                    'NAME'  => $name,
                    'VALUE' => $value . ' ' . $unit,
                ];
            }
        }

        $stock = $this->fn->getWhArray($row["stav"] ,'add');
        $availability = $this->fn->getAvailability($stock["stockMainWh"],$stock["stockExtWh"]);


        return new ShoptetData(

            name: (string) $row["nazev"],
            description:(string) $row["popis"],
            code: (string) $row["registracni_cislo"],
            manufacturer: (string) self::COMPANY,
            supplier: (string) self::COMPANY,
            categories: (array) $this->fn->getCategory($row["category_name"]),
            images: (array) $row["obrazky"]->obr,
            unit: (string) $row["merna_jednotka"],
            stock: (array) $stock["result"],  
            vat: $this->fn->getVAT($row["dph"]),
            currency:(string) $row["mena"],
            priceVat: (float) $row["cena"],
            purchasePrice: (float)$row["nakupni_cena"],            
            standardPrice: (float)$row["cena"],
            informationParameter: (Array) $informationParameter,
            weight:(string) $row['hmotnost'],
            height:(string) $row['vyska'],
            width: (string) $row['sirka'],
            depth: (string) $row['hloubka'],
            availabilityInStock: $availability["availability"], 
            visibility: $availability["visibility"],
        );
    }

    //*************************** INBOUND ********************************/
    public function inbound(array $shoptetRow, array $row, string $warehouse)
    {
        $stock = $this->fn->getWhArray($row["qty"],'inbound',$shoptetRow );
        $availability = $this->fn->getAvailability($stock["stockMainWh"],$stock["stockExtWh"]);


        return new ShoptetData(           
            stock: (array) $stock["result"],
            availabilityInStock: $availability["availability"], 
            visibility: $availability["visibility"],
        );
    } 
    
    //*************************** UPDATE ********************************/
    public function update(array $shoptetRow, array $row, string $warehouse)
    {
        $stock = (array)($this->fn->getWhArray($row["stav"],'update',$shoptetRow));
        $availability = $this->fn->getAvailability($stock["stockMainWh"],$stock["stockExtWh"]);

        $vat = $this->fn->getVAT($row["dph"]);

        $images = (array)($shoptetRow["IMAGES"]->IMAGE ?? null)?->IMAGE;
        if (isset($shoptetRow["IMAGES"]->IMAGE[0])){
            if ($shoptetRow["IMAGES"]->IMAGE[0] == '') {
                $this->logger->warning('Missing IMAGES for code=' . (string)($shoptetRow['CODE'] ?? ''));  
            }
        }

        $flagsIter = ($shoptetRow['FLAGS'] ?? null)?->FLAG;
        $flags = $flagsIter ? iterator_to_array($flagsIter, false) : [];
 
        if (isset($shoptetRow["CATEGORIES"]->CATEGORY)){
            if ($shoptetRow["CATEGORIES"]->CATEGORY == '') {
                $this->logger->warning('Missing CATEGORY for code=' . (string)($shoptetRow['CODE'] ?? ''));  
                $categories = [];
            } else {
            $categories = $shoptetRow["CATEGORIES"];
            }
        }

        if(isset($shoptetRow["INFORMATION_PARAMETERS"]->INFORMATION_PARAMETER)){
            $informationParameter =  $shoptetRow["INFORMATION_PARAMETERS"]->INFORMATION_PARAMETER;   
        } else {
            $informationParameter = [];
        }

        $actionFrom  = ($shoptetRow['ACTION_PRICE_FROM']  ?? null)
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $shoptetRow['ACTION_PRICE_FROM']) : null;
        $actionUntil = ($shoptetRow['ACTION_PRICE_UNTIL'] ?? null)
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $shoptetRow['ACTION_PRICE_UNTIL']) : null;

        return new ShoptetData(

            name: (string) $shoptetRow["NAME"],
            description:(string) ($shoptetRow["DESCRIPTION"] ?? ''),
            manufacturer: (string) self::COMPANY,
            supplier: (string) self::COMPANY,
            adult: (int) $shoptetRow["ADULT"],
            itemType: (string) $shoptetRow["ITEM_TYPE"],
            categories: (array)$categories,
            images: (array)$images,
            informationParameter: (array) $informationParameter,
            flag: (array) $flags, 
            visibility: $availability["visibility"],
            seoTitle: (string) $shoptetRow["SEO_TITLE"],
            allowsIplatba: (int) $shoptetRow["ALLOWS_IPLATBA"],
            allowsPayOnline: (int) $shoptetRow["ALLOWS_PAY_ONLINE"],
            freeShipping: (int) $shoptetRow["FREE_SHIPPING"],
            freeBilling: (int) $shoptetRow["FREE_BILLING"],
            unit: (string) $shoptetRow["UNIT"], 
            code: (string) $shoptetRow["CODE"],
            ean: (string) $shoptetRow["EAN"],
            logistic: (array) $shoptetRow["LOGISTIC"],
            atypicalShipping: (int) $shoptetRow["ATYPICAL_PRODUCT"]->ATYPICAL_SHIPPING,
            atypicalBilling: (int) $shoptetRow["ATYPICAL_PRODUCT"]->ATYPICAL_BILLING,
            currency: (string) $shoptetRow["CURRENCY"],     
            vat: (int) $vat,
            priceVat: (float) $row["cena"],
            purchasePrice: (float)$row["nakupni_cena"],            
            standardPrice: (float)$row["cena"],
            minimalAmount: (int)$shoptetRow["STOCK"]->MINIMAL_AMOUNT,
            maximalAmount: (int)$shoptetRow["STOCK"]->MAXIMAL_AMOUNT,
            stock: (array) $stock["result"],
            availabilityOutOfStock: (string)$shoptetRow["AVAILABILITY_OUT_OF_STOCK"],
            availabilityInStock: $availability["availability"], 
            visible: (int)$shoptetRow["VISIBLE"],
            productNumber: $shoptetRow["PRODUCT_NUMBER"],
            firmyCz: (int)$shoptetRow["FIRMY_CZ"],
            heurekaHidden: (int)$shoptetRow["HEUREKA_HIDDEN"],
            heurekaCartHidden: (int)$shoptetRow["HEUREKA_CART_HIDDEN"],
            zboziHidden: (int)$shoptetRow["ZBOZI_HIDDEN"],
            arukeresoHidden:(int)$shoptetRow["ARUKERESO_HIDDEN"],
            arukeresoMarketplaceHidden: (int)$shoptetRow["ARUKERESO_MARKETPLACE_HIDDEN"],
            decimalCount: (int)$shoptetRow["DECIMAL_COUNT"],
            negativeAmount: (int)$shoptetRow["NEGATIVE_AMOUNT"],
            priceRatio: (int)$shoptetRow["PRICE_RATIO"],
            minPriceRatio: (int)$shoptetRow["MIN_PRICE_RATIO"],
            actionPrice: (float)$shoptetRow["ACTION_PRICE"],
            actionFrom: $actionFrom,
            actionUntil: $actionUntil,
            applyVolumeDiscount: (int)$shoptetRow["APPLY_VOLUME_DISCOUNT"],
            applyQuantityDiscount: (int)$shoptetRow['APPLY_QUANTITY_DISCOUNT'],
            applyDiscountCoupon: (int)$shoptetRow['APPLY_DISCOUNT_COUPON'],
        );
    }
}