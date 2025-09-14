<?php
// src/Domain/ShoptetXml.php
namespace App\Domain;

use DOMDocument;
use DOMElement;
use function App\Domain\ShoptetXmlFunc\addText;

final class ShoptetXml
{
    /** @param ShoptetData[] $items */
    public function build($items): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $shop = $doc->createElement('SHOP');
        $doc->appendChild($shop);

        foreach ($items as $d) {
            $this->appendShopItem($doc, $shop, $d);
        }

        return $doc->saveXML();
    }

    private function appendShopItem(DOMDocument $doc, DOMElement $shop, ShoptetData $d): void
    {
        $it = $doc->createElement('SHOPITEM');
        if ($d->id !== null) {
            $it->setAttribute('id', (string)$d->id);
        }
        $shop->appendChild($it);

        // Základ
        ShoptetXmlFunc::addText($doc, $it, 'NAME', $d->name);
        ShoptetXmlFunc::addCdata($doc, $it, 'SHORT_DESCRIPTION', $d->shortDescription);
        ShoptetXmlFunc::addCdata($doc, $it, 'DESCRIPTION', $d->description);
        ShoptetXmlFunc::addText($doc, $it, 'MANUFACTURER', $d->manufacturer);
        ShoptetXmlFunc::addText($doc, $it, 'WARRANTY', $d->warranty);
        ShoptetXmlFunc::addText($doc, $it, 'SUPPLIER', $d->supplier);
        ShoptetXmlFunc::addText($doc, $it, 'ADULT', (string)$d->adult);
        ShoptetXmlFunc::addText($doc, $it, 'ITEM_TYPE', $d->itemType);

        // Bloky
        ShoptetXmlFunc::appendCategories($doc, $it, $d->categories);
        ShoptetXmlFunc::appendImages($doc, $it, $d->images);
        ShoptetXmlFunc::appendInfoParams($doc, $it, $d->infoParameters);
        ShoptetXmlFunc::appendFlags($doc, $it, $d->flags);

        // Viditelnost/SEO/Platební
        ShoptetXmlFunc::addText($doc, $it, 'VISIBILITY', $d->visibility);
        ShoptetXmlFunc::addText($doc, $it, 'SEO_TITLE', $d->seoTitle);
        ShoptetXmlFunc::addText($doc, $it, 'ALLOWS_IPLATBA', (string)$d->allowsIPlatba);
        ShoptetXmlFunc::addText($doc, $it, 'ALLOWS_PAY_ONLINE', (string)$d->allowsPayOnline);
        ShoptetXmlFunc::addText($doc, $it, 'INTERNAL_NOTE', $d->internalNote);
        ShoptetXmlFunc::addText($doc, $it, 'HEUREKA_CATEGORY_ID', $d->heurekaCategoryId);
        ShoptetXmlFunc::addText($doc, $it, 'ZBOZI_CATEGORY_ID', $d->zboziCategoryId);
        ShoptetXmlFunc::addText($doc, $it, 'GOOGLE_CATEGORY_ID', $d->googleCategoryId);
        ShoptetXmlFunc::addText($doc, $it, 'GLAMI_CATEGORY_ID', $d->glamiCategoryId);
        ShoptetXmlFunc::addText($doc, $it, 'FREE_SHIPPING', (string)$d->freeShipping);
        ShoptetXmlFunc::addText($doc, $it, 'FREE_BILLING', (string)$d->freeBilling);
        ShoptetXmlFunc::addText($doc, $it, 'UNIT', $d->unit);

        // Kódy
        ShoptetXmlFunc::addText($doc, $it, 'CODE', $d->code);
        ShoptetXmlFunc::addText($doc, $it, 'EAN', $d->ean);
        ShoptetXmlFunc::addText($doc, $it, 'PRODUCT_NUMBER', $d->productNumber ?? $d->code);

        // Logistika
        ShoptetXmlFunc::appendLogistic($doc, $it, $d->logistic);
        // ShoptetXmlFunc::addText($doc, $logistic, 'DEPTH', (string)$d->logHeight);
        // ShoptetXmlFunc::addText($doc, $logistic, 'WIDTH', (string)$d->logWidth);
        // ShoptetXmlFunc::addText($doc, $logistic, 'HEIGHT', (string)$d->logDepth);
        // ShoptetXmlFunc::addText($doc, $logistic, 'WEIGHT', (string)$d->logWeight);

        // Atyp
        $atyp = $doc->createElement('ATYPICAL_PRODUCT');
        ShoptetXmlFunc::addText($doc, $atyp, 'ATYPICAL_SHIPPING', (string)$d->atypicalShipping);
        ShoptetXmlFunc::addText($doc, $atyp, 'ATYPICAL_BILLING', (string)$d->atypicalBilling);
        $it->appendChild($atyp);

        // Ceny/DPH
        ShoptetXmlFunc::addText($doc, $it, 'CURRENCY', $d->currency);
        ShoptetXmlFunc::addText($doc, $it, 'VAT', (string)$d->vat);
        ShoptetXmlFunc::addText($doc, $it, 'PRICE_VAT', ShoptetXmlFunc::f($d->priceVat));
        ShoptetXmlFunc::addText($doc, $it, 'PURCHASE_PRICE', ShoptetXmlFunc::f($d->purchasePrice));
        ShoptetXmlFunc::addText($doc, $it, 'STANDARD_PRICE', ShoptetXmlFunc::f($d->standardPrice));

        // Sklady
        $stockNode = $doc->createElement('STOCK');
        ShoptetXmlFunc::appendStock($doc, $stockNode, $d->stock);
        ShoptetXmlFunc::addText($doc, $stockNode, 'MINIMAL_AMOUNT', $d->minimalAmount);
        ShoptetXmlFunc::addText($doc, $stockNode, 'MAXIMAL_AMOUNT', $d->maximalAmount);
        $it->appendChild($stockNode);
        // Dostupnosti/viditelnosti
        ShoptetXmlFunc::addText($doc, $it, 'AVAILABILITY_OUT_OF_STOCK', $d->availabilityOut);
        ShoptetXmlFunc::addText($doc, $it, 'AVAILABILITY_IN_STOCK', $d->availabilityIn);
        ShoptetXmlFunc::addText($doc, $it, 'VISIBLE', (string)$d->visible);

        // Market + přepínače
        ShoptetXmlFunc::addText($doc, $it, 'FIRMY_CZ', (string)$d->firmyCz);
        ShoptetXmlFunc::addText($doc, $it, 'HEUREKA_HIDDEN', (string)$d->heurekaHidden);
        ShoptetXmlFunc::addText($doc, $it, 'HEUREKA_CART_HIDDEN', (string)$d->heurekaCartHidden);
        ShoptetXmlFunc::addText($doc, $it, 'HEUREKA_CPC', $d->heurekaCpc);
        ShoptetXmlFunc::addText($doc, $it, 'ZBOZI_HIDDEN', (string)$d->zboziHidden);
        ShoptetXmlFunc::addText($doc, $it, 'ZBOZI_CPC', $d->zboziCpc);
        ShoptetXmlFunc::addText($doc, $it, 'ZBOZI_SEARCH_CPC', $d->zboziSearchCpc);
        ShoptetXmlFunc::addText($doc, $it, 'ARUKERESO_HIDDEN', (string)$d->arukeresoHidden);
        ShoptetXmlFunc::addText($doc, $it, 'ARUKERESO_MARKETPLACE_HIDDEN', (string)$d->arukeresoMarketplaceHidden);
        ShoptetXmlFunc::addText($doc, $it, 'DECIMAL_COUNT', (string)$d->decimalCount);
        ShoptetXmlFunc::addText($doc, $it, 'NEGATIVE_AMOUNT', (string)$d->negativeAmount);

        // Jednotky měr a poměry
        $uom = $doc->createElement('UNIT_OF_MEASURE');
        ShoptetXmlFunc::addText($doc, $uom, 'PACKAGE_AMOUNT', $d->packageAmount);
        ShoptetXmlFunc::addText($doc, $uom, 'PACKAGE_AMOUNT_UNIT', $d->packageAmountUnit);
        ShoptetXmlFunc::addText($doc, $uom, 'MEASURE_AMOUNT', $d->measureAmount);
        ShoptetXmlFunc::addText($doc, $uom, 'MEASURE_AMOUNT_UNIT', $d->measureAmountUnit);
        $it->appendChild($uom);

        ShoptetXmlFunc::addText($doc, $it, 'PRICE_RATIO', ShoptetXmlFunc::f($d->priceRatio));
        ShoptetXmlFunc::addText($doc, $it, 'MIN_PRICE_RATIO', ShoptetXmlFunc::f($d->minPriceRatio));

        // Akce
        ShoptetXmlFunc::addText($doc, $it, 'ACTION_PRICE', ShoptetXmlFunc::f($d->actionPrice));
        ShoptetXmlFunc::addText($doc, $it, 'ACTION_PRICE_FROM', ShoptetXmlFunc::date($d->actionFrom) ?? '1999-01-01');
        ShoptetXmlFunc::addText($doc, $it, 'ACTION_PRICE_UNTIL', ShoptetXmlFunc::date($d->actionUntil) ?? '1999-01-01');

        // Přepínače slev
        ShoptetXmlFunc::addText($doc, $it, 'APPLY_LOYALTY_DISCOUNT', (string)$d->applyLoyaltyDiscount);
        ShoptetXmlFunc::addText($doc, $it, 'APPLY_VOLUME_DISCOUNT', (string)$d->applyVolumeDiscount);
        ShoptetXmlFunc::addText($doc, $it, 'APPLY_QUANTITY_DISCOUNT', (string)$d->applyQuantityDiscount);
        ShoptetXmlFunc::addText($doc, $it, 'APPLY_DISCOUNT_COUPON', (string)$d->applyDiscountCoupon);
    }
}
