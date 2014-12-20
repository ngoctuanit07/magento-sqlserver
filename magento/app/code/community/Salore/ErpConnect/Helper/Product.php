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
		$connection = Mage::helper('sberpconnect')->getConnection();
		$select = $connection->select()->from('tblItem');
		$products = $connection->fetchAll($select);
		foreach ($products as $data)
		{
			$this->createProduct($data);
		}  
	}
	public function createProduct($data) {
		$product = Mage::getModel('catalog/product');
		try {
			$product
			->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
			->setAttributeSetId(9) //ID of a attribute set named 'default'
			->setTypeId('simple') //product type
			->setCreatedAt(strtotime('now')) //product creation time
			->setSku($data['SKU']) //SKU
			->setName($data['SKU_Name']) //product name
			->setWeight(4.0000)
			->setStatus(1) //product status (1 - enabled, 2 - disabled)
			->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
			->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
			->setNewsToDate('06/30/2014') //product set as new to
			->setCountryOfManufacture('AF') //country of manufacture (2-letter country code)
			->setPrice($data['Price']) //price in form 11.22
			->setDescription($data['ShortDesc'])
			->setShortDescription($data['ShortDesc'])
			->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
			->setStockData(array(
					'use_config_manage_stock' => 1, //'Use config settings' checkbox
					'manage_stock'=>1, //manage stock
					'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
					'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
					'is_in_stock' => 1, //Stock Availability
					'qty' => $data['Qty'] //qty
			)
			)
			$product->save();
		} catch (Exception $e) {
			throw $e;
		}
	}
	
}
