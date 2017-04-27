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
 * Custom renderer for the Oyst init button
 * @package Oyst_Oyst
 */
class Oyst_Oyst_Block_Adminhtml_Field_InitButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('oyst/field/init_button.phtml');

        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $fieldConfig = $element->getFieldConfig();
        $this->addData(array(
                'button_label' => Mage::helper('oyst_oyst')->__((string)$fieldConfig->button_label),
                'button_url'   => $this->getUrl($fieldConfig->button_url, array('_secure' => true)),
                'html_id' => $element->getHtmlId()
        ));

        return $this->_toHtml();
    }
}
