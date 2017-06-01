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
 * FreePay install notification
 *
 * Adminhtml_Notifications Block
 */
class Oyst_Oyst_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    /**
     * Disable the block caching for this block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData(array('cache_lifetime' => null));
    }

    /**
     * Returns a value that indicates if some of the FreePay settings have already been initialized.
     *
     * @return bool Flag if FreePay is already initialized
     */
    public function isInitialized()
    {
        return Mage::getStoreConfigFlag('oyst/freepay/is_initialized');
    }

    /**
     * Get FreePay management url
     *
     * @return string URL for FreePay form in payment method
     */
    public function getManageUrl()
    {
        return
            //Mage::helper("adminhtml")->getUrl('system_config/edit/section/payment');
            $this->getUrl('adminhtml/system_config/edit/section/payment');
    }

    /**
     * Get FreePay installation skip action
     *
     * @return string URL for skip action
     */
    public function getSkipUrl()
    {
        return $this->getUrl('adminhtml/oyst_actions/skip');
    }

    /**
     * ACL validation before html generation
     *
     * @return string Notification content
     */
    protected function _toHtml()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('system/oyst_freepay')) {
            return parent::_toHtml();
        }

        return '';
    }
}
