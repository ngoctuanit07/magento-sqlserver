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
	protected  $flagCreateProduct = false;
	protected $flagUpdateProduct = false;
	protected $flagUpdateInventory = false;
	protected $_helper = null;
	public function __construct() {
		$this->_helper = Mage::helper('sberpconnect');
	}
    public function import() {
        $connection = $this->_helper->getConnection ();
        $select = $connection->select()->from( 'tblItem' );
        $products = $connection->fetchAll( $select );
       	$dataProduct = array();
        $optionCreateProduct  = Mage::helper('sberpconnect')->isEnableCreateProduct();
        $optionUpdateProduct = Mage::helper('sberpconnect')->isEnableUpdateProduct();
        $optionInvertoryProduct = Mage::helper('sberpconnect')->isEnableInventoryProduct();
        if((int)$optionCreateProduct === 1 ) {
       		$this->flagCreateProduct = true;
        }
        if((int)$optionUpdateProduct === 1) {
       		$this->flagUpdateProduct = true;
        }
        if((int)$optionInvertoryProduct === 1) {
       		$this->flagUpdateInventory = true;
       	}
       	if($this->flagCreateProduct === false && $this->flagUpdateProduct === false && ($this->flagUpdateInventory === false || $this->flagUpdateInventory === true)) {
       		return;
        }
		foreach ( $products as $data ) {
        	//import and update product
			if($this->flagCreateProduct === true && $this->flagUpdateProduct === true ) {
				if(!$this->isSkuExist($data['SKU'])) {
					$this->createProduct ( $data );
					$this->updateSage($data, $dataProduct, $connection);
					continue;
				}  else {
					$this->updateProduct($data);
					continue;
				} 
        	}
        	//import product
         	if($this->flagCreateProduct === true && $this->flagUpdateProduct === false ) {
            	if(!$this->isSkuExist($data['SKU'])) {
            		$this->createProduct($data);
            		$this->updateSage($data, $dataProduct, $connection);
            		continue;
            	} else {
            		$this->updateSage($data, $dataProduct, $connection);
            		continue;
            	}
            }
            //update product 
            if($this->flagCreateProduct === false && $this->flagUpdateProduct === true ) {
				if($this->isSkuExist($data['SKU'])) {
					$this->updateProduct($data);
				} else {
					return;
					continue;
				}
            } 
        }
    }
	
    public function updateSage($data , &$dataProduct , $connection) {
   		$dataProduct['SentToMagento'] = $data ['DateAdded'];
		$where = "SKU = '{$data['SKU']}'";    	
		$connection->update("tblItem", $dataProduct , $where);
    }
    /**
     * Check if product exists in magento
     * @param unknown $sku
     * @return boolean
     */
    public function isSkuExist($sku) {
       	$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
       	if ( $product ) {
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
	            ->setMediaGallery ( 
	            	array(
	                    'images' => array(),
	                    'values' => array()
	            	)
             	)
             	->setCategoryIds(array(3));
           
            	if($this->flagUpdateInventory ) {
            		$productInventory = array(
            			'use_config_manage_stock' => 1,
            			'manage_stock' => 1,
            			'min_sale_qty' => 1,
            			'max_sale_qty' => 2,
            			'qty' => $data ['Qty']
            		);
            	
            		if($data['Qty'] > 0) {
            			$productInventory['is_in_stock'] = (int)1;
            		} else {
            			$productInventory['is_in_stock'] = (int)0;
            			$productInventory['qty'] = (int)0;
            		}
            		$product->setStockData ( $productInventory);
            	}
            	$product->save ();
        } catch ( Exception $e ) {
        	$this->_helper->log($e->getMessage());
        }
    }
    public function updateProduct( $data) {
    	$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$data['SKU']);
    	if (!$product->getId()) {//insert new product
    		$this->createProduct($data);
    	}
    	$product->setAttributeSetId(4); // 4 means Default AttributeSet
    	$product->setTypeId('simple');
    	$product->setName($data ['SKU_Name']);
    	$product->setCategoryIds(array(2,3,4,5,6,7));
    	$product->setWebsiteIDs(array(1)); # Website id, 1 is default
    	$product->setDescription((string)$XMLproduct->LongDescription);
    	$product->setShortDescription((string)$XMLproduct->Description);
    	$product->setPrice($data ['Price']);
    	$product->setWeight(1.0);
    	$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG);
    	$product->setStatus(1);
    	$product->setTaxClassId(0); # My default tax class
    	$product->setCreatedAt(date("m.d.y") );
    	
    	try {
    		$product->save();
    		$productId = $product->getId();
    		$stockItem =Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
    		$stockItemId = $stockItem->getId();
    		if($this->flagUpdateInventory) {
    			if($data['Qty'] < 0 ) {
    				$stockItem->setData('manage_stock', 0);
    			} else {
    				$stockItem->setData('manage_stock', 1);
    			}
    			$stockItem->setData('qty', $data['Qty']);//(integer)$XMLproduct->QtyInStock
    		}
    		$stockItem->save();
    	}
    	catch (Exception $e) {
    		Mage::log($e->getMessage() , null , 'updateproduct.log');
    	}
    }
}

