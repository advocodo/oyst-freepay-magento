<?php
/**
 *
 * File containing class Oyst_Oyst_Model_Notification
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
 * @class  Oyst_Oyst_Model_Notification
 */
class Oyst_Oyst_Model_Notification extends Mage_Core_Model_Abstract
{

    /**
     * Object construct
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_init('oyst_oyst/notification');
    }

    /**
     * Get last notification filter by type AND/OR id
     *
     * @param string $type
     * @param Int $dataId
     * @return Oyst_Oyst_Model_Notification
     */
    public function getLastNotification($type = false, $dataId = false)
    {
        $collection = $this->getCollection()->addDataIdToFilter($type, $dataId);
        return $collection->getFirstItem();
    }
}
