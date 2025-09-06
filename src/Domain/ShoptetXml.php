<?php
// src/Domain/ShoptetXml.php
namespace App\Domain;

final class ShoptetData
{
    public function __construct(
        public ?string $name = null,
        public ?string $shortDescription = null,
        public ?string $description = null,
        public ?string $manufacturer = null,
        public ?string $supplier = null,
        public ?string $warranty = null,
        public ?int $adult = null,
        public ?string $itemType = null,
        public array $categories = [],    
        public array $images = [],   
        public array $informationParameter = [],
        public array $flag = [],   
        public ?string $visibility = null,   
        public ?string $seoTitle = null,    
        public ?int $allowsIplatba = null,      
        public ?int $allowsPayOnline = null, 
        public ?int $freeShipping = null,      
        public ?int $freeBilling = null,         
        public ?string $unit = null,                 
        public ?string $code = null,     
        public ?string $ean = null,
        public array $logistic = [],
        public ?int $atypicalShipping = null,
        public ?int $atypicalBilling = null,
        public ?string $currency = null,
        public ?int $vat = null,
        public ?float $priceVat = null,
        public ?float $purchasePrice = null,
        public ?float $standardPrice = null,
        public ?int $minimalAmount = null,
        public ?int $maximalAmount = null,
        public ?string $availabilityOutOfStock = null,
        public ?string $availabilityInStock = null,
        public ?int $visible = null,
        public ?string $productNumber = null,
        public ?int $firmyCz = null,
        public ?int $heurekaHidden = null,
        public ?int $heurekaCartHidden = null,
        public ?int $zboziHidden = null,
        public ?int $arukeresoHidden = null,
        public ?int $arukeresoMarketplaceHidden = null,
        public ?int $decimalCount = null,
        public ?int $negativeAmount = null,
        public ?int $priceRatio = null,
        public ?int $minPriceRatio = null,
        public ?float $actionPrice = null,
        public ?\DateTimeInterface $actionFrom = null,
        public ?\DateTimeInterface $actionUntil = null,
        public ?int $applyLoyaltyDiscount = null,
        public ?int $applyVolumeDiscount = null,
        public ?int $applyQuantityDiscount = null,
        public ?int $applyDiscountCoupon = null,
        public ?array $stock = [],  
        public ?string $weight = null,
        public ?string $height = null,
        public ?string $width = null,
        public ?string $depth = null,    
    
    ) 
    {
        $this->warranty ??= '2 roky';
        $this->adult ??= 0;
        $this->itemType ??= 'product';
        $this->visibility ??= 'visible';
        $this->allowsIplatba  ??= 1;  
        $this->allowsPayOnline  ??= 1;
        $this->freeShipping  ??= 0;  
        $this->freeBilling  ??= 0;                  
        $this->atypicalShipping ??= 0;
        $this->atypicalBilling ??= 0;
        $this->currency ??= 'CZK';
        $this->availabilityOutOfStock ??= 'Momentálně nedostupné';
        $this->availabilityInStock ??= 'Skladem ve skladu e-shopu';
        $this->visible  ??= 1;  
        $this->firmyCz  ??= 1;  
        $this->heurekaHidden  ??= 0;  
        $this->heurekaCartHidden  ??= 0; 
        $this->zboziHidden  ??= 0;  
        $this->arukeresoHidden  ??= 0;
        $this->arukeresoMarketplaceHidden  ??= 0;  
        $this->decimalCount  ??= 0;
        $this->negativeAmount  ??= 0;  
        $this->priceRatio  ??= 1;  
        $this->minPriceRatio  ??= 0;  
        $this->actionPrice  ??= 0;  
        $this->actionFrom ??= new \DateTimeImmutable('1999-01-01');  
        $this->actionUntil  ??= new \DateTimeImmutable('1999-01-01');  
        $this->applyLoyaltyDiscount  ??= 1; 
        $this->applyVolumeDiscount  ??= 0;  
        $this->applyQuantityDiscount  ??= 1;  
        $this->applyDiscountCoupon  ??= 0;  
    }
}
