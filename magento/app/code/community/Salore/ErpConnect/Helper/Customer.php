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
class Salore_ErpConnect_Helper_Customer extends Mage_Core_Helper_Abstract {
    /**
     * Import Customer From Microsoft Sql to Magento
     */
	protected $_helper = null;
	public function __construct() {
		$this->_helper = Mage::helper('sberpconnect');
	}
    public function import() {
        $connection = Mage::helper ( 'sberpconnect' )->getConnection ();
        $select = $connection->select ()->from ( 'tblCustomer' );
        $customers = $connection->fetchAll ( $select );
        foreach ( $customers as $data ) {
        	if((int)strlen(trim($data['EmailAddress'])) <= 0 ) {
				continue 1;
        	}
          	if(!$this->checCustomerExist($data ['EmailAddress'])) {
                $this->createCustomer ( $data );
                $this->updateSage($data, $connection);
            } else {
                $this->updateSage($data, $connection);
            }
        }
    }
    public function checCustomerExist($email) {
    	$customer = Mage::getModel("customer/customer")->loadByEmail($email);
    	if($customer->getCustomerId() > 0 && $customer) {
    		return true;
    	}
    	return false;
    }
    public function updateSage($data  , $connection) {
    	$updateData = array();
    	$updateData['SentToMagento'] = "1";
    	$email = $data ['EmailAddress'];
    	$where = "EmailAddress = '{$email}'"  ;
    	$connection->update("tblCustomer" , $updateData , $where);
    }
    
    public function createCustomer($data) {
        $customer = Mage::getModel ( 'customer/customer' );
        $websiteId = Mage::app()->getWebsite()->getId();
        $store = Mage::app()->getStore();
        try {
            $customer
	            ->setWebsiteId($websiteId)
	            ->setStore($store)
	            ->setCreatedAt($data['AddedDate'])
	            ->setFirstname($data['CustomerName'])
	            ->setLastname('Doe')
	            ->setEmail($data['EmailAddress'])
	            ->setPassword('somepassword');
	            $customer->save(); 
        } catch ( Exception $e ) {
        	$this->_helper->log($e->getMessage());
        }
    }
}

