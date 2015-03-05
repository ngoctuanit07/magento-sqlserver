<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade SolrBridge to newer
 * versions in the future.
 *
 * @category    Salore
 * @package     Salore_Mssql
 * @author      Salore team
 * @copyright   Copyright (c) Salore team
 */
class Salore_Mssql_Adapter_Mssql extends Salore_Mssql_Adapter_Abstract {
	public function __construct($config) {
		//Override config values from admin settings
		$configs = Mage::getStoreConfig('sbmssql/setting');
		if(isset($configs['password']) && !empty($configs['password'])) {
			$configs['password'] = Mage::helper('core')->decrypt( $configs['password'] );
		}
		$this->_config = array_merge($config , $configs);
		parent::__construct($this->_config);
	}
}