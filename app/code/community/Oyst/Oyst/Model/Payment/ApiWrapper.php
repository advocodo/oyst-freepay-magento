<?php
/**
 * 
 * File containing class Oyst_Oyst_Model_Payment_ApiWrapper
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
 * @class  Oyst_Oyst_Model_Payment_ApiWrapper
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
        $response = Mage::getModel('oyst_oyst/api')->send(Oyst_Oyst_Model_Api::TYPE_PAYMENT, $params);
        return $response['url'];
    }
}
