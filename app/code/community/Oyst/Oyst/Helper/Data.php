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
 * Data Helper
 */
class Oyst_Oyst_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Global function for log if enabled
     *
     * @param string $message
     *
     * @return null
     */
    public function log($message)
    {
        if (Mage::getStoreConfig('oyst/global_settings/log_enable')) {
            Mage::log($message, null, 'oyst.log', true);
        }
    }
}
