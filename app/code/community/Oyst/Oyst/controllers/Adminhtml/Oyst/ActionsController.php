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
 * Oyst_Actions Controller
 */
class Oyst_Oyst_Adminhtml_Oyst_ActionsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test if user can access to this sections
     *
     * @return bool
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oyst/actions');
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
            ->_setActiveMenu('oyst/oyst_actions')
            ->_title($oystHelper->__('Actions'))
            ->_addBreadcrumb($oystHelper->__('Actions'), $oystHelper->__('Actions'));

        return $this;
    }

    /**
     * Print action page
     *
     * @retun null
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_Oyst_Helper_Data $helper */
        $helper = Mage::helper('oyst_oyst');
        $helper->setIsInitialized();
        $this->_redirectReferer();
    }
}
