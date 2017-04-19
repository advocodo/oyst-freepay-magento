<?php
/**
 *
 * File containing class Oyst_Oyst_Model_Resource_Notification
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
 * @class  Oyst_Oyst_Model_Resource_Notification
 */
class Oyst_Oyst_Model_Resource_Notification extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Object construct
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_init('oyst_oyst/notification', 'notification_id');
    }
}
