<?php

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Pattern to get url. Sometime url like xxxxx/downloader. we can't use baseurl
// Warning : url type http(s)://user:password@test.com/ not autorize.
$pattern = '/(http|ftp|https):\/\/([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])?/';
$url = Mage::getBaseUrl();
$count = preg_match($pattern, $url, $params);

// if result, we set default config in core_config_data, else, user must set manualy in backoffice
if ($count) {
    Mage::getConfig()->saveConfig('payment/oyst_abstract/cancel_url', $url . 'oyst/payment/cancel/');
    Mage::getConfig()->saveConfig('payment/oyst_abstract/error_url', $url . 'oyst/payment/error/');
    Mage::getConfig()->saveConfig('payment/oyst_abstract/return_url', $url . 'oyst/payment/return/');
    Mage::getConfig()->saveConfig('payment/oyst_abstract/notification_url', $url . 'oyst/notifications/index/');
}

$installer->endSetup();
