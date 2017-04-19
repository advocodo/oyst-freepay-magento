<?php
/**
 *
 * File containing class Oyst_Oyst_Helper_Catalog_Tax
 *
 * PHP version 5
 *
 * @category Onibi
 * @author   Onibi <dev@onibi.fr>
 * @license  Copyright 2017, Onibi
 * @link     http://www.onibi.fr
 */

/**
 * @category Onibi
 * @class  Oyst_Oyst_Helper_Catalog_Tax
 */
class Oyst_Oyst_Helper_Catalog_Tax extends Mage_Tax_Helper_Data
{
    /**
     * get price from product
     * WARNING : Magento function rewrited
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   float $price inputed product price
     * @param   bool $includingTax return price include tax flag
     * @param   null|Mage_Customer_Model_Address $shippingAddress
     * @param   null|Mage_Customer_Model_Address $billingAddress
     * @param   null|int $ctc customer tax class
     * @param   null|Mage_Core_Model_Store $store
     * @param   bool $priceIncludesTax flag what price parameter contain tax
     * @param   bool $roundPrice
     * @param   bool $onlyPercent
     * @return  float
     * @see Mage_Tax_Helper_Data::getPrice()
     */
    public function getPrice($product, $price, $includingTax = null, $shippingAddress = null, $billingAddress = null, $ctc = null, $store = null, $priceIncludesTax = null, $roundPrice = true, $onlyPercent = true)
    {
        if (! $price) {
            return $price;
        }
        $store = $this->_app->getStore($store);

        // OYST : with oyst, this is only api so not frontend => Magento function disable
        // if (!$this->needPriceConversion($store)) {
        // return $store->roundPrice($price);
        // }

        if (is_null($priceIncludesTax)) {
            $priceIncludesTax = $this->priceIncludesTax($store);
        }

        $percent = $product->getTaxPercent();
        $includingPercent = null;

        $taxClassId = $product->getTaxClassId();
        if (is_null($percent) && $taxClassId) {
            $request = Mage::getSingleton('tax/calculation')->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
            $percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
            // OYST
            if ($onlyPercent) {
                return $percent;
            }
        }
        if ($taxClassId && $priceIncludesTax) {
            if ($this->isCrossBorderTradeEnabled($store)) {
                $includingPercent = $percent;
            } else {
                $request = Mage::getSingleton('tax/calculation')->getRateOriginRequest($store);
                $includingPercent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
            }
        }

        if (($percent === false || is_null($percent)) && $priceIncludesTax && ! $includingPercent) {
            return $price;
        }

        $product->setTaxPercent($percent);
        if ($product->getAppliedRates() == null) {
            $request = Mage::getSingleton('tax/calculation')->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
            $request->setProductClassId($taxClassId);
            $appliedRates = Mage::getSingleton('tax/calculation')->getAppliedRates($request);
            $product->setAppliedRates($appliedRates);
        }

        if (! is_null($includingTax)) {
            if ($priceIncludesTax) {
                if ($includingTax) {
                    /**
                     * Recalculate price include tax in case of different rates.
                     * Otherwise price remains the same.
                     */
                    if ($includingPercent != $percent) {
                        // determine the customer's price that includes tax
                        $price = $this->_calculatePriceInclTax($price, $includingPercent, $percent, $store);
                    }
                } else {
                    $price = $this->_calculatePrice($price, $includingPercent, false);
                }
            } else {
                if ($includingTax) {
                    $appliedRates = $product->getAppliedRates();
                    if (count($appliedRates) > 1) {
                        $price = $this->_calculatePriceInclTaxWithMultipleRates($price, $appliedRates);
                    } else {
                        $price = $this->_calculatePrice($price, $percent, true);
                    }
                }
            }
        } else {
            if ($priceIncludesTax) {
                switch ($this->getPriceDisplayType($store)) {
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX:
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH:
                        if ($includingPercent != $percent) {
                            // determine the customer's price that includes tax
                            $taxablePrice = $this->_calculatePriceInclTax($price, $includingPercent, $percent, $store);
                            // determine the customer's tax amount,
                            // round tax unless $roundPrice is set explicitly to false
                            $tax = $this->getCalculator()->calcTaxAmount($taxablePrice, $percent, true, $roundPrice);
                            // determine the customer's price without taxes
                            $price = $taxablePrice - $tax;
                        } else {
                            // round tax first unless $roundPrice is set to false explicitly
                            $price = $this->_calculatePrice($price, $includingPercent, false, $roundPrice);
                        }
                        break;
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX:
                        $price = $this->_calculatePrice($price, $includingPercent, false);
                        $price = $this->_calculatePrice($price, $percent, true);
                        break;
                }
            } else {
                switch ($this->getPriceDisplayType($store)) {
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX:
                        $appliedRates = $product->getAppliedRates();
                        if (count($appliedRates) > 1) {
                            $price = $this->_calculatePriceInclTaxWithMultipleRates($price, $appliedRates);
                        } else {
                            $price = $this->_calculatePrice($price, $percent, true);
                        }
                        break;
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH:
                    case Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX:
                        break;
                }
            }
        }

        if ($roundPrice) {
            return $store->roundPrice($price);
        }
        return $price;
    }
}
