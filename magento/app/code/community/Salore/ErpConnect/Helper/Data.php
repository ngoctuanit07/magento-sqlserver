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
}	