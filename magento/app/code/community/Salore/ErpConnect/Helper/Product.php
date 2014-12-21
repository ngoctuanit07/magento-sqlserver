<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade SolrBridge to newer
 * versions in the future.
 *
 * @category    Salore
 * @package     Salore_ErpConnect
 * @author      Salore team
 * @copyright   Copyright (c) Salore team
 */
class Salore_ErpConnect_Helper_Product extends Mage_Core_Helper_Abstract {
    /**
     * Import Product From Microsoft Sql to Magento
     */
    public function import() {
        $connection = Mage::helper ( 'sberpconnect' )->getConnection ();
        $select = $connection->select ()->from ( 'tblItem' );
        $products = $connection->fetchAll ( $select );
        foreach ( $products as $data ) {
            if (!$this->isSkuExist($data ['SKU'])) {
                $this->createProduct ( $data );
            }
        }
    }
    /**
     * Check if product exists in magento
     * @param unknown $sku
     * @return boolean
     */
    public function isSkuExist($sku)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($product && $product->getId() > 0)
        {
            return true;
        }
        return false;
    }
    public function createProduct($data) {
        $product = Mage::getModel ( 'catalog/product' );
        try {
            $product
            ->setWebsiteIds ( array (1) )
            ->setAttributeSetId ( 4 )
            ->setTypeId ( 'simple' )
            ->setCreatedAt ( strtotime ( 'now' ) )
            ->setSku ( $data ['SKU'] )
            ->setName ( $data ['SKU_Name'] )
            ->setWeight ( 1.0000 )
            ->setStatus ( 1 )
            ->setTaxClassId ( 4 )
            ->setVisibility ( Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH )
            ->setPrice ( $data ['Price'] )
            ->setDescription ( $data ['ShortDesc'] )
            ->setShortDescription ( $data ['ShortDesc'] )
            ->setMediaGallery ( array (
                    'images' => array (),
                    'values' => array () 
           			)
             )
            ->setStockData ( array (
                    'use_config_manage_stock' => 1,
                    'manage_stock' => 1,
                    'min_sale_qty' => 1,
                    'max_sale_qty' => 2,
                    'is_in_stock' => 1,
                    'qty' => $data ['Qty']
           		 ) 
            )
            ->setCategoryIds(array(3));
            $product->save ();
        } catch ( Exception $e ) {
            Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
}
