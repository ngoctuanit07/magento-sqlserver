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
class Salore_ErpConnect_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getAddressField($bind, $field) {
        if (isset ( $bind [$field] )) {
            return $bind [$field];
        }
        return null;
    }
    public function getConnection() {
        return Mage::getModel ( 'core/resource' )->getConnection ( 'sbmssql_write' );
    }
    public function getItemIdFromOrder( $orderId ) {
    	$id = array();
    	$orderCollection = Mage::getModel('sales/order')->load($orderId);
    	$orderItem = $orderCollection->getAllItems();
    	foreach ($orderItem as $item) {
    		$id[] = $item->getData();
    	}
    	return $id;
    }
    public function prefixOrderNo($orderId) {
    	$lengthId = strlen($orderId);
    	$prefix = null;
    	switch ($lengthId) {
    	
    		case $lengthId == "1":
    			$prefix = "000000".$orderId	;
    		break;
    		case $lengthId == "2":
    			$prefix = "00000".$orderId	;
    			break;
    		case $lengthId == "3":
    			$prefix = "0000".$orderId	;
    			break;
    		case $lengthId == "4":
    			$prefix = "000".$orderId	;
    			break;
    		case $lengthId == "5":
    			$prefix = "00".$orderId	;
    			break;
    		case $lengthId == "6":
    			$prefix = "0".$orderId	;
    			break;
    		case $lengthId == "7":
    			$prefix = $orderId	;
    			break;
    	}
    	return $prefix;
    }
    public function checkLenghtShippingMethod($shippingMethod) {
    	$lengthShippingMethod = strlen($shippingMethod);
    	$shipvia = null;
    	if($lengthShippingMethod <= 15) {
    		$shipvia = $shippingMethod;
    	} else {
    		return;
    	}
    	return $shipvia;
    }
 public function formatDate($date) {
    	$timestamp = Mage::getModel('core/date')->timestamp($date);
    	$date = date('m/d/Y', $timestamp);
    	return $date;
    }
	public function isEnableCreateProduct() {
    	return Mage::getStoreConfig('sbmssql/setting/create_product');
    }
    public function isEnableUpdateProduct() {
    	return Mage::getStoreConfig('sbmssql/setting/update_product');
    }
    public function isEnableInventoryProduct() {
    	return Mage::getStoreConfig('sbmssql/setting/inventory_product');
    }
}	
