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
        if (Mage::getStoreConfig('oyst/global_settings/log_enable')) {
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
        return strpos($order->getPayment()->getMethod(), Oyst_Oyst_Model_Payment_Method_Oyst::PAYMENT_METHOD_CODE) !== false;
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
}
