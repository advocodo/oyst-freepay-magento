<?php
/**
 *
 * File containing class Oyst_Oyst_Adminhtml_Oyst_ActionsController
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
 * @class  Oyst_Oyst_Adminhtml_Oyst_ActionsController
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
        $this->loadLayout()
            ->_setActiveMenu('oyst/oyst_actions')
            ->_title(Mage::helper('oyst_oyst')->__('Actions'))
            ->_addBreadcrumb(Mage::helper('oyst_oyst')->__('Actions'), Mage::helper('oyst_oyst')->__('Actions'));
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
}
