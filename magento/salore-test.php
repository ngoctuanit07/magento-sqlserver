<?php
require_once ('app/Mage.php');
Mage::app();
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
$connection = Mage::getSingleton('core/resource')->getConnection('sbmssql_write');
$result = $connection->select()->from('tblItem');
print_r($result); die();
