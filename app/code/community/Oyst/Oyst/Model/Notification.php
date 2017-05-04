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
 * Notification Model
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
        $collection = $this->getCollection()
            ->addDataIdToFilter($type, $dataId)
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        // @codingStandardsIgnoreLine
        return $collection->getFirstItem();
    }
}
