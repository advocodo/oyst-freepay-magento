<?php

//must do with 'add attribute' from 'sales module' not '$this => Mage_Core_Model_Resource_Setup'
$installer = new Mage_Sales_Model_Mysql4_Setup();
$installer->startSetup();

//add attribute to order and quote for synchronisation
$installer->addAttribute('order', 'oyst_order_id', array(
    'type' => 'varchar'
));
$installer->addAttribute('quote', 'oyst_order_id', array(
    'type' => 'varchar'
));

$installer->endSetup();
