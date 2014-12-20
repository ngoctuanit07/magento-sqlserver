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
		$product->setData('sku' , $data['SKU']);
		$product->setData('name' , $data['SKU_Name']);
		$product->setData('short_description' , $data['ShortDesc']);
		$product->setData('description' , $data['ShortDesc']);
		$product->setData('price' , $data['Price']);
		$product->setData('qty' , $data['Qty']);
		$product->save();
	}
	
}
