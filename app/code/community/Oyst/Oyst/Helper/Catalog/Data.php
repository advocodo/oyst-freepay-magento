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
 * Catalog_Data Helper
 */
class Oyst_Oyst_Helper_Catalog_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Translate Product attribute for Oyst <-> Magento
     *
     * @var array
     */
    protected $_productAttrTranslate = array(
        'id' => array(
            'name' => 'reference',
            'type' => 'string',
            'required' => true
        ),
        'entity_id' => array(
            'name' => 'reference',
            'type' => 'string',
            'required' => true
        ),
        'sku' => array(
            'name' => 'merchant_reference',
            'type' => 'string',
            'required' => true
        ),
        'status' => array(
            'name' => 'is_active',
            'type' => 'bool',
            'required' => true
        ),
        'name' => array(
            'name' => 'title',
            'type' => 'string',
            'required' => true
        ),
        'short_description' => array(
            'name' => 'short_description',
            'type' => 'string',
            'required' => true
        ),
        'description' => array(
            'name' => 'description',
            'type' => 'string',
            'required' => true
        ),
        'meta_title' => array(
            'name' => 'meta',
            'type' => 'jsonb',
            'required' => true
        ),
        'qty' => array(
            'name' => 'available_quantity',
            'type' => 'int',
            'required' => true
        ),
        'min_sale_qty' => array(
            'name' => 'minimum_orderable_quantity',
            'type' => 'int',
            'required' => true
        )
    );

    /**
     * Translate Sku attribute for Oyst <-> Magento
     *
     * @var array
     */
    protected $_skusAttrTranslate = array(
        'id' => array(
            'name' => 'reference',
            'type' => 'string',
            'required' => true
        ),
        'entity_id' => array(
            'name' => 'reference',
            'type' => 'string',
            'required' => true
        ),
        'name' => array(
            'name' => 'title',
            'type' => 'string',
            'required' => true
        ),
        'description' => array(
            'name' => 'description',
            'type' => 'string',
            'required' => true
        ),
        'qty' => array(
            'name' => 'available_quantity',
            'type' => 'int',
            'required' => true
        ),
        'weight' => array(
            'name' => 'weight',
            'type' => 'string',
            'required' => true
        ),
        'min_sale_qty' => array(
            'name' => 'minimum_orderable_quantity',
            'type' => 'int',
            'required' => true
        )
    );

    /**
     * Object construct
     *
     * @return null
     */
    public function __construct()
    {
        if (! $this->_getConfig('enable')) {
            Mage::throwException($this->__('Catalog Module is not enabled'));
        }
    }

    /**
     * Synchronisation process from notification controller
     *
     * @param array $event
     * @param array $data
     * @return number
     */
    public function syncFromNotification($event, $data)
    {
        //get last notification
        $lastNotification = Mage::getModel('oyst_oyst/notification')
            ->getLastNotification('catalog', $data['import_id']);

        //if last notification is not finished
        if ($lastNotification->getId() && $lastNotification->getStatus() != 'finished') {
            Mage::throwException($this->__("Last Notification id %s is not finished", $data['import_id']));
        }

        //if last notification finish but with same id
        if ($lastNotification->getId() && $lastNotification->getImportRemaining() <= 0) {
            $response['total_count'] = Mage::getModel('catalog/product')->getCollection()->getSize();
            $response['import_id'] = $data['import_id'];
            $response['remaining'] = 0;
            return $response;
        }

        //set param 'num_per_page'
        if ($numberPerPack = $this->_getConfig('number_per_pack')) {
            $params['num_per_page'] = $numberPerPack;
        }

        //set param 'import_id'
        $params['import_id'] = $data['import_id'];

        //get last notification with this id and have remaining
        $notificationCollection = Mage::getModel('oyst_oyst/notification')
            ->getCollection()
            ->addDataIdToFilter('catalog', $data['import_id']);
        $excludedProductsId = array();
        //set id to exclude
        foreach ($notificationCollection as $pastNotification) {
            if ($productsId = Zend_Json::decode($pastNotification->getProductsId())) {
                $excludedProductsId = array_merge($excludedProductsId, $productsId);
            }
        }

        $params['product_id_exclude_filter'] = $excludedProductsId;

        //create new notifaction in db with status 'start'
        $notification = Mage::getModel('oyst_oyst/notification');
        $notification->setData(
            array(
                'event' => $event,
                'oyst_data' => Zend_Json::encode($data),
                'status' => 'start',
                'created_at' => Mage::getSingleton('core/date')->gmtDate(),
                'executed_at' => Mage::getSingleton('core/date')->gmtDate()
            )
        );
        $notification->save();

        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');
        $oystHelper->log('Start of import id : ' . $data['import_id']);

        //synchronize with Oyst
        $result = $this->sync($params);

        //set param for db
        $response['import_id'] = $data['import_id'];
        $response['total_count'] = Mage::getModel('catalog/product')->getCollection()->getSize();
        $done = $response['total_count'] - count($excludedProductsId) - count($result['imported_product_ids']);
        $response['remaining'] = ($done <= 0) ? 0 : $done;

        //save new status and result in db
        $notification->setStatus('finished')
            ->setOystData(Zend_Json::encode($data))
            ->setProductsId(Zend_Json::encode($result['imported_product_ids']))
            ->setImportRemaining($response['remaining'])
            ->setExecutedAt(Mage::getSingleton('core/date')->gmtDate())
            ->save();
        $oystHelper->log('End of import id : ' . $data['import_id']);

        return $response;
    }

    /**
     * Synchronisation process
     *
     * @param array $params
     * @return array
     */
    public function sync($params = array())
    {
        /** @var Oyst_Oyst_Helper_Data $oystHelper */
        $oystHelper = Mage::helper('oyst_oyst');

        //get list of product from params
        $collection = $this->_prepareCollection($params);
        $oystHelper->log('Product Collection Sql : ' . $collection->getSelect()->__toString());

        //format list into array
        $productsFormated = $this->_format($collection);

        //get products ids
        $importedProductIds = $productsFormated['imported_product_ids'];
        unset($productsFormated['imported_product_ids']);

        //sync api
        $response = Mage::getModel('oyst_oyst/catalog_apiWrapper')->sendProduct($productsFormated, $params);
        $response['imported_product_ids'] = $importedProductIds;

        return $response;
    }

    /**
     * Prepare Database Request with filters
     *
     * @param array $params
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _prepareCollection($params)
    {
        //construct param for list in db request
        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
        if (! empty($params) && is_array($params)) {
            if (!empty($params["product_id_include_filter"])) {
                $collection->addAttributeToFilter(
                    'entity_id',
                    array(
                        'in' => $params['product_id_include_filter']
                    )
                );
            }

            if (!empty($params["product_id_exclude_filter"])) {
                $collection->addAttributeToFilter(
                    'entity_id',
                    array(
                        'nin' => $params['product_id_exclude_filter']
                    )
                );
            }

            if (!empty($params['num_per_page'])) {
                $collection->setPage(0, $params['num_per_page']);
            }
        }

        // do not use : that include 'catalog_product_index_price' with inner join
        // but this table is used only if product is active once.
        // we want ALL products so let's join manally
        // Mage::getSingleton('cataloginventory/stock')->addItemsToProducts($collection);
        // $collection->addPriceData();
        $collection->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->joinField('backorders', 'cataloginventory/stock_item', 'backorders', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->joinField('min_sale_qty', 'cataloginventory/stock_item', 'min_sale_qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
        $collection->getSelect()->order('FIELD(type_id, "configurable", "grouped", "simple", "downloadable", "virtual", "bundle")');

        return $collection;
    }

    /**
     * Transform Database Datas to formatd array
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $products
     * @return array
     */
    protected function _format($products)
    {
        $importedProductIds = $productsFormated = array();
        foreach ($products as $product) {
            if (in_array($product->getId(), $importedProductIds)) {
                continue;
            }

            //get product attributes
            $attributes = $this->_getAttributes($product, $this->_productAttrTranslate);
            $importedProductIds[] = $product->getId();
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                //get sku attributes
                $productIds = $this->_addSku($product, $attributes);
                $importedProductIds = array_merge($importedProductIds, $productIds);
            }

            //add others attributes
            $this->_addComplexAttributes($product, $attributes);
            $this->_addCategories($product, $attributes);
            $this->_addShipments($product, $attributes);
            $this->_addImages($product, $attributes);
            $this->_addRelatedProducts($product, $attributes);

            $productsFormated['products'][] = $attributes;
        }

        $productsFormated['imported_product_ids'] = $importedProductIds;

        return $productsFormated;
    }

    /**
     * Get product attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $translateAttribute $this->_skusAttrTranslate || $this->_productAttrTranslate
     * @return array
     */
    protected function _getAttributes($product, $translateAttribute)
    {
        $attributes = array();
        foreach ($translateAttribute as $attributeCode => $simpleAttribute) {
            if ($data = $product->getData($attributeCode)) {
                if ($simpleAttribute['type'] == 'jsonb') {
                    $data = Zend_Json::encode(
                        array(
                            'meta' => $data
                        )
                    );
                } else {
                    settype($data, $simpleAttribute['type']);
                }

                if ($data !== null) {
                    $attributes[$simpleAttribute['name']] = $data;
                }
            } elseif (array_key_exists('required', $simpleAttribute) && $simpleAttribute['required'] == true) {
                if ($simpleAttribute['type'] == 'jsonb') {
                    $data = '{}';
                } else {
                    $data = 'Empty';
                    settype($data, $simpleAttribute['type']);
                }

                $attributes[$simpleAttribute['name']] = $data;
            }
        }

        return $attributes;
    }

    /**
     * add sku attributes to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return array list of sku's ids
     */
    protected function _addSku($product, &$attributes)
    {
        $productIds = array();
        $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);

        foreach ($childProducts as $simpleProduct) {
            $simpleAttributes = $this->_getAttributes($simpleProduct, $this->_skusAttrTranslate);
            $this->_addImages($simpleProduct, $simpleAttributes);

            $attributes['skus'][] = $simpleAttributes;
            $productIds[] = $simpleProduct->getId();
        }

        return $productIds;
    }

    /**
     * Add complex attribute to product array
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return null
     */
    protected function _addComplexAttributes($product, &$attributes)
    {
        $attributes['id'] = '00000000-0000-0000-0000-000000000000';
        $attributes['url'] = $product->getUrlModel()->getProductUrl($product);
        $attributes['is_materialized'] = ($product->isVirtual()) ? true : false;
        $attributes['condition'] = 'new';
        $attributes['is_orderable_outstock'] = ($product->getBackorders() == 1) ? true : false;
    }

    /**
     * add categories to product array
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return null
     */
    protected function _addCategories($product, &$attributes)
    {
        $indexStore = 0;
        foreach (Mage::app()->getStores() as $store) {
            $index = 0;
            $categoryCollection = $product->getCategoryCollection()
                ->setStoreId($store->getId())
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key');
            foreach ($categoryCollection as $category) {
                $attributes['categories'][$index]['titles'][$indexStore]['name'] = $category->getName();
                $attributes['categories'][$index]['titles'][$indexStore]['language'] = Mage::getStoreConfig('general/locale/code', $store->getId());
                $attributes['categories'][$index]['reference'] = $category->getId();
                $index ++;
            }

            $indexStore ++;
        }
    }

    /**
     * add Shipments info for product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return null
     */
    protected function _addShipments($product, &$attributes)
    {
        // @codingStandardsIgnoreLine
        // @todo make multi-device and multi-store
        $country = 'FR';
        $qty = 1;
        $currency = 'EUR';

        //prepare info for temporary cart
        $addToCartInfo = array(
            'estimate' => array(
                'country_id' => $country
            ),
            'product' => $product->getId(),
            'related_product' => '',
            'qty' => $qty
        );
        $product->setAddToCartInfo($addToCartInfo);

        //set Qty
        $request = new Varien_Object($addToCartInfo);
        $request->setQty($qty);

        //create quote
        $quote = Mage::getModel('sales/quote')->setIsSuperMode(true)->setIgnoreOldQty(true);
        $quote->addProduct($product, $request);

        //set shipping quote by country
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCountryId($country);
        $shippingAddress->setCollectShippingRates(true);

        //calculate all totals
        $quote->collectTotals();

        //get rates
        $rateResult = $shippingAddress->getGroupedAllShippingRates();

        //format rates for each shipping methods
        $index = 0;
        foreach ($rateResult as $_code => $_method) {
            $method = $_method[0];

            $value = ($method->getPrice()) ? $method->getPrice() : 0;
            $tax = $this->_getShippingPercentTax($method->getPrice(), true, $shippingAddress);

            if ($_code == 'freeshipping') {
                $value = 0;
            }

            $attributes['shipments'][$index]['area'] = $country;
            $attributes['shipments'][$index]['quantity'] = $qty;
            $attributes['shipments'][$index]['method'] = $method->getCarrierTitle();
            $attributes['shipments'][$index]['carrier'] = $_code;
            // @codingStandardsIgnoreLine
            $attributes['shipments'][$index]['delay'] = 0; // @todo Required but nothing in magento
            $attributes['shipments'][$index]['shipment_amount']['value'] = $value;
            $attributes['shipments'][$index]['shipment_amount']['currency'] = $currency;
            $attributes['shipments'][$index]['vat'] = $tax; // required
            $index ++;
        }
    }

    /**
     * add picture link of product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return null
     */
    protected function _addImages($product, &$attributes)
    {
        $attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery');
        $media = Mage::getResourceSingleton('catalog/product_attribute_backend_media');
        $gallery = $media->loadGallery(
            $product, new Varien_Object(
                array(
                    'attribute' => $attribute
                )
            )
        );

        foreach ($gallery as $image) {
            $attributes['images'][]['url'] = $product->getMediaConfig()->getMediaUrl($image['file']);
            ;
        }
    }

    /**
     * add related product of parent product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array of parent product
     * @return null
     */
    protected function _addRelatedProducts($product, &$attributes)
    {
        if ($product->getRelatedProductIds()) {
            $attributes['related_products'] = $product->getRelatedProductIds();
        }
    }

    /**
     * Calcul of shipping tax
     *
     * @see Mage_Tax_Helper_Data::getShippingPrice()
     * @param int $price
     * @param bool $includingTax
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @param string $ctc
     * @param string $store
     * @return null
     */
    private function _getShippingPercentTax($price, $includingTax = null, $shippingAddress = null, $ctc = null, $store = null)
    {
        $pseudoProduct = new Varien_Object();
        $pseudoProduct->setTaxClassId(Mage::helper('oyst_oyst/catalog_tax')->getShippingTaxClass($store));

        $billingAddress = false;
        if ($shippingAddress && $shippingAddress->getQuote() && $shippingAddress->getQuote()->getBillingAddress()) {
            $billingAddress = $shippingAddress->getQuote()->getBillingAddress();
        }

        return Mage::helper('oyst_oyst/catalog_tax')
            ->getPrice($pseudoProduct, $price, $includingTax, $shippingAddress, $billingAddress, $ctc, $store, false, true, true);
    }

    /**
     * Get config from Magento
     *
     * @param string $code
     * @return mixed
     */
    protected function _getConfig($code)
    {
        return Mage::getStoreConfig("oyst/catalog_settings/$code");
    }
}
