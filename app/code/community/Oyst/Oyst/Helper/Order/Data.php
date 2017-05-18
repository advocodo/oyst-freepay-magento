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
 * Order_Data Helper
 */
class Oyst_Oyst_Helper_Order_Data extends Mage_Core_Helper_Abstract
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
     * Send order update to Oyst
     *
     * @param array $params
     *
     * @return array $response
     */
    public function updateStatus($params)
    {
        //if status is not defined
        if (!(array_key_exists('status', $params) && $status = $params['status'])) {
            Mage::throwException($this->__("Order status %s is not defined for order update", $status));
        }

        //if order id is not defined
        if (!(array_key_exists('oyst_order_id', $params) && $oystOrderId = $params['oyst_order_id'])) {
            Mage::throwException($this->__("Oyst order id %s is not defined for order update", $oystOrderId));
        }

        $response = Mage::getModel('oyst_oyst/order_apiWrapper')->putOrder($oystOrderId, $status);

        return $response;
    }

    /**
     * Sync order from notification
     *
     * @param array $event
     * @param array $data
     *
     * @return array
     */
    public function syncFromNotification($event, $data)
    {
        $oystOrderId = $data['order_id'];

        //get last notification
        $lastNotification = Mage::getModel('oyst_oyst/notification')->getLastNotification('order', $oystOrderId);

        //if last notification is not finished
        if ($lastNotification->getId() && $lastNotification->getStatus() != 'finished') {
            Mage::throwException($this->__("Last Notification with order id %s is not finished", $oystOrderId));
        }

        //create new notifaction in db with status 'start'
        $notification = Mage::getModel('oyst_oyst/notification');
        $notification->setData(
            array(
                'event' => $event,
                'oyst_data' => Zend_Json::encode($data),
                'status' => 'start',
                'created_at' => Mage::getSingleton('core/date')->gmtDate(),
                'executed_at' => Mage::getSingleton('core/date')->gmtDate()
            )
        );
        $notification->save();

        $params = array(
            'oyst_order_id' => $oystOrderId
        );
        //sync Order From Api
        $result = $this->sync($params);

        //save new status and result in db
        $notification->setStatus('finished')
            ->setOrderId($result['magento_order_id'])
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();

        return array(
            'magento_order_id' => $result['magento_order_id']
        );
    }

    /**
     * Do process of synchronisation
     *
     * @param array $params
     *
     * @return array
     */
    public function sync($params)
    {
        //Retrive order from Api
        $oystOrderId = $params['oyst_order_id'];
        $response = Mage::getModel('oyst_oyst/order_apiWrapper')->getOrder($oystOrderId);

        //save order in Magento
        $order = $this->_importOrder($response);
        $response['magento_order_id'] = $order->getId();

        return $response;
    }

    /**
     * Import order from Oyst to Magento
     *
     * @param array $params
     *
     * @return array
     */
    protected function _importOrder($params)
    {
        //register a 'lock' for not update status to Oyst
        Mage::register('order_status_changing', true);

        //init temporary quote
        $quote = $this->_initQuote($params);

        //init quote address
        $quote = $this->_initAddresses($params, $quote);
        $quote->getPayment()->importData(
            array(
                'method' => 'oyst'
            )
        );

        //init quote customer
        $quote = $this->_initCustomerInfos($params, $quote);

        //transform quote to order
        $order = $this->_submitQuote($params, $quote);

        //change status of order if need to be invoice
        $order = $this->_changeStatus($params, $order);
        Mage::unregister('order_status_changing');

        return $order;
    }

    /**
     * Init temporary cart
     * For the moment only one product by cart/order
     *
     * @param array $params
     *
     * @return array
     */
    protected function _initQuote($params)
    {
        // @codingStandardsIgnoreLine
        // @todo multi products
        $product = Mage::getModel('catalog/product')->load($params['product_id']);
        $quote = Mage::getModel('sales/quote')->setIsSuperMode(true);
        $quote->addProduct($product, $params['quantity']);
        $quote->setOystOrderId($params['id']);

        return $quote;
    }

    /**
     * Init quote addresses
     * Warning : we don't have an order example to know if it will have a customer id
     *
     * @param array $params
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _initAddresses($params, $quote)
    {
        //if customer exist in magento we load it and addresses
        $customer = Mage::getModel('customer/customer');
        if (array_key_exists('user_id', $params) && $userId = $params['user_id']) {
            $customer = $customer->load($userId);
            $defaultShippingAddress = $customer->getDefaultShippingAddress();
            $defaultBillingAddress = $customer->getDefaultBillingAddress();
        } else {
            $defaultShippingAddress = $defaultBillingAddress = Mage::getModel('customer/address');
        }

        //format addresses
        $shippingInfoFormated = $this->_formatAddress($defaultShippingAddress, 'shipping');
        $billingInfoFormated = $this->_formatAddress($defaultBillingAddress, 'billing');

        //add addressess
        $quote->getBillingAddress()->addData($billingInfoFormated);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($shippingInfoFormated);

        //force shipping method to free shipping
        // @codingStandardsIgnoreLine
        // @todo set real shipping method
        $shippingAddress = $shippingAddress->setShippingMethod('freeshipping_freeshipping')
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $shippingAddress->save();

        //save quote
        $quote->save();

        return $quote;
    }

    /**
     * Transform Magento address to formated array
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param string $type
     *
     * @return array
     */
    protected function _formatAddress($address, $type)
    {
        $street = array_filter($address->getStreet());
        $formatedAddress = array(
            'city' => (string) ($address->getCity()) ? $address->getCity() : 'Paris',
            'country_id' => (string) ($address->getCountryId()) ? $address->getCountryId() : 'FR',
            'firstname' => (string) ($address->getFirstname()) ? $address->getFirstname() : 'firstname',
            'lastname' => (string) ($address->getLastname()) ? $address->getLastname() : 'lastname',
            'postcode' => (string) ($address->getPostcode()) ? $address->getPostcode() : '75000',
            'street' => (! empty($street)) ? $address->getStreet() : array(
                0 => 'street'
            ),
            'telephone' => (string) ($address->getTelephone()) ? $address->getTelephone() : 'telephone',
            'region_id' => (string) ($address->getRegionId()) ? $address->getRegionId() : 'region_id',
            'region' => (string) ($address->getRegion()) ? $address->getRegion() : 'Paris',
            'use_for_shipping' => '0',
            'name' => 'freeshipping_freeshipping'
        );

        if ($type == 'shipping') {
            $formatedAddress['shipping_method'] = 'freeshipping_freeshipping';
            $formatedAddress['use_for_shipping'] = '1';
            $formatedAddress['same_as_billing'] = '0';
        }

        return $formatedAddress;
    }

    /**
     * Init Customer
     * Warning : we don't have an order example to know if it will have a customer id
     *
     * @param array $params
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _initCustomerInfos($params, $quote)
    {
        $customer = Mage::getModel('customer/customer');
        if ($userId = $params['user_id']) {
            $customer = $customer->load($userId);
        }

        if (! $customer->getId()) {
            $firstname = '';
            $lastname = '';

            if (!empty($params['user']['firstname'])) {
                $firstname = $params['user']['firstname'];
            }

            if (!empty($params['user']['lastname'])) {
                $lastname = $params['user']['lastname'];
            }

            if (array_key_exists('user', $params) &&
                array_key_exists('user', $params['user']) &&
                !empty($params['user']['email'])) {
                $email = $params['user']['email'];
            } else {
                $email = Mage::getStoreConfig('trans_email/ident_general/email');
            }

            $quote->setCustomerFirstname($firstname);
            $quote->setCustomerLastname($lastname);

            $quote->setCheckoutMethod('guest')
                ->setCustomerId(null)
                ->setCustomerEmail($email)
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
                ->save();
        } else {
            $quote->setCustomerFirstname($customer->getFirstname());
            $quote->setCustomerLastname($customer->getLastname());

            $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER)
                ->setCustomerId($customer->getId())
                ->setCustomerEmail($customer->getEmail())
                ->setCustomerIsGuest(false)
                ->save();
        }

        return $quote;
    }

    /**
     * Transform temporary cart to order
     *
     * @param array $params
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _submitQuote($params, $quote)
    {
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        return $order;
    }

    /**
     * Change Order Status
     *
     * @param array $params
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _changeStatus($params, $order)
    {
        $orderedStatus = array();
        $statuses = $params['status'];

        //order statuses by chronology
        foreach ($statuses as $status) {
            $timeStamp = strtotime($status['date']);
            $statusLabel = $status['status'];
            $orderedStatus[$timeStamp] = $statusLabel;
        }

        //take the last one and change order status
        $lastStatus = array_pop($orderedStatus);
        if ($lastStatus == 'denied' || $lastStatus == 'refunded') {
            $order->cancel();
            $order->save();
        } elseif ($lastStatus == 'accepted') {
            $result = $this->invoice(
                array(
                    'order_increment_id' => $order->getIncrementId()
                )
            );
            $order = $order->load($result['order_id']);
        }

        return $order;
    }

    /**
     * Create Invoice for order
     *
     * @param array $params
     *
     * @return array
     */
    public function invoice($params)
    {
        return Mage::helper('oyst_oyst/payment_data')->invoice($params);
    }

    /**
     * Cancel Order
     *
     * @param array $params
     *
     * @return array
     */
    public function cancel($params)
    {
        return Mage::helper('oyst_oyst/payment_data')->cancel($params);
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     *
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/order_settings/$code");
    }
}
