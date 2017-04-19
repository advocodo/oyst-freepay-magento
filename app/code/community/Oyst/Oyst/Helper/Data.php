<?php
/**
 *
 * File containing class Oyst_Oyst_Helper_Data
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
 * @class  Oyst_Oyst_Helper_Data
 */
class Oyst_Oyst_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Globale function for log if enabled
     *
     * @param string $message
     * @return null
     */
    public function log($message)
    {
        if (Mage::getStoreConfig('oyst/global_settings/log_enable')) {
            Mage::log($message, null, 'oyst.log', true);
        }
    }
}
