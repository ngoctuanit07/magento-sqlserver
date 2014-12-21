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
            $this->createProduct ( $data );
        }
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
            ->setWeight ( 4.0000 )
            ->setStatus ( 1 )
            ->setTaxClassId ( 4 )
            ->setVisibility ( Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH )
            ->setNewsToDate ( '06/30/2014' )
            ->setCountryOfManufacture ( 'AF' )
            ->setPrice ( $data ['Price'] )
            ->setDescription ( $data ['ShortDesc'] )
            ->setShortDescription ( $data ['ShortDesc'] )
            ->setMediaGallery ( array (
                    'images' => array (),
                    'values' => array () 
            ) )
            ->setStockData ( array (
                    'use_config_manage_stock' => 1,
                    'manage_stock' => 1,
                    'min_sale_qty' => 1,
                    'max_sale_qty' => 2,
                    'is_in_stock' => 1,
                    'qty' => $data ['Qty']
            ) );
            $product->save ();
        } catch ( Exception $e ) {
            throw $e;
        }
    }
}
