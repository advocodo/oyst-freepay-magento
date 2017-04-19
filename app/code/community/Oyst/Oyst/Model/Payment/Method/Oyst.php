<?php
/**
 *
 * File containing class Oyst_Oyst_Model_Payment_Method_Oyst
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
 * @class  Oyst_Oyst_Model_Payment_Method_Oyst
 */
class Oyst_Oyst_Model_Payment_Method_Oyst extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     * @var string
     */
    protected $_code = 'oyst';

    /**
     * Specify to magento that there is a 'payment_action'
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Specify to magento that the payment is not internal
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Specify to magento that the payment method is not for multiple shipping address
     * @var bool
     */
    protected $_canUseForMultishipping = false;

    /**
     * Native method for retrieve Oyst Form Redirect Url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = Mage::helper('oyst_oyst/payment_data')->getPaymentUrl();
        return $url;
    }
}
