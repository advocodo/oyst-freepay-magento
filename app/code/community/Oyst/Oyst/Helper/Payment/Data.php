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
 * Payment_Data Helper
 */
class Oyst_Oyst_Helper_Payment_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Sync payment informations from notification
     *
     * @param array $event
     * @param array $data
     *
     * @return array
     */
    public function syncFromNotification($event, $data)
    {
        //get last notification

        /** @var Oyst_Oyst_Model_Notification $lastNotification */
        $lastNotification = Mage::getModel('oyst_oyst/notification');
        $lastNotification = $lastNotification->getLastNotification('payment', $data['payment_id']);

        //if last notification is not finished
        if ($lastNotification->getId() && 'finished' != $lastNotification->getStatus()) {
            Mage::throwException($this->__('Last Notification payment id %s is not finished', $data['payment_id']));
        }

        //create new notification in db with status 'start'
        /** @var Oyst_Oyst_Model_Notification $notification */
        $notification = Mage::getModel('oyst_oyst/notification');
        $notification->setData(
            array(
                'event' => $event,
                'oyst_data' => Zend_Json::encode($data),
                'status' => 'start',
                'created_at' => Mage::getSingleton('core/date')->gmtDate(),
                'executed_at' => Mage::getSingleton('core/date')->gmtDate(),
            )
        );
        $notification->save();

        //get order INCREMENT id
        if (empty($data['order_increment_id'])) {
            Mage::throwException($this->__('order_id not found for payment id %s', $data['payment_id']));
        }

        $orderIncrementId = $data['order_increment_id'];
        if (!$orderIncrementId) {
            Mage::throwException($this->__('Order Id not found'));
        }

        $params = array(
            'order_increment_id' => $orderIncrementId,
        );
        //if data asynchrone payment notification is success, we invoice the order, else we cancel
        if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_AUTHORISATION == $data['event_code'] && $data['success']) {
            $result = $this->invoice($params, $data);
        }

        if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_CAPTURE == $data['event_code'] && $data['success']) {
            $result = $this->invoice($params, $data);
        }

        if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_CANCELLATION == $data['event_code'] && $data['success']) {
            $params['notification'] = "$notification->getId()";
            $result = $this->cancel($params, $data);
        }

        if (!$data['success']) {
            $this->addNotificationHistoryComment(
                sprintf(
                    $this->__('Last %s notification fail.'),
                    $data['event_code']
                )
            )->save();
        }

        //save new status and result in db
        $notification->setStatus('finished')
            ->setOrderId($result['order_id'])
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();

        return array('order_id' => $result['order_id']);
    }

    /**
     * Create Invoice for order
     *
     * @param array $params
     *
     * @return array
     */
    public function invoice($params, $transactionData = false)
    {
        //get order
        $order = Mage::getModel('sales/order')->load($params['order_increment_id'], 'increment_id');

        //prepare invoice
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

        //prepare transaction and create invoice
        if ($transactionData) {
            $this->_addTransaction($order->getId(), $transactionData);
        } else {
            //pay offline
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            //don't notify customer
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);

            //save order and invoice
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        }

        return array('order_id' => $order->getId());
    }

    /**
     * Cancel Order
     *
     * @param array $params
     *
     * @return array
     */
    public function cancel($params, $data)
    {
        $order = Mage::getModel('sales/order')->load($params['order_increment_id'], 'increment_id');

        if ($order->canUnhold()) {
            $order->unhold()->save();
        }

        if (!$order->canCancel()) {
            $order->addStatusHistoryComment(
                $this->__('Cancel Order is not possible. Transaction ID: "%s".', $data['payment_id'])
            )->save();
        } else {
            try {
                $order->cancel();
                $order->getStatusHistoryCollection(true);
                $order->save();

                $order->addStatusHistoryComment(
                    $this->__('Success Cancel Order. Transaction ID: "%s".', $data['payment_id'])
                )->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }

        }

        return array(
            'order_id' => $order->getId()
        );
    }

    /**
     * Add history in Order
     *
     * @param array $params
     */
    public function addNotificationHistoryComment($params)
    {
        $order = Mage::getModel('sales/order')->load($params['order_increment_id'], 'increment_id');

        $order->addStatusHistoryComment($this->__('Notification.'))->save();
    }

    /**
     * Create order Transaction
     *
     * @param string $orderId
     * @param array $transactionData
     */
    protected function _addTransaction($orderId, $transactionData)
    {
        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        $paymentId = !empty($transactionData['payment_id']) ? $transactionData['payment_id'] : false;

        if ($order->getId() && $paymentId) {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            $amount = $this->getFormatAmount($transactionData);

            // save transaction details to sales_payment_transaction
            $additionalInfo = array(
                $oystHelper->__('Payment Service Providers') => Oyst_Oyst_Model_Payment_Method_Oyst::PAYMENT_METHOD_NAME,
                $oystHelper->__('Transaction Number') => $transactionData['payment_id'],
                $oystHelper->__('Transaction Type') => 'DEBIT',
                $oystHelper->__('Transaction Status') => $transactionData['event_code'],
                $oystHelper->__('Amount') => $amount . ' ' . $transactionData['amount']['currency'],
            );

            if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_AUTHORISATION == $transactionData['event_code']) {
                $expiryMonth = $expiryYear = null;
                list($expiryMonth, $expiryYear) = explode('/', $transactionData['additional_data']['expiry_date']);

                // save payment infos to sales_flat_order_payment
                $order->getPayment()
                    ->setCcType('CB')
                    ->setCcLast4($transactionData['additional_data']['card_summary'])
                    ->setCcExpMonth($expiryMonth)
                    ->setCcExpYear($expiryYear);

                // save transaction details to sales_payment_transaction
                $additionalInfo[$oystHelper->__('Credit Card No Last 4')] = $transactionData['additional_data']['card_summary'];
                $additionalInfo[$oystHelper->__('Expiration Date')] = $expiryMonth . ' / ' . $expiryYear;
            }


            if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_CAPTURE == $transactionData['event_code']) {
                /** @var Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection $transactions */
                $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection')
                    ->addOrderIdFilter($order->getId())
                    ->addOrderInformation(array('increment_id'))
                    ->addPaymentInformation(array('method'))
                    ->addAttributeToFilter('txn_type', array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH))
                    ->addAttributeToFilter('method', array('eq' => Oyst_Oyst_Model_Payment_Method_Oyst::PAYMENT_METHOD_CODE))
                    ->addAttributeToFilter('is_closed', array('eq' => true));

                foreach ( $transactions as $t) {
                    $payment->setTransactionId($paymentId)->setParentTransactionId($t->getId());
                }
            }

            //set transaction addition information
            $payment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $additionalInfo
            );

            $payment->setTransactionId($paymentId)
                ->setCurrencyCode($transactionData['amount']['currency'])
                ->setPreparedMessage('Success')
                //->setParentTransactionId(false)
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(1)
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $additionalInfo);

            if (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_AUTHORISATION == $transactionData['event_code']) {
                $payment->registerAuthorizationNotification($amount);
            } elseif (Oyst_Oyst_Model_Payment_Method_Oyst::EVENT_CODE_CAPTURE == $transactionData['event_code']) {
                $payment->registerCaptureNotification($amount, true);
            }

            $order->save();
        }
    }

    /**
     * Construct Oyst Secure Url
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        $params = $this->_constructParams();

        /** @var Oyst_Oyst_Model_Payment_ApiWrapper $paymentApiWrapper */
        $paymentApiWrapper = Mage::getModel('oyst_oyst/payment_apiWrapper');
        $response = $paymentApiWrapper->getPaymentUrl($params);

        return $response;
    }

    /**
     * Get url params
     *
     * @return array
     */
    protected function _constructParams()
    {
        $orderIncrementId = Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        $params['order_id'] = $orderIncrementId;
        $params['is_3d'] = (bool) $this->_getConfig('secure_3ds_enable');
        $params['label'] = $this->_getConfig('invoice_label');

        $this->_addAmount($params);
        $this->_addUrls($params, $orderIncrementId);
        $this->_addUserInfos($params);

        return $params;
    }

    /**
     * Add amout to pay as param url
     *
     * @param array Url params
     *
     * @return null
     */
    protected function _addAmount(&$params)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $params['amount'] = array(
            'value' => $checkoutSession->getQuote()->getGrandTotal() * 100,
            'currency' => $checkoutSession->getQuote()->getQuoteCurrencyCode()
        );
    }

    /**
     * Add urls
     *
     * @param array $params
     * @param int $orderId
     *
     * @return null
     */
    protected function _addUrls(&$params, $orderId)
    {
        $notificationUrl = sprintf(
            "%s" . 'order_increment_id' . DS .  "%d",
            Mage::getStoreConfig('oyst/global_settings/notification_url'),
            $orderId
        );
        $cancelUrl = $this->_getConfig('cancel_url') . 'order_increment_id' . DS . $orderId;
        $errorUrl = $this->_getConfig('error_url') . 'order_increment_id' . DS . $orderId;
        $returnUrl = $this->_getConfig('return_url') . 'order_increment_id' . DS . $orderId;

        $params['urls'] = array(
            'notification' => $notificationUrl,
            'cancel' => $cancelUrl,
            'error' => $errorUrl,
            'return' => $returnUrl
        );
    }

    /**
     * Add customer infos
     *
     * @param array $params
     *
     * @return null
     */
    protected function _addUserInfos(&$params)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $customer = $quote->getCustomer();

        if ($quote->getCheckoutMethod() == 'guest') {
            $params['user']['additional_data'] = $this->_getCustomerInfosFromQuote($quote);
            $params['user']['email'] = $quote->getCustomerEmail();
            $params['user']['first_name'] = $quote->getCustomerFirstname();
            $params['user']['last_name'] = $quote->getCustomerLastname();
            $params['user']['phone'] = $quote->getBillingAddress()->getTelephone();
        } else {
            $params['user']['additional_data'] = $customer->getData();
            $params['user']['email'] = $customer->getEmail();
            $params['user']['first_name'] = $customer->getFirstname();
            $params['user']['last_name'] = $customer->getLastname();
            $params['user']['phone'] = ($customer->getPhone()) ?
                $customer->getPhone() : $quote->getBillingAddress()->getTelephone();
        }

        $params['user']['language'] = Mage::app()->getLocale()
            ->getLocale()
            ->getLanguage();
        $params['user']['addresses'][] = $this->_getAddresses($quote->getShippingAddress());
        $params['user']['billing_addresses'][] = $this->_getAddresses($quote->getBillingAddress());
    }

    /**
     * Get customer address datas in array
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return array
     */
    protected function _getAddresses($address)
    {
        $attr = array(
            'city' => 'city',
            'company_name' => 'company',
            'complementary' => 'complementary',
            'country' => 'country_id',
            'first_name' => 'firstname',
            'label' => 'label',
            'last_name' => 'lastname',
            'postcode' => 'postcode',
            'street' => 'street'
        );

        foreach ($attr as $oystAttr => $mageAttr) {
            if ($address->getData($mageAttr)) {
                $param[$oystAttr] = $address->getData($mageAttr);
            } elseif ($oystAttr == 'label') { // label is required even if empty
                $param['label'] = $address->getAddressType();
            }
        }

        return $param;
    }

    /**
     * Get all optional customer information from quote
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function _getCustomerInfosFromQuote($quote)
    {
        $attr = array();
        foreach ($quote->getData() as $key => $data) {
            if (preg_match("/^customer_*/", $key)) {
                $attr[$key] = $data;
            }
        }

        return $attr;
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return string
     */
    protected function _getConfig($code, $paymentMethodCode = null, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = Mage::app()->getStore()->getStoreId();
        }
        if (empty($paymentMethodCode)) {
            return trim(Mage::getStoreConfig("oyst/payment_settings/$code", $storeId));
        }

        return trim(Mage::getStoreConfig("oyst/$paymentMethodCode/$code", $storeId));
    }

    /**
     * Format the amount to get the right syntax
     *
     * @param array $transactionData
     *
     * @return float|int $amount
     */
    private function getFormatAmount($transactionData)
    {
        $amount = !empty($transactionData['amount']) ?
            !empty($transactionData['amount']['value']) ? $transactionData['amount']['value'] : 0 : 0;

        //must transform amount from YYYYY to YYY.YY
        if ($amount > 0) {
            $amount = (float)$amount / 100;
        } else {
            $amount = 0;
        }

        return $amount;
    }
}
