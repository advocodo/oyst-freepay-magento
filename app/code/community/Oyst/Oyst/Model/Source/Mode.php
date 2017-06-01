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

use Oyst\Api\OystApiClientFactory;

/**
 * API Modes
 */
class Oyst_Oyst_Model_Source_Mode
{
    const CUSTOM = 'custom';
    const PREPROD = OystApiClientFactory::ENV_PREPROD;
    const PROD = OystApiClientFactory::ENV_PROD;

    /**
     * Return the options for mode.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::PREPROD, 'label' => Mage::helper('oyst_oyst')->__(self::PREPROD)),
            array('value' => self::PROD, 'label' => Mage::helper('oyst_oyst')->__(self::PROD)),
            array('value' => self::CUSTOM, 'label' => Mage::helper('oyst_oyst')->__(self::CUSTOM)),
        );
    }
}
