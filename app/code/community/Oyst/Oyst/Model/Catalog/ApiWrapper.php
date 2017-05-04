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
 * Catalog_ApiWrapper Model
 */
class Oyst_Oyst_Model_Catalog_ApiWrapper extends Mage_Core_Model_Abstract
{
    /**
     * Send Product in post or put to api
     *
     * @param array $productsFormated
     * @param array $params
     * @return array
     */
    public function sendProduct($productsFormated, $params)
    {
        //get params for update product or new product
        if (array_key_exists('action', $params) && $params['action'] == 'update') {
            $method = Oyst_Oyst_Model_Api::TYPE_PUTCATALOG;
            $productsFormated = $productsFormated['products'];
        } else {
            $method = Oyst_Oyst_Model_Api::TYPE_POSTCATALOG;
            foreach ($productsFormated['products'] as $index => $product) {
                if (!empty($product['id'])) {
                    unset($productsFormated['products'][$index]['id']);
                }
            }
        }

        //API send
        $response = Mage::getModel('oyst_oyst/api')->send($method, $productsFormated);

        return $response;
    }
}
