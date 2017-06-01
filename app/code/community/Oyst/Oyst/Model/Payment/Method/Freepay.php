<?php
/**
 * This file is part of Oyst_Oyst for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <dev@oyst.com> <@oystcompany>
 * @category Oyst
 * @package Oyst_Oyst
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Payment_Method_Oyst Model
 */
class Oyst_Oyst_Model_Payment_Method_Freepay extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_METHOD_NAME = 'Oyst Freepay';

    const EVENT_CODE_AUTHORISATION = 'AUTHORISATION';
    const EVENT_CODE_CAPTURE = 'CAPTURE';
    const EVENT_CODE_CANCELLATION = 'CANCELLATION';
    const EVENT_CODE_REFUND = 'REFUND';

    protected $_code = 'oyst_freepay';
    protected $_formBlockType = 'oyst_oyst/form_freepay';
    protected $_infoBlockType = 'oyst_oyst/info_freepay';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;

    /**
     * Native method for retrieve Oyst Form Redirect Url
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = Mage::helper('oyst_oyst/payment_data')->getPaymentUrl();

        return $url;
    }
}
