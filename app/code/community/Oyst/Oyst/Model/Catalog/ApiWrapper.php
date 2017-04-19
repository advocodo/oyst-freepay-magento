<?php
/**
 *
 * File containing class Oyst_Oyst_Model_Catalog_ApiWrapper
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
 * @class  Oyst_Oyst_Model_Catalog_ApiWrapper
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
