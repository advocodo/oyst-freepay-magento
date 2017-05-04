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
 * Observer Model
 */
class Oyst_Oyst_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Add mass action on product grid
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_Oyst_Model_Observer
     */
    public function addCatalogMassAction(Varien_Event_Observer $observer)
    {
        // get MassAction blog from admin
        $block = $observer->getEvent()->getBlock();
        $block->getMassactionBlock()->addItem('send_to_oyst', array(
            'label' => Mage::helper('oyst_oyst')->__('Send To Oyst'),
            'url' => Mage::helper('adminhtml')->getUrl('adminhtml/oyst_catalog/sync'),
            'confirm' => Mage::helper('oyst_oyst')->__('Are you sure?')
        ));

        return $this;
    }

    /**
     * Update product event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_Oyst_Model_Observer
     */
    public function sendProductUpdate(Varien_Event_Observer $observer)
    {
        if (!$this->_getConfig("catalog", "enable")) {
            return $this;
        }
        // get product id
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getId();

        Mage::helper('oyst_oyst')->log('Start of update of product id : ' . $productId);
        $params = array(
            'product_id_include_filter' => array(
                $productId
            ),
            'action' => 'update'
        );

        // send product updates to Oyst
        Mage::helper('oyst_oyst/catalog_data')->sync($params);
        Mage::helper('oyst_oyst')->log('end of update of product id : ' . $productId);

        return $this;
    }

    /**
     * Update order status event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Oyst_Oyst_Model_Observer
     */
    public function sendOrderStatusUpdate(Varien_Event_Observer $observer)
    {
        // if it's not while order import
        if (!$this->_getConfig("order", "enable") || Mage::registry('order_status_changing')) {
            return $this;
        }
        
        $order = $observer->getOrder();
        $state = $order->getState();
        if ($order->getOrigData('state') != $state) {
            switch ($state) {
                case Mage_Sales_Model_Order::STATE_NEW:
                case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
                case Mage_Sales_Model_Order::STATE_HOLDED:
                case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
                    $oystStatus = 'pending';
                    break;
                case Mage_Sales_Model_Order::STATE_PROCESSING:
                case Mage_Sales_Model_Order::STATE_COMPLETE:
                    $oystStatus = 'accepted';
                    break;
                case Mage_Sales_Model_Order::STATE_CANCELED:
                    $oystStatus = 'denied';
                    break;
                case Mage_Sales_Model_Order::STATE_CLOSED:
                    $oystStatus = 'refunded';
                    break;
                default:
                    $oystStatus = '';
            }
        }
        Mage::helper('oyst_oyst')->log('Start of update of order id : ' . $order->getId());

        // sent status update
        Mage::helper('oyst_oyst/order_data')->updateStatus(array(
            'oyst_order_id' => $order->getOystOrderId(),
            'status' => $oystStatus
        ));
        Mage::helper('oyst_oyst')->log('End of update of order id : ' . $order->getId());

        return $this;
    }
    
    public function validateApiKey(Varien_Event_Observer $observer)
    {
        $config = $observer->getEvent()->getObject();
        if ($config->getSection() != "oyst") {
            return $this;
        }
        
        $groups = $config->getGroups();
        $globalSettings = $groups["global_settings"];
        
        if (
            $globalSettings["fields"]["enable"]["value"] == 1 &&
            !empty($globalSettings["fields"]["api_login"]["value"])
        ) {
            $apiKey = $globalSettings["fields"]["api_login"]["value"];
            
            $apiResponse = Mage::getModel('oyst_oyst/api')->validateApikeyFromApi($apiKey);
            if ($apiResponse != "true") {
                Mage::throwException(Mage::helper('oyst_oyst')->__("API key %s is not valid", $apiKey));
            }
        }

        return $this;
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($section, $code)
    {
        return Mage::getStoreConfig("oyst/" . $section . "_settings/" . $code);
    }
}
