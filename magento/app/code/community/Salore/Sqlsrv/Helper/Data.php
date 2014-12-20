<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade SolrBridge to newer
 * versions in the future.
 *
 * @category    Salore
 * @package     Salore_Sqlsrv
 * @author      Salore team
 * @copyright   Copyright (c) Salore team
 */
class Salore_Sqlsrv_Helper_Data extends Mage_Core_Helper_Abstract {
	public function getConnection() {
		return Mage::getModel('core/resource')->getConnection('sbsqlsrv_write');
	}
}