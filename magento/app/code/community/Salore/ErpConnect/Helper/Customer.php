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
        foreach ( $customers as $data ) {
            if (!$this->checCustomerExist($data ['EMailAddress'])) {
                $this->createCustomer ( $data );
            }
        }
    }
    /**
     * Check if product exists in magento
     * @param unknown $sku
     * @return boolean
     */
    public function isSkuExist($sku)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($product && $product->getId() > 0)
        {
            return true;
        }
        return false;
    }
    public function checCustomerExist($email) {
    	$customer = Mage::getModel("customer/customer")->loadByEmail($email);
    	if($customer->getCustomerId() > 0 && $customer) {
    		return true;
    	}
    	return false;
    	 
    }
    public function createCustomer($data) {
        $customer = Mage::getModel ( 'customer/customer' )->loadByEmail($data['EMailAddress']);
        $websiteId = Mage::app()->getWebsite()->getId();
        $store = Mage::app()->getStore();
        try {
            $customer
            ->setWebsiteId($websiteId)
            ->setStore($store)
            ->setCreatedAt($data['AddedDate'])
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail($data['EMailAddress'])
            ->setPassword('somepassword');
            $_custom_address = array (
            		'firstname'  => $data['firstname'],
            		'lastname'   => $data['lastname'],
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
            ->setSaveInAddressBook('1');
        } catch ( Exception $e ) {
            Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
}
