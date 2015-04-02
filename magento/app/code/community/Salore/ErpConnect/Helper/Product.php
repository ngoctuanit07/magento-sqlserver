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
       $dataProduct = array();
//	print_r($products); die(); 
	foreach ( $products as $data ) {
            if (!$this->isSkuExist($data ['SKU'])) {
$this->createProduct ( $data );
		 $this->updateSage($data, $dataProduct, $connection);
            }else 
		{
	//	print_r($data); 
            	$this->updateSage($data, $dataProduct, $connection);
            }
        }
    }
	/*public function updateSage($data , &$dataProduct , &$connection) {
	
	$productCollection = Mage::getModel('catalog/product')->loadByAttribute('sku', $data ['SKU']);
	$dataProduct['SentToMagento'] = $productCollection->getCreatedAt();
    	$where = "SKU = " . $productCollection->getSku();
    	$connection->update(static::TABLE_ITEM , $dataProduct , $where);
    }*/
     public function updateSage($data , &$dataProduct , $connection) {
	//die('abc');
    	$dataProduct['SentToMagento'] = $data ['DateAdded'];
    //	$where = "SKU=" . '{$data ['SKU']}';
	//$where = "SKU = ". '"'.$data['SKU'].'"';
	//echo get_class($connection);
	$where = "SKU = '{$data['SKU']}'";    	
$connection->update("tblItem", $dataProduct , $where);

    }
    /**
     * Check if product exists in magento
     * @param unknown $sku
     * @return boolean
     */
    public function isSkuExist($sku)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($product )
        {
		//die('abc');
            return true;
        }
        return false;
    }
    public function createProduct($data) {
        $product = Mage::getModel ( 'catalog/product' );
        $specialPrice = $data['Special_Price'];
        $specialPriceFromDate = $data['Special_From'];
        $specialPriceToDate = $data['Special_To'];
        $taxClassId = $data['Tax_Class_ID'];
        try {
            $product
            ->setWebsiteIds ( array (1) )
            ->setAttributeSetId ( 4 )
            ->setTypeId ( 'simple' )
            ->setCreatedAt ( $data ['DateAdded'] )
            ->setSku ( $data ['SKU'] )
            ->setName ( $data ['SKU_Name'] )
            ->setSpecialPrice($specialPrice) 
            ->setSpecialFromDate($specialPriceFromDate) 
            ->setSpecialToDate($specialPriceToDate)
            ->setWeight ( 1.0000 )
            ->setStatus ( 1 )
            ->setTaxClassId ( $taxClassId )
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

