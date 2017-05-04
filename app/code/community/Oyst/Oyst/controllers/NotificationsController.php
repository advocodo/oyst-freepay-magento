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
 * Notifications Controller
 */
class Oyst_Oyst_NotificationsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get notification for catalog, order and payment callback
     *
     * @return null
     */
    public function indexAction()
    {
        $event = $this->getRequest()->getPost('event');
        $data = $this->getRequest()->getPost('data');
        // @codingStandardsIgnoreLine
        $post = (array) Zend_Json::decode(str_replace("\n", '', file_get_contents('php://input')));
        //set the type and data from notification url
        if (empty($event) && empty($data) && !empty($post)) {
            if (array_key_exists('event', $post)) {
                $event = $post['event'];
            }

            if (array_key_exists('data', $post)) {
                $data = (array) $post['data'];
            }

            if (array_key_exists('notification', $post)) {
                $event = 'payment';
                $data = (array) $post['notification'];
                $data['order_increment_id'] = Mage::app()->getRequest()->getParam('order_increment_id');
            }
        }

        if (empty($post) || empty($data) || empty($event)) {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.1', '400 Bad Request')
                ->sendResponse();

            return $this;
        }
        if ($event == 'products.import') {
            $helperName = 'oyst_oyst/catalog_data';
        } elseif ($event == 'notification.newOrder') {
            $helperName = 'oyst_oyst/order_data';
        } elseif ($event == 'payment') {
            $helperName = 'oyst_oyst/payment_data';
        }

        $helper = Mage::helper($helperName);
        if (!$helper) {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.1', '400 Bad Request')
                ->sendResponse();

            return $this;
        }
        $result = $helper->syncFromNotification($event, $data);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
}
