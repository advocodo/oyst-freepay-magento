<?php
/**
 * 
 * File containing class Oyst_Oyst_Model_Api
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
 * @class  Oyst_Oyst_Model_Api
 */
class Oyst_Oyst_Model_Api extends Mage_Core_Model_Abstract
{
    /**
     * Api type of call
     * @var string
     */
    const TYPE_POSTCATALOG = '_postcatalog';

    /**
     * Api type of call
     * @var string
     */
    const TYPE_PUTCATALOG = '_putcatalog';

    /**
     * Api type of call
     * @var string
     */
    const TYPE_GETORDER = '_getorder';

    /**
     * Api type of call
     * @var string
     */
    const TYPE_PUTORDER = '_putorder';

    /**
     * Api type of call
     * @var string
     */
    const TYPE_PAYMENT = '_payment';

    /**
     * Api call to Oyst 
     * 
     * @param string $type
     * @param array $dataFormated
     * @return array
     */
    public function send($type, $dataFormated)
    {
        //if api type don't have method associate
        if (!method_exists($this, $type)) {
            Mage::helper('oyst_oyst')->log('Something wrong with Oyst api : ' . $type);
            Mage::throwException($this->__('Something wrong with Oyst api : ' . $type));
        }

        //get api service url from config
        $targetUrl = $this->_getConfig('api_url');

        //get api key from config
        $apiKey = $this->_getConfig('api_login');

        //if type is order, we must retrieve the param 'oyst_order_id' from $dataFormated but not send it
        if ($type == Oyst_Oyst_Model_Api::TYPE_PUTORDER) {
            $targetUrl .= 'order' . DS . 'orders' . DS . $dataFormated['oyst_order_id'];
            Mage::helper('oyst_oyst')->log('Oyst api order id : ' . $dataFormated['oyst_order_id']);
            unset($dataFormated['oyst_order_id']);
        }

        $dataJson = Zend_Json::encode($dataFormated);

        //init curl params according to method
        $ch = curl_init();

        //set curl opt, construct final $targetUrl and build Headers
        $headers = $this->$type($ch, $targetUrl, $apiKey, $dataJson);

        //set common curl opt
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_HEADER, true);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        Mage::helper('oyst_oyst')->log($dataJson);

        //analyse API response
        $result_array = array();
        if ($res === false || $info['http_code'] != '200') {
            Mage::helper('oyst_oyst')->log('Curl error target: ' . curl_error($ch));
            curl_close($ch);
            Mage::throwException($this->__('Curl error target: ' . curl_error($ch)));
        } else {
            $result_array = Zend_Json::decode($res);
        }

        curl_close($ch);
        return $result_array;
    }

    /**
     * set curl opt, construct final $targetUrl and build Headers for POST catalog
     * 
     * @param Resource $ch
     * @param string $targetUrl
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _postcatalog(&$ch, &$targetUrl, $apiKey, $dataJson) {
        $targetUrl .= 'catalog' . DS . 'products';
        $this->_initCh($ch, 'POST', $dataJson);
        return $this->_getHeaders($apiKey, $dataJson);
    }

    /**
     * set curl opt, construct final $targetUrl and build Headers for PUT catalog
     *
     * @param Resource $ch
     * @param string $targetUrl
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _putcatalog(&$ch, &$targetUrl, $apiKey, $dataJson) {
        $targetUrl .= 'catalog' . DS . 'products';
        $this->_initCh($ch, 'PUT', $dataJson);
        return $this->_getHeaders($apiKey, $dataJson);
    }

    /**
     * set curl opt, construct final $targetUrl and build Headers for GET order
     *
     * @param Resource $ch
     * @param string $targetUrl
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _getorder(&$ch, &$targetUrl, $apiKey, $dataJson = false) {
        $targetUrl .= 'order' . DS . 'orders';
        $targetUrl .= ($dataJson) ? DS . $dataJson : '';
        $this->_initCh($ch, 'GET');
        return $this->_getHeaders($apiKey);
    }

    /**
     * set curl opt, construct final $targetUrl and build Headers for PUT order
     *
     * @param Resource $ch
     * @param string $targetUrl
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _putorder(&$ch, &$targetUrl, $apiKey, $dataJson) {
        $this->_initCh($ch, 'PUT', $dataJson);
        return $this->_getHeaders($apiKey, $dataJson);
    }

    /**
     * set curl opt, construct final $targetUrl and build Headers for POST payment
     *
     * @param Resource $ch
     * @param string $targetUrl
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _payment(&$ch, &$targetUrl, $apiKey, $dataJson) {
        $targetUrl .= 'payment' . DS . 'payments';
        $this->_initCh($ch, 'POST', $dataJson);
        return $this->_getHeaders($apiKey, $dataJson);
    }

    /**
     * Init 2 curl opt according to type
     * 
     * @param Resource $ch
     * @param string $type
     * @param string $dataJson
     */
    protected function _initCh(&$ch, $type, $dataJson = false) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if ($dataJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        }
    }

    /**
     * Return Headers for curl request
     * 
     * @param string $apiKey
     * @param string $dataJson
     * @return array
     */
    protected function _getHeaders($apiKey, $dataJson = false) {
        $headers = array(
            'Authorization: Bearer ' . $apiKey . '',
            'Content-Type: application/json',
        );

        if ($dataJson) {
            $headers[] = 'Content-Length: ' . strlen($dataJson);
        }

        return $headers;
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/global_settings/$code");
    }
}
