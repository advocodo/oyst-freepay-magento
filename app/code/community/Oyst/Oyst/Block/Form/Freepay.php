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
 * Form Freepay block manage info in checkout
 * Oyst_Oyst_Block_Form_Freepay Block
 */
class Oyst_Oyst_Block_Form_Freepay extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('oyst/form/freepay.phtml');
    }

    public function getPaymentMethodLabel()
    {
        return Oyst_Oyst_Model_Payment_Method_Freepay::PAYMENT_METHOD_NAME;
    }
}
