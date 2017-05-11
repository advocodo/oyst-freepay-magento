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
 * Payment Controller
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
            Mage::getSingleton('core/session')->addError(
                Mage::helper('oyst_oyst')->__("Order %s cancelled", $lastOrderId)
            );
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
        $this->_redirect(
            'checkout/onepage/success', array(
            '_secure' => true
            )
        );
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

            return null;
        }

        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if ($lastOrderId) {
            Mage::getSingleton('core/session')->addError(
                Mage::helper('oyst_oyst')->__("An error occured with order %s", $lastOrderId)
            );
            $orderModel = Mage::getModel('sales/order')->load($lastOrderId);

            if ($lastQuoteId && $orderModel->canCancel()) {
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();

                $orderModel->cancel();
                $orderModel->setStatus('canceled');
                $orderModel->save();

                $this->_redirect('checkout/cart', array('_secure' => true));

                return null;
            }
        }

        Mage::helper('oyst_oyst')->__('Payment Error');
        $this->_redirect('checkout/cart', array('_secure' => true));

        return null;
    }
}
