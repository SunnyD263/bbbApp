<?php
// src/Domain/ShoptetData.php
namespace App\Domain;

final class ShoptetData
{
    // Základ
    public ?int    $id               = null;
    public ?string $guid             = null;
    public ?string $name             = null;
    public ?string $shortDescription = null; // HTML (CDATA)
    public ?string $description      = null; // HTML (CDATA)
    public ?string $manufacturer     = null;
    public ?string $supplier         = null;
    public string  $warranty         = '2 roky';
    public int     $adult            = 0;    // 0/1
    public string  $itemType         = 'product';

    // Kateg./obrázky/parametry/vlajky
    /** @var array<int, array{id:int,name:string,default?:bool}> */
    public array $categories = [];
    /** @var array<int, array{url:string,description?:string}> */
    public array $images = [];
    /** @var array<int, array{name:string,value:string}> */
    public array $infoParameters = [];
    /** @var array<int, array{code:string,active:int}> */
    public array $flags = [];
    /** @var array<int, array{name:string,value:float}> */
    public array $logistic= [];

    // Viditelnost/SEO/platební
    public string  $visibility      = 'visible';
    public ?string $seoTitle        = null;
    public int     $allowsIPlatba   = 1;
    public int     $allowsPayOnline = 1;
    public int     $freeShipping    = 0;
    public int     $freeBilling     = 0;
    public ?string $unit            = 'ks';

    // Kódy
    public ?string $code           = null;
    public ?string $ean            = null;
    public ?string $productNumber  = null;

    // Logistika
    public ?string $logDepth  = null;
    public ?string $logWidth  = null;
    public ?string $logHeight = null;
    public ?string $logWeight = null;
    public int    $atypicalShipping = 0;
    public int    $atypicalBilling  = 0;

    // Ceny/DPH
    public string  $currency      = 'CZK';
    public ?int    $vat           = 21;
    public ?float  $priceVat      = null;
    public ?float  $purchasePrice = null;
    public ?float  $standardPrice = null;
    public ?float  $actionPrice   = null;
    public ?\DateTimeInterface $actionFrom  = null;
    public ?\DateTimeInterface $actionUntil = null;

    // Sklady
    /** @var array<int, array{name:string,value:int,location?:string}> */
    public array $stock = [];
    public ?int  $minimalAmount = null;
    public ?int  $maximalAmount = null;

    // Dostupnost/viditelnost
    public string $availabilityOut = 'Momentálně nedostupné';
    public string $availabilityIn  = 'Skladem ve skladu e-shopu';
    public int    $visible         = 1;

    // Marketplaces a přepínače
    public int     $firmyCz                    = 1;
    public int     $heurekaHidden              = 0;
    public int     $heurekaCartHidden          = 0;
    public ?string $heurekaCpc                 = null;
    public int     $zboziHidden                = 0;
    public ?string $zboziCpc                   = null;
    public ?string $zboziSearchCpc             = null;
    public int     $arukeresoHidden            = 0;
    public int     $arukeresoMarketplaceHidden = 0;

    public int     $decimalCount   = 0;
    public int     $negativeAmount = 0;

    // Jednotky měr / poměry cen
    public ?string $packageAmount     = null;
    public ?string $packageAmountUnit = null;
    public ?string $measureAmount     = null;
    public ?string $measureAmountUnit = null;
    public float   $priceRatio        = 1;
    public float   $minPriceRatio     = 0;

    // Interní poznámky a ID kategorií trhů (volitelné)
    public ?string $internalNote      = null;
    public ?string $heurekaCategoryId = null;
    public ?string $zboziCategoryId   = null;
    public ?string $googleCategoryId  = null;
    public ?string $glamiCategoryId   = null;
    public int $applyLoyaltyDiscount = 1;
    public int $applyVolumeDiscount = 0;
    public int $applyQuantityDiscount = 0;
    public int $applyDiscountCoupon = 1;
}
