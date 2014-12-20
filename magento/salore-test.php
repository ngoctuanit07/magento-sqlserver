<?php
require_once ('app/Mage.php');
Mage::app();
$connection = Mage::getSingleton('core/resource')->getConnection('sbmssql_write');
$result = $connection->getServerVersion();
print_r($result); die();
