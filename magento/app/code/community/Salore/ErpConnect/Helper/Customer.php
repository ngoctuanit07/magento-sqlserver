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
     * Import Product From Microsoft Sql to Magento
     */
    public function import() {
        $connection = Mage::helper ( 'sberpconnect' )->getConnection ();
        $select = $connection->select ()->from ( 'tblCustomer' );
        $customers = $connection->fetchAll ( $select );
	 echo $data['EmailAddress'];
        foreach ( $customers as $data ) {
        	if((int)strlen(trim($data['EmailAddress'])) <= 0 ) {
		//	print_r($data);
        		echo $data['EmailAddress'];
			continue 1;
			
				
        	}
          	if (!$this->checCustomerExist($data ['EmailAddress'])) {
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
            $customer->save(); echo $customer->getId();die();
        /*    $_custom_address = array (
            		'firstname'  => $data['CustomerName'],
            		'lastname'   => "Doe",
            		'street'     => array (
            				'0' => $data['AddressLine1'] ,
            				'1' => $data['AddressLine2']
            		),
            		'city'       => $data['City'],
            		'postcode'   => $data['ZipCode'],
            		'country_id' => $data['CountryCode'],
            		'telephone'  => array('0' => $data['TelephoneNo'], '1' => $data['TelephoneExt']),
            		'fax'        => $data['FaxNo'],
            		 
            );
            $customAddress   = Mage::getModel('customer/address');
            $customAddress->setData($_custom_address)
            ->setCustomerId($customer->getId()) // this is the most important part
            ->setIsDefaultBilling('1')  // set as default for billing
            ->setIsDefaultShipping('1') // set as default for shipping
            ->setSaveInAddressBook('1');*/
        } catch ( Exception $e ) {
            Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
}

