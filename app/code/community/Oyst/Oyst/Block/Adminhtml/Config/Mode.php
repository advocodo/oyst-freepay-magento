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
 * Mode config
 *
 * Adminhtml_Config_Mode Block
 */
class Oyst_Oyst_Block_Adminhtml_Config_Mode extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html =  parent::_getElementHtml($element);

        $javascript = "
        <script type=\"text/javascript\">
            element = $('".$element->getHtmlId()."');
            Event.observe(element, 'change', function(){
                if(element.selectedOptions[0].value != '".$element->getValue()."'){
                    $('freepay_mode_comment').style.display = 'block';
                } else {
                    $('freepay_mode_comment').style.display = 'none';
                }
            });
        </script>";

        return $html.$javascript;
    }


}
