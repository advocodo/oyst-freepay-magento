<?php

/**
 * 
 * File containing class Oyst_Oyst_Model_Observer
 *
 * PHP version 5
 *
 * @category Onibi
 * @author   Onibi <dev@onibi.fr>
 * @license  Copyright 2017, Onibi
 * @link     http://www.onibi.fr
 */

/**
 *
 * @category Onibi
 * @class Oyst_Oyst_Model_Observer
 */
class Oyst_Oyst_Model_Observer
{
    /**
     * Add mass action on product grid
     * 
     * @param Varien_Event_Observer $observer
     * @return Oyst_Oyst_Model_Observer
     */
    public function addCatalogMassAction($observer)
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
     * @return Oyst_Oyst_Model_Observer
     */
    public function sendProductUpdate($observer)
    {
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
     * @return Oyst_Oyst_Model_Observer
     */
    public function sendOrderStatusUpdate($observer)
    {
        // if it's not while order import
        if (! Mage::registry('order_status_changing')) {
            return $this;
        }
        $order = $observer->getOrder();
        if ($state = $order->getState() != $order->getOrigData('state')) {
            $oystStatus = '';
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
}