<?php
/*** DISCLAIMER
 *
* Do not edit or add to this file if you wish to upgrade Salore to newer
* versions in the future.
*
* @category    Salore
* @package     Salore_Salon
* @author      Salore team
* @copyright   Copyright (c) Salore team
*/

require_once 'abstract.php';
class Salore_ErpConnect_Shell extends Mage_Shell_Abstract
{
	public function __construct()
	{
		parent::__construct();
	}
	public function run()
	{
		ini_set('memory_limit', '2040M');
		if ($this->getArg('import'))
		{
			Mage::helper('sberpconnect/product')->import();	
		}
		echo "Done";
	}
	
}
$shell = new Salore_ErpConnect_Shell();
$shell->run();