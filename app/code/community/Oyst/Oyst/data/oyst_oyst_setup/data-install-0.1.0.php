<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Pattern to get url. Sometime url like xxxxx/downloader. we can't use baseurl
// Warning : url type http(s)://user:password@test.com/ not autorize.
$pattern = '/(http|ftp|https):\/\/([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])?/';
$count = preg_match($pattern, Mage::getBaseUrl(), $params);

// if result, we set default config in core_config_data, else, user must set manualy in backoffice
if ($count) {
    // http || https
    $mode = $params[1];
    $domain = $params[2];
    $url = $mode . '://' . $domain . '/';
    Mage::getConfig()->saveConfig('oyst/payment_settings/cancel_url', $url . 'oyst/payment/cancel/');
    Mage::getConfig()->saveConfig('oyst/payment_settings/error_url', $url . 'oyst/payment/error/');
    Mage::getConfig()->saveConfig('oyst/payment_settings/return_url', $url . 'oyst/payment/return/');
    Mage::getConfig()->saveConfig('oyst/global_settings/notification_url', $url . 'oyst/notifications/index/');
}

$installer->endSetup();
