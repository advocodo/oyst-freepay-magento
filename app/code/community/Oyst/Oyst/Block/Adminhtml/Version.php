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
 * Custom renderer for the Oyst section title
 *
 * Adminhtml_Version Block
 */
class Oyst_Oyst_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');

        return (string) $oystHelper->getExtensionVersion();
    }
}
