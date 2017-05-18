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
 * Cancel Modes
 */
class Oyst_Oyst_Model_Source_CancelModes
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Sales_Model_Order::STATE_HOLDED,
                'label' => Mage::helper('sales')->__('On Hold')
            ),
            array(
                'value' => Mage_Sales_Model_Order::STATE_CANCELED,
                'label' => Mage::helper('sales')->__('Canceled')
            ),
        );
    }
}
