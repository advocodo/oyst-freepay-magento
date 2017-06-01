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
 * Oyst_Catalog Controller
 */
class Oyst_Oyst_Adminhtml_Oyst_CatalogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oyst/catalog');
    }

    /**
     * Magento method for init layout, menu and breadcrumbs
     *
     * @return Oyst_Oyst_Adminhtml_Oyst_ActionsController
     */
    protected function _initAction()
    {
        $this->_activeMenu();

        return $this;
    }

    /**
     * Active menu
     *
     * @return Oyst_Oyst_Adminhtml_Oyst_ActionsController
     */
    protected function _activeMenu()
    {
        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');

        $this->loadLayout()
            ->_setActiveMenu('oyst/oyst_catalog')
            ->_title($oystHelper->__('Catalog'))
            ->_addBreadcrumb($oystHelper->__('Catalog'), $oystHelper->__('Catalog'));

        return $this;
    }

    /**
     * Synchronize product from Magento to Oyst
     *
     * @return Oyst_Oyst_Adminhtml_Oyst_CatalogController
     */
    public function syncAction()
    {
        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');

        //get list of product
        $product = Mage::app()->getRequest()->getParam('product');
        $params = array('product_id_include_filter' => $product);
        $oystHelper->log('Start of sending product id : ' . var_export($product, true));

        //sync product to Oyst
        $result = Mage::helper('oyst_oyst/catalog_data')->sync($params);
        $oystHelper->log('End of sending product id : ' . var_export($product, true));

        //if api response is success
        if ($result && array_key_exists('success', $result) && $result['success'] == true) {
            $this->_getSession()->addSuccess($oystHelper->__('The sync was successfully done'));
        } else {
            $this->_getSession()->addError($oystHelper->__('An error was occured'));
        }

        $this->getResponse()->setRedirect($this->getRequest()->getServer('HTTP_REFERER'));

        return $this;
    }
}
