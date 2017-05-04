<?php
/**
 * This file is part of Oyst_Oyst for Magento.
 *
 * @license All rights reserved, Oyst
 * @author Oyst <dev@oyst.com> <@oystcompany>
 * @category Oyst
 * @package Oyst_Oyst
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Payment_Method_Oyst Model
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
