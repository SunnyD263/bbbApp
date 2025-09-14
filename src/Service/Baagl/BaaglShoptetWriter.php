<?php
declare(strict_types=1);

namespace App\Service\Baagl;

use App\Service\ShoptetWriterFunc;
use App\Domain\ShoptetData;
use Psr\Log\LoggerInterface;

final class BaaglShoptetWriter
{
    private const MAINWH = 'Výchozí sklad';
    private const COMPANY = 'BAAGL';

    public function __construct(
        private LoggerInterface $logger,
        private ShoptetWriterFunc $fn,
    ) {}

    /** Nová položka z feedu */
    public function add(array $row, string $warehouse): ShoptetData
    {
        $stockMainWh = 0;
        $stockExtWh = (int) $row["stav"];

        $parametrs = $this->fn->getParameters(
            height:     (string)$row['vyska'],
            width:      (string)$row['sirka'],
            depth:      (string)$row['hloubka'],
            weight:     (string)$row['hmotnost'],
            material:   (string)$row['material'],
            capacity:   (string)$row['nosnost']
            
        );

        // Sklady + dostupnost
        $warehouseItem = $this->fn->getWhArray($stockMainWh, $stockExtWh , $warehouse, $location = '');
        // Obrázky do jednotného tvaru
        $images = $this->fn->mapFeedImages($row['obrazky']->obr ?? []);

        $d = new ShoptetData();
        $d->name         = (string)($row['nazev'] ?? '');
        $d->seoTitle    = (string)($row['nazev'] ?? '');     
        $d->description  = (string)($row['popis'] ?? '');
        $d->code         = (string)($row['registracni_cislo'] ?? '');
        $d->manufacturer = self::COMPANY;
        $d->supplier     = self::COMPANY;

        $d->categories     = (array)$this->fn->getCategory((string)($row['category_name'] ?? ''));
        $d->images         = $images;
        $d->unit           = !empty($row['merna_jednotka']) ? (string)$row['merna_jednotka'] : 'ks';
        $d->ean            = (string) $row["ean"];
        $d->vat            = (int)$this->fn->getVAT($row['dph'] ?? null);
        $d->currency       = (string)($row['mena'] ?? 'CZK');
        $d->priceVat       = isset($row['cena']) ? (float)$row['cena'] : null;
        $d->purchasePrice  = isset($row['nakupni_cena']) ? (float)$row['nakupni_cena'] : null;
        $d->standardPrice  = isset($row['cena']) ? (float)$row['cena'] : null;

        $d->stock = $warehouseItem["stock"];
        $d->minimalAmount =  null;
        $d->maximalAmount =  null;
        $d->infoParameters = $parametrs["infoParameter"];
        $d->logistic = $parametrs["logistic"];        
        // $d->logHeight = isset($parametrs["logistic"]["HEIGHT"]) ? $parametrs["logistic"]["HEIGHT"] : null;
        // $d->logWidth = isset($parametrs["logistic"]["WIDTH"]) ? $parametrs["logistic"]["WIDTH"] : null;
        // $d->logDepth = isset($parametrs["logistic"]["DEPTH"]) ? $parametrs["logistic"]["DEPTH"] : null;
        // $d->logWeight =isset($parametrs["logistic"]["WEIGHT"]) ? $parametrs["logistic"]["WEIGHT"] : null;

        // Dostupnost/viditelnost
        $d->availabilityIn = (string)$warehouseItem["deposit"]["availability"];
        $d->visibility     = (string)$warehouseItem["deposit"]["visibility"];

        return $d;
    }

    /** Příjem (navýšení skladu) k existujícímu produktu */
    public function inbound(array $shoptetRow, array $row, string $warehouse): ShoptetData
    {
        $wh = $this->fn->getWhArray((int)($row['qty'] ?? 0), 'inbound', $shoptetRow);
        $availability = $this->fn->getAvailability($wh['stockMainWh'], $wh['stockExtWh']);

        $d = new ShoptetData();
        $d->warehouses    = (array)$wh['result'];
        $d->availabilityIn= (string)$availability['availability'];
        $d->visibility    = (string)$availability['visibility'];
        return $d;
    }

