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
	const ERROR_LOG_FILE = "salore_erpconnect_log.log";
	
	public function log( $message ) {
		Mage::log( $message, null, self::ERROR_LOG_FILE );
	}
	
	public function getConnection() {
		return Mage::getModel ( 'core/resource' )->getConnection ( 'sbmssql_write' );
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
	
	public function getAddressField($bind, $field) {
		if (isset ( $bind [$field] )) {
			return $bind [$field];
		}
		return null;
	}
	
	public function formatDate($date) {
		$timestamp = Mage::getModel('core/date')->timestamp($date);
		$date = date('m/d/Y', $timestamp);
		return $date;
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
}