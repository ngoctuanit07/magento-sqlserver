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
class Salore_ErpConnect_Model_Observer
{
	/**
	 * Insert  order data from magento  To Sage()
	 * @param  $observer
	 * @return statement resources
	 */
	public function salesPlaceOrderAfter($observer)
	{
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$order_id = $observer->getEvent()->getOrder()->getId();
		$cartItems = $quote->getAllVisibleItems();
		$db = $this->_helper->getConnection();
		$bind = array();
		$salesOrderHeader = 'tblSalesOrderHeader';
		try {
			if($this->setOrderData($cartItems , $order_id))
			{
				$db->insert($salesOrderHeader , $bind);
			}
			else 
			{
				Mage::getSingleton('core/session')->addError('Please check information order');
			}
			
		} catch (Exception $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
		}
	}
	protected  function setOrderData() 
	{
		foreach ($cartItems as $item)
		{
			$product_id = $item->getProductId();
			$product = Mage::getModel('catalog/product')->load($product_id);
			$taxClassId = $product->getTaxClassId();
			$taxClassName = Mage::getModel('tax/class')->load($taxClassId)->getClassName();
			$order = Mage::getModel('sales/order')->load($order_id);
			$termCode = Mage::helper('checkout')->getRequiredAgreementIds();
			$billingAddress = $order->getBillingAddress()->getData();
			$shippingAddress = $order->getShippingAddress()->getData();
			$bind['MagSalesOrderNo'] = $order->getIncrementId();
			$bind['SalesOrderNo'] = $order->getId();
			$bind['OrderDate'] = $order->getCreatedAt();
			$bind['OrderType'] = 'N';
			$bind['OrderStatus'] = 'N';
			$bind['ShipExpireDate'] = date("m.d.Y");
			$bind['ARDivisionNo'] = 'No';
			$bind['CustomerNo'] =  $order->getCustomerId();
			$bind['BillToName'] = $this->_helper->getAddressField($billingAddress , 'firstname') . ''.$this->_helper->getAddressField($billingAddress , 'lastname');
			$bind['BillToAddress1'] = $this->_helper->getAddressField($billingAddress , 'street');
			$bind['BillToAddress2'] = 'No';
			$bind['BillToAddress3'] = 'No';
			$bind['BillToCity'] = $this->_helper->getAddressField($billingAddress , 'street');
			$bind['BillToState'] = 'NA';
			$bind['BillToZipCode'] = $this->_helper->getAddressField($billingAddress , 'postcode');
			$bind['BillToCountryCode'] = $billingAddress['country_id'];
			$bind['ShipToCode'] = 'No';
			$bind['ShipToName'] = $this->_helper->getAddressField($shippingAddress , 'firstname') . '' .$this->_helper->getAddressField($shippingAddress , 'lastname') ;
			$bind['ShipToAddress1'] = $this->_helper->getAddressField($shippingAddress , 'street');
			$bind['ShipToAddress2'] = 'No';
			$bind['ShipToAddress3'] = 'No';
			$bind['DepositAmt'] = 0;
			$bind['ReceivedDate'] =  date("m.d.Y");
			$bind['SentToSage'] =  date("m.d.Y");
			$bind['MagCompleteOrder'] = 0;
			$bind['CustomerPoNo'] = $order->getCustomerId();
			$bind['ShipVia'] = 'unknown';
			$bind['OrderLength'] = 0;
			$bind['ShipToCity'] = $this->_helper->getAddressField($shippingAddress , 'city');
			$bind['ShipToState'] = 'No';
			$bind['ShipToZipCode'] = $this->_helper->getAddressField($shippingAddress , 'postcode');
			$bind['ShipToCountryCode'] = $this->_helper->getAddressField($shippingAddress , 'country_id');
			$bind['ConfirmTo'] = $order->getCustomerEmail();
			$bind['Comment'] = $order->getCustomerEmail();
			$bind['TermsCode'] = implode("" , $termCode);
			$bind['TaxSchedule'] = substr($taxClassName , 0 , 9);
			$bind['TaxExemptNo'] = 'unknown';
			$bind['PaymentType'] = 'No'; 
			$bind['PaymentTypeCategory'] = 'N';
			$bind['DiscountRate'] = 0;
			$bind['DiscountAmt'] = $order->getDiscountAmount();
			$bind['TaxableAmt'] = $order->getGrandTotal();
			$bind['NonTaxableAmt'] = 0;
			$bind['SalesTaxAmt'] = 0;
			$bind['FreightAmt'] = 0;
			$bind['OtherPaymentTypeRefNo'] = 0;
			$bind['TaxableSubjectToDiscount'] = 0;
			$bind['TaxSubjToDiscPrcntOfTotSubjTo'] = 0;
			$bind['MagCompleteOrder'] = 0;
			$bind['OrderLength'] = 0;
		}
	}
}