    /** Update existující položky (ceny, sklady, parametry…) */
    public function update(array $shoptetRow, array $row, string $warehouse): ShoptetData
    {
        $wh = $this->fn->getWhArray((int)($row['stav'] ?? 0), 'update', $shoptetRow);
        $availability = $this->fn->getAvailability($wh['stockMainWh'], $wh['stockExtWh']);
        $vat = (int)$this->fn->getVAT($row['dph'] ?? null);

        // Mapování bloků ze Shoptetu
        $images        = $this->fn->mapShoptetImages($shoptetRow['IMAGES']->IMAGE ?? null, (string)($shoptetRow['CODE'] ?? ''), $this->logger);
        $flags         = $this->fn->mapShoptetFlags($shoptetRow['FLAGS']->FLAG ?? null);
        $categories    = $this->fn->mapShoptetCategories($shoptetRow['CATEGORIES'] ?? null, (string)($shoptetRow['CODE'] ?? ''), $this->logger);
        $infoParameters= $this->fn->mapShoptetInfoParams($shoptetRow['INFORMATION_PARAMETERS']->INFORMATION_PARAMETER ?? null);

        // LOGISTIC -> do jednotlivých polí
        $log = $shoptetRow['LOGISTIC'] ?? null;

        // Akční data
        $actionFrom  = isset($shoptetRow['ACTION_PRICE_FROM'])
            ? \DateTimeImmutable::createFromFormat('Y-m-d', (string)$shoptetRow['ACTION_PRICE_FROM']) ?: null
            : null;
        $actionUntil = isset($shoptetRow['ACTION_PRICE_UNTIL'])
            ? \DateTimeImmutable::createFromFormat('Y-m-d', (string)$shoptetRow['ACTION_PRICE_UNTIL']) ?: null
            : null;

        $d = new ShoptetData();
        $d->name         = (string)($shoptetRow['NAME'] ?? '');
        $d->description  = (string)($shoptetRow['DESCRIPTION'] ?? '');
        $d->manufacturer = self::COMPANY;
        $d->supplier     = self::COMPANY;
        $d->adult        = (int)($shoptetRow['ADULT'] ?? 0);
        $d->itemType     = (string)($shoptetRow['ITEM_TYPE'] ?? 'product');

        $d->categories     = $categories;
        $d->images         = $images;
        $d->infoParameters = $infoParameters;
        $d->flags          = $flags;

        $d->visibility      = (string)$availability['visibility'];
        $d->seoTitle        = (string)($shoptetRow['SEO_TITLE'] ?? '');
        $d->allowsIPlatba   = (int)($shoptetRow['ALLOWS_IPLATBA'] ?? 1);
        $d->allowsPayOnline = (int)($shoptetRow['ALLOWS_PAY_ONLINE'] ?? 1);
        $d->freeShipping    = (int)($shoptetRow['FREE_SHIPPING'] ?? 0);
        $d->freeBilling     = (int)($shoptetRow['FREE_BILLING'] ?? 0);
        $d->unit            = (string)($shoptetRow['UNIT'] ?? 'ks');

        $d->code = (string)($shoptetRow['CODE'] ?? '');
        $d->ean  = (string)($shoptetRow['EAN'] ?? '');

        if ($log) {
            $d->logDepth  = $this->fn->toFloatOrNull($log['DEPTH']  ?? null);
            $d->logWidth  = $this->fn->toFloatOrNull($log['WIDTH']  ?? null);
            $d->logHeight = $this->fn->toFloatOrNull($log['HEIGHT'] ?? null);
            $d->logWeight = $this->fn->toFloatOrNull($log['WEIGHT'] ?? null);
        }

        $atyp = $shoptetRow['ATYPICAL_PRODUCT'] ?? null;
        if ($atyp) {
            $d->atypicalShipping = (int)($atyp->ATYPICAL_SHIPPING ?? 0);
            $d->atypicalBilling  = (int)($atyp->ATYPICAL_BILLING  ?? 0);
        }

        $d->currency      = (string)($shoptetRow['CURRENCY'] ?? 'CZK');
        $d->vat           = $vat;
        $d->priceVat      = isset($row['cena']) ? (float)$row['cena'] : null;
        $d->purchasePrice = isset($row['nakupni_cena']) ? (float)$row['nakupni_cena'] : null;
        $d->standardPrice = isset($row['cena']) ? (float)$row['cena'] : null;

        $d->warehouses    = (array)$wh['result'];
        $d->minimalAmount = isset($shoptetRow['STOCK']->MINIMAL_AMOUNT) ? (int)$shoptetRow['STOCK']->MINIMAL_AMOUNT : null;
        $d->maximalAmount = isset($shoptetRow['STOCK']->MAXIMAL_AMOUNT) ? (int)$shoptetRow['STOCK']->MAXIMAL_AMOUNT : null;

        $d->availabilityOut = (string)($shoptetRow['AVAILABILITY_OUT_OF_STOCK'] ?? 'Momentálně nedostupné');
        $d->availabilityIn  = (string)$availability['availability'];
        $d->visible         = isset($shoptetRow['VISIBLE']) ? (int)$shoptetRow['VISIBLE'] : 1;

        $d->productNumber   = (string)($shoptetRow['PRODUCT_NUMBER'] ?? $d->code);
        $d->firmyCz         = (int)($shoptetRow['FIRMY_CZ'] ?? 1);
        $d->heurekaHidden   = (int)($shoptetRow['HEUREKA_HIDDEN'] ?? 0);
        $d->heurekaCartHidden = (int)($shoptetRow['HEUREKA_CART_HIDDEN'] ?? 0);
        $d->zboziHidden     = (int)($shoptetRow['ZBOZI_HIDDEN'] ?? 0);
        $d->arukeresoHidden = (int)($shoptetRow['ARUKERESO_HIDDEN'] ?? 0);
        $d->arukeresoMarketplaceHidden = (int)($shoptetRow['ARUKERESO_MARKETPLACE_HIDDEN'] ?? 0);
        $d->decimalCount    = (int)($shoptetRow['DECIMAL_COUNT'] ?? 0);
        $d->negativeAmount  = (int)($shoptetRow['NEGATIVE_AMOUNT'] ?? 0);
        $d->priceRatio      = (float)($shoptetRow['PRICE_RATIO'] ?? 1);
        $d->minPriceRatio   = (float)($shoptetRow['MIN_PRICE_RATIO'] ?? 0);

        $d->actionPrice     = isset($shoptetRow['ACTION_PRICE']) ? (float)$shoptetRow['ACTION_PRICE'] : null;
        $d->actionFrom      = $actionFrom;
        $d->actionUntil     = $actionUntil;
        $d->applyLoyaltyDiscount  = (int)($shoptetRow['APPLY_LOYALTY_DISCOUNT'] ?? 1);
        $d->applyVolumeDiscount   = (int)($shoptetRow['APPLY_VOLUME_DISCOUNT'] ?? 0);
        $d->applyQuantityDiscount = (int)($shoptetRow['APPLY_QUANTITY_DISCOUNT'] ?? 1);
        $d->applyDiscountCoupon   = (int)($shoptetRow['APPLY_DISCOUNT_COUPON'] ?? 0);

        return $d;
    }
}
