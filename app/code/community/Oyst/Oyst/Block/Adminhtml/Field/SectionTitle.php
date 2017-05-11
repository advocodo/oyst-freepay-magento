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
 * Adminhtml_Field_SectionTitle Block
 */
class Oyst_Oyst_Block_Adminhtml_Field_SectionTitle extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div style="background-color: #D1DEDF; border: 1px solid #849BA3; height: 26px; padding-top: 5px;">';
        $html .= '<p style="font-size: 11px; margin: 0; padding-left: 20px;"><b>' . $element->getLabel() . '</b></p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<tr style="height: 13px;"><td colspan="4"></td></tr>';
        $html .= '<tr>';
        $html .= '<td colspan="4">';
        $html .= $this->_getElementHtml($element);

        if ($element->getComment()) {
            $html .= '<p class="note" style="margin-left: 20px; margin-bottom: 15px;">';
            $html .= '<span>' . $element->getComment() . '</span>';
            $html .= '</p>';
        }

        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }
}
