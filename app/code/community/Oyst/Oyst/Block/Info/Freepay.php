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
 * Info Freepay block manage info in order
 * Oyst_Oyst_Block_Info Block
 */
class Oyst_Oyst_Block_Info_Freepay extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('oyst/info_freepay.phtml');
    }

    /**
     * Prepare specific information for the payment block in order
     *
     * @param null $transport
     *
     * @return null|Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $info = $this->getInfo();

        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(
            array(
                Mage::helper('oyst_oyst')->__('Transaction Number') => $info->getLastTransId(),
                Mage::helper('oyst_oyst')->__('Payment Mean') => $info->getCcType(),
                Mage::helper('oyst_oyst')->__('Credit Card No Last 4') => $info->getCcLast4(),
                Mage::helper('oyst_oyst')->__('Expiration Date') => $info->getCcExpMonth() . ' / ' . $info->getCcExpYear(),
            )
        );
        return $transport;
    }
}