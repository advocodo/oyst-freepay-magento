<?php
/**
 * This file is part of Oyst_Oyst for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <dev@oyst.com> <@oystcompany>
 * @category Oyst
 * @package Oyst_Oyst
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Data Helper
 */
class Oyst_Oyst_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Global function for log if enabled
     *
     * @param string $message
     *
     * @return null
     */
    public function log($message)
    {
        if (Mage::getStoreConfig('payment/oyst_abstract/log_enable')) {
            Mage::log($message, null, 'oyst.log', true);
        }
    }

    /**
     * Determine if the payment method is oyst
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return boolean
     */
    public function isPaymentMethodOyst(Mage_Sales_Model_Order $order)
    {
        /** @var Oyst_Oyst_Model_Payment_Method_Freepay $paymentMethod */
        $freepayPaymentMethod = Mage::getModel('oyst_oyst/payment_method_freepay');

        return strpos($order->getPayment()->getMethod(), $freepayPaymentMethod->getCode()) !== false;
    }

    /**
     * Get the extension version
     *
     * @return string Extension version
     */
    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Oyst_Oyst->version;
    }

    /**
     * Set initialization status config flag and refresh config cache.
     *
     * @param bool $isInitialized Flag for initialization
     */
    public function setIsInitialized($isInitialized = true)
    {
        $isInitialized = (bool)$isInitialized ? '1' : '0';
        Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData('oyst/freepay/is_initialized', $isInitialized);
        Mage::app()->getCacheInstance()->cleanType('config');
        Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'config'));
    }
}
