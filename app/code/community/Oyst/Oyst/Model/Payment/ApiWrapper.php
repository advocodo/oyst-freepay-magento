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
 * Payment_ApiWrapper Model
 */
class Oyst_Oyst_Model_Payment_ApiWrapper extends Mage_Core_Model_Abstract
{
    /**
     * Make api call for retrieve Oyst url
     *
     * @param array $params
     * @return string
     */
    public function getPaymentUrl($params)
    {
        /** @var OystPaymentAPI $paymentApi */
        $paymentApi = Mage::getModel('oyst_oyst/api')->sendPayment(Oyst_Oyst_Model_Api::TYPE_PAYMENT, $params);
        $response = $paymentApi->getResponse();

        return $response['url'];
    }
}
