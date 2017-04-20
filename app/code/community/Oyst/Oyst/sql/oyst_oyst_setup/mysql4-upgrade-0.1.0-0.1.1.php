<?php

$installer = $this;
$installer->startSetup();

//must do with 'add attribute' from 'sales module' not '$this => Mage_Core_Model_Resource_Setup'
$sales = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
$sales->startSetup();

//add attribute to order and quote for synchronisation
$sales->addAttribute('order', 'oyst_order_id', array(
    'type' => 'varchar'
));
$sales->addAttribute('quote', 'oyst_order_id', array(
    'type' => 'varchar'
));

$installer->endSetup();
