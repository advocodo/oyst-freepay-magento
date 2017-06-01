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
 * Autoloader Helper
 */
class Oyst_Oyst_Helper_Autoloader
{
    /*
     * Validate the use of autoload
     */
    public static function createAndRegister()
    {
        if (self::_getStoreConfig('oyst/dev/register_autoloader')) {
            $libBaseDir = self::_getStoreConfig('oyst/dev/autoloader_basepath');
            if ($libBaseDir[0] !== '/') {
                $libBaseDir = Mage::getBaseDir() . DS . $libBaseDir;
            }

            self::loadComposerAutoLoad($libBaseDir);
        }
    }

    /**
     * Load Composer autoload from lib oyst vendor folder
     *
     * @param $libBaseDir   Path of the Magento lib folder
     */
    public static function loadComposerAutoLoad($libBaseDir)
    {
        static $registered = false;
        if (!$registered) {
            // @codingStandardsIgnoreLine
            require_once $libBaseDir . DS . 'vendor' . DS . 'autoload.php';
            $registered = true;
        }
    }

    /**
     * Load store config first in case we are in update mode, where store config would not be available
     *
     * @param $path
     *
     * @return bool
     */
    protected static function _getStoreConfig($path)
    {
        static $configLoaded = false;
        if (! $configLoaded && Mage::app()->getUpdateMode()) {
            Mage::getConfig()->loadDb();
            $configLoaded = true;
        }

        return Mage::getStoreConfig($path);
    }
}
