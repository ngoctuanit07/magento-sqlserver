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
	protected  $_helper = null;
	public function __construct()
	{
		$this->_helper = Mage::helper('sberpconnect');
	}
	/**
	 * Insert  order data from magento  To Sage()
	 * @param  $observer
	 * @return statement resources
	 */
	public function salesPlaceOrderAfter($observer)
	{
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$orderId = $observer->getEvent()->getOrder()->getId();
		$cartItems = $quote->getAllVisibleItems();
		$db = $this->_helper->getConnection();
		$dataOrderHeader = array();
		$dataOrderDetail = array();
		$dataAdjustment = array();
		$dataShippingItem = array();
		$dataShipingTracking = array();
		$salesOrderHeader = 'tblSalesOrderHeader';
		$salesOrderDetail = 'tblSalesOrderDetail';
		$salesOrderAdjustments = 'tblSOAdjustment';
		$shippeditem = 'tblShippedByItem';
		$shippingtracking = 'tblShippedTracking';
		try {
			$this->setOrderData($dataOrderHeader , $dataOrderDetail , $dataAdjustment , $dataShippingItem , $dataShipingTracking , $cartItems , $orderId);
			$db->insert($salesOrderHeader , $dataOrderHeader);
			$db->insert($salesOrderDetail , $dataOrderDetail);
			$db->insert($salesOrderAdjustments , $dataAdjustment);
			$db->insert($shippeditem , $dataShippingItem);
			$db->insert($shippingtracking , $dataShipingTracking);
		} catch (Exception $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
		}
	}
	protected  function setOrderData(&$dataOrderHeader , &$dataOrderDetail , &$dataAdjustment , &$dataShippingItem , &$dataShipingTracking  , &$cartItems , &$orderId) 
	{
		foreach ($cartItems as $item)
		{
			$productId = $item->getProductId();
			$product = Mage::getModel('catalog/product')->load($productId);
			$taxClassId = $product->getTaxClassId();
			$taxClassName = Mage::getModel('tax/class')->load($taxClassId)->getClassName();
			$order = Mage::getModel('sales/order')->load($orderId);
			$termCode = Mage::helper('checkout')->getRequiredAgreementIds();
			$billingAddress = $order->getBillingAddress()->getData();
			$shippingAddress = $order->getShippingAddress()->getData();
			$dataOrderHeader['MagSalesOrderNo'] = $order->getIncrementId();
			$dataOrderHeader['SalesOrderNo'] = $order->getId();
			$dataOrderHeader['OrderDate'] = $order->getCreatedAt();
			$dataOrderHeader['OrderType'] = 'N';
			$dataOrderHeader['OrderStatus'] = 'N';
			$dataOrderHeader['ShipExpireDate'] = date("m.d.Y");
			$dataOrderHeader['ARDivisionNo'] = 'No';
			$dataOrderHeader['CustomerNo'] =  $order->getCustomerId();
			$dataOrderHeader['BillToName'] = $this->_helper->getAddressField($billingAddress , 'firstname') . ''.$this->_helper->getAddressField($billingAddress , 'lastname');
			$dataOrderHeader['BillToAddress1'] = $this->_helper->getAddressField($billingAddress , 'street');
			$dataOrderHeader['BillToAddress2'] = 'No';
			$dataOrderHeader['BillToAddress3'] = 'No';
			$dataOrderHeader['BillToCity'] = $this->_helper->getAddressField($billingAddress , 'street');
			$dataOrderHeader['BillToState'] = 'NA';
			$dataOrderHeader['BillToZipCode'] = $this->_helper->getAddressField($billingAddress , 'postcode');
			$dataOrderHeader['BillToCountryCode'] = $billingAddress['country_id'];
			$dataOrderHeader['ShipToCode'] = 'No';
			$dataOrderHeader['ShipToName'] = $this->_helper->getAddressField($shippingAddress , 'firstname') . '' .$this->_helper->getAddressField($shippingAddress , 'lastname') ;
			$dataOrderHeader['ShipToAddress1'] = $this->_helper->getAddressField($shippingAddress , 'street');
			$dataOrderHeader['ShipToAddress2'] = 'No';
			$dataOrderHeader['ShipToAddress3'] = 'No';
			$dataOrderHeader['DepositAmt'] = 0;
			$dataOrderHeader['ReceivedDate'] =  date("m.d.Y");
			$dataOrderHeader['SentToSage'] =  date("m.d.Y");
			$dataOrderHeader['MagCompleteOrder'] = 0;
			$dataOrderHeader['CustomerPoNo'] = $order->getCustomerId();
			$dataOrderHeader['ShipVia'] = 'unknown';
			$dataOrderHeader['OrderLength'] = 0;
			$dataOrderHeader['ShipToCity'] = $this->_helper->getAddressField($shippingAddress , 'city');
			$dataOrderHeader['ShipToState'] = 'No';
			$dataOrderHeader['ShipToZipCode'] = $this->_helper->getAddressField($shippingAddress , 'postcode');
			$dataOrderHeader['ShipToCountryCode'] = $this->_helper->getAddressField($shippingAddress , 'country_id');
			$dataOrderHeader['ConfirmTo'] = $order->getCustomerEmail();
			$dataOrderHeader['Comment'] = $order->getCustomerEmail();
			$dataOrderHeader['TermsCode'] = implode("" , $termCode);
			$dataOrderHeader['TaxSchedule'] = substr($taxClassName , 0 , 9);
			$dataOrderHeader['TaxExemptNo'] = 'unknown';
			$dataOrderHeader['PaymentType'] = 'No'; 
			$dataOrderHeader['PaymentTypeCategory'] = 'N';
			$dataOrderHeader['DiscountRate'] = 0;
			$dataOrderHeader['DiscountAmt'] = $order->getDiscountAmount();
			$dataOrderHeader['TaxableAmt'] = $order->getGrandTotal();
			$dataOrderHeader['NonTaxableAmt'] = 0;
			$dataOrderHeader['SalesTaxAmt'] = 0;
			$dataOrderHeader['FreightAmt'] = 0;
			$dataOrderHeader['OtherPaymentTypeRefNo'] = 0;
			$dataOrderHeader['TaxableSubjectToDiscount'] = 0;
			$dataOrderHeader['TaxSubjToDiscPrcntOfTotSubjTo'] = 0;
			$dataOrderHeader['MagCompleteOrder'] = 0;
			$dataOrderHeader['OrderLength'] = 0;
			$dataOrderDetail['MagSalesOrderNo'] = $order->getIncrementId();
			$dataOrderDetail['MagLineNo'] = 'N';
			$dataAdjustment['MagSalesOrderNo'] =  $order->getIncrementId();
			$dataAdjustment['MagLineNo'] =  'N';
			$dataShippingItem['MagSalesOrderNo'] = $order->getIncrementId();
			$dataShippingItem['SageInvoiceNo'] = 'N';
			$dataShippingItem['MagLineNo'] = 'N';
			$dataShipingTracking['MagSalesOrderNo'] = $order->getIncrementId();
			$dataShipingTracking['SageInvoiceNo'] = 'N';
			$dataShipingTracking['PackageNo'] = 'N';
			
		}
	}
}