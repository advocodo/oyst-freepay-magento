<?php

/**
 * 
 * File containing class Oyst_Oyst_PaymentController
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
 *           @class Oyst_Oyst_PaymentController
 */
class Oyst_Oyst_PaymentController extends Mage_Core_Controller_Front_Action
{

    /**
     * Cancel the order in case of payment error
     *
     * @return null
     */
    public function cancelAction()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        if (! $checkoutSession->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if ($lastOrderId) {
            Mage::getSingleton('core/session')->addError(Mage::helper('oyst_oyst')->__("Order %s cancelled", $lastOrderId));
            $orderModel = Mage::getModel('sales/order')->load($lastOrderId);

            if ($lastQuoteId && $orderModel->canCancel()) {
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();

                $orderModel->cancel();
                $orderModel->setStatus('canceled');
                $orderModel->save();

                $this->_redirect('checkout/cart', array('_secure' => true));
                return;
            }
        }
        Mage::helper('oyst_oyst')->log('Order Cancel Error');
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Redirect customer to success if payment return is success
     *
     * @return null
     */
    public function returnAction()
    {
        $this->_redirect('checkout/onepage/success', array(
            '_secure' => true
        ));
    }

    /**
     * Cancel the order in case of payment error
     *
     * @return null
     */
    public function errorAction()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        if (! $checkoutSession->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if ($lastOrderId) {
            Mage::getSingleton('core/session')->addError(Mage::helper('oyst_oyst')->__("An error occured with order %s", $lastOrderId));
            $orderModel = Mage::getModel('sales/order')->load($lastOrderId);

            if ($lastQuoteId && $orderModel->canCancel()) {
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();

                $orderModel->cancel();
                $orderModel->setStatus('canceled');
                $orderModel->save();

                $this->_redirect('checkout/cart', array('_secure' => true));
                return;
            }
        }
        Mage::helper('oyst_oyst')->__('Payment Error');
        $this->_redirect('checkout/cart', array('_secure' => true));
        return;
    }
}