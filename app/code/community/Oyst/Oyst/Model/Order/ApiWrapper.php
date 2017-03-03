<?php
/**
 * 
 * File containing class Oyst_Oyst_Model_Order_ApiWrapper
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
 * @class  Oyst_Oyst_Model_Order_ApiWrapper
 */
class Oyst_Oyst_Model_Order_ApiWrapper extends Mage_Core_Model_Abstract
{

    /**
     * Retrieve order from Oyst
     * 
     * @param Int $oystOrderId
     * @return array
     */
    public function getOrder($oystOrderId)
    {
        $response = Mage::getModel('oyst_oyst/api')->send(Oyst_Oyst_Model_Api::TYPE_GETORDER, $oystOrderId);
        return $response;
    }

    /**
     * Update Order Status to Oyst
     * 
     * @param Int $oystOrderId
     * @param string $status
     * @return array
     */
    public function putOrder($oystOrderId, $status)
    {
        //if order id => update to Oyst
        if ($oystOrderId && $status) {
            $response = Mage::getModel('oyst_oyst/api')->send(Oyst_Oyst_Model_Api::TYPE_PUTORDER, array(
                'oyst_order_id' => $oystOrderId,
                'status' => $status
            ));
        } else {
            $response = Mage::helper('oyst_oyst')->__("order id %s or status %s not found", $oystOrderId, $status);
        }
        return $response;
    }
}
