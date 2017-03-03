<?php
/**
 * 
 * File containing class Oyst_Oyst_Helper_Payment_Data
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
 * @class  Oyst_Oyst_Helper_Payment_Data
 */
class Oyst_Oyst_Helper_Payment_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Object construct
     *
     * @return null
     */
    public function __construct()
    {
        if (! $this->_getConfig('enable')) {
            Mage::throwException($this->__('Order Module is not enabled'));
        }
    }

    /**
     * Sync payment informations from notification
     *
     * @param array $event
     * @param array $data
     * @return array
     */
    public function syncFromNotification($event, $data)
    {
        //get last notification
        $lastNotification = Mage::getModel('oyst_oyst/notification')->getLastNotification('payment', $data['payment_id']);

        //if last notification is not finished
        if ($lastNotification->getId() && $lastNotification->getStatus() != 'finished') {
            Mage::throwException($this->__("Last Notification payment id %s is not finished", $data['payment_id']));
        }

        //create new notifaction in db with status 'start'
        $notification = Mage::getModel('oyst_oyst/notification');
        $notification->setData(array(
            'event' => $event,
            'oyst_data' => Zend_Json::encode($data),
            'status' => 'start',
            'created_at' => Zend_Date::now(),
            'executed_at' => Zend_Date::now()
        ));
        $notification->save();

        //get order INCREMENT id
        if(empty($data['order_increment_id'])) {
            Mage::throwException($this->__("order_id not found for payment id %s", $data['payment_id']));
        }

        $orderIncrementId = $data['order_increment_id'];
        if (!$orderIncrementId) {
            Mage::throwException($this->__('Order Id not found'));
        }

        $params = array(
            'order_increment_id' => $orderIncrementId
        );
        //if data asynchrone payment notification is success, we invoice the order, else we cancel
        if ($data['success']) {
            $result = Mage::helper('oyst_oyst/order_data')->invoice($params);
        } else {
            $result = Mage::helper('oyst_oyst/order_data')->cancel($params);
        }

        //save new status and result in db
        $notification->setStatus('finished')
            ->setOrderId($result['order_id'])
            ->setExecutedAt(Zend_Date::now())
            ->save();

        return array('order_id' => $result['order_id']);
    }

    /**
     * Construct Oyst Secure Url
     * 
     * @return string
     */
    public function getPaymentUrl()
    {
        $params = $this->_constructParams();
        $response = Mage::getModel('oyst_oyst/payment_apiWrapper')->getPaymentUrl($params);
        return $response;
    }

    /**
     * Get url params
     * 
     * @return array
     */
    protected function _constructParams()
    {
        $order_increment_id = Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        $params['notification_url'] = Mage::getStoreConfig('oyst/global_settings/notification_url') . 'order_increment_id' . DS . $order_increment_id;
        $params['is_3d'] = (bool) $this->_getConfig('secure_3ds_enable');
        $params['label'] = $this->_getConfig('invoice_label');

        $this->_addAmount($params);
        $this->_addRedirectsInfos($params, $order_increment_id);
        $this->_addUserInfos($params);

        return $params;
    }

    /**
     * Add amout to pay as param url
     * 
     * @param array Url params
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
     * Add return urls
     * 
     * @param array $params
     * @param Int $order_id
     * @return null
     */
    protected function _addRedirectsInfos(&$params, $order_id)
    {
        $cancelUrl = $this->_getConfig('cancel_url') . 'order_increment_id' . DS . $order_id;
        $errorUrl = $this->_getConfig('error_url') . 'order_increment_id' . DS . $order_id;
        $returnUrl = $this->_getConfig('return_url') . 'order_increment_id' . DS . $order_id;

        $params['redirects'] = array(
            'cancel_url' => $cancelUrl,
            'error_url' => $errorUrl,
            'return_url' => $returnUrl
        );
    }

    /**
     * Add customer infos
     * 
     * @param array $params
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
            $params['user']['phone'] = ($customer->getPhone()) ? $customer->getPhone() : $quote->getBillingAddress()->getTelephone();
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
     * Get all optionalle customer information from quote
     * 
     * @param Mage_Sales_Model_Quote $quote
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
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/payment_settings/$code");
    }
}