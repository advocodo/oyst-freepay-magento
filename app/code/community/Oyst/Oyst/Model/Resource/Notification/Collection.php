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
 * Resource_Notification_Collection Model
 */
class Oyst_Oyst_Model_Resource_Notification_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
     * Add type filter to notifications list in db
     *
     * @param string $type
     * @param Int $dataId
     * @return Oyst_Oyst_Model_Resource_Notification_Collection
     */
    public function addDataIdToFilter($type, $dataId)
    {
        if ($dataId) {
            if ($type == 'catalog') {
                $this->addFieldToFilter(
                    'oyst_data',
                    array(
                        'like' => '%import_id":"' . $dataId . '"%'
                    )
                );
            } elseif ($type == 'order') {
                $this->addFieldToFilter(
                    'oyst_data',
                    array(
                        'like' => '%order_id":"' . $dataId . '"%'
                    )
                );
            } elseif ($type == 'payment') {
                $this->addFieldToFilter(
                    'oyst_data',
                    array(
                        'like' => '%payment_id":"' . $dataId . '"%'
                    )
                );
            }
        }

        $this->setOrder('notification_id');

        return $this;
    }
}
