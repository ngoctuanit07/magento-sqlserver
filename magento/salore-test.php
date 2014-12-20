<?php
require_once ('app/Mage.php');
Mage::app();
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
//$connection = Mage::getSingleton('core/resource')->getConnection('sbmssql_write');
$connection = new Zend_Db_Adapter_Pdo_Mssql(array(
		'host'     => '192.168.1.211 , 1433', // parklife
		'username' => 'sa',
		'password' => 'qaz123456',
		'dbname'   => 'Sage',
		'pdoType'  =>  'dblib' )
);
$result = $connection->getServerVersion();
print_r($result); die();
