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

class Salore_ErpConnect_Model_Observer
{
	/**
	 * Insert Data From Magento To Sage
	 * @param  $observer
	 * @return statement resources
	 */
	public function salesPlaceOrderAfter($observer)
	{
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$orders = $observer->getEvent()->getOrder();
		$cartItems = $quote->getAllVisibleItems();
		$db = Mage::helper('sberpconnect')->getConnection();
		$bind = array();
		$salesOrderHeader = 'tblSalesOrderHeader';
		foreach ($cartItems as $item)
		{
			$product_id = $item->getProductId();
			$order_id = $orders->getId();
			if(isset($product_id) && $product_id)
			{
				$order_id = $observer->getEvent()->getOrder()->getId();
				$product = Mage::getModel('catalog/product')->load($product_id);
				$taxClassId = $product->getTaxClassId();
				$taxClassName = Mage::getModel('tax/class')->load($taxClassId);
				var_dump($taxClassName); die();
				$taxClassName = Mage::getModel('tax/class')->load($taxClassId)->getClassName();
				if(isset($order_id) && $order_id)
				{
					$order = Mage::getModel('sales/order')->load($order_id);
					$termCode = Mage::helper('checkout')->getRequiredAgreementIds();
					$billing_address = $order->getBillingAddress()->getData();
					$shipping_address = $order->getShippingAddress()->getData();
					$bind['MagSalesOrderNo'] = $order->getIncrementId();
					$bind['OrderDate'] = $order->getCreateAt();
					$bind['SalesOrderNo'] = $order->getId();
					$bind['CustomerNo'] = $order->getCustomerId();
					$bind['BillToName'] = $billing_address['firstname'] . ''.$billing_address['lastname'];
					$bind['BillToAddress1'] = $billing_address['street'];
					$bind['BillToAddress2'] = 'No';
					$bind['BillToAddress3'] = 'No';
					$bind['TaxSchedule'] = substr($taxClassName , 0 , 9);
					$bind['TermsCode'] = implode("" , $termCode);
					$bind['BillToCity'] = $billing_address['city'];
					$bind['BillToState'] = substr($billing_address['region'], 0, 2);
					$bind['BillToZipCode'] = (string)($billing_address['postcode']);
					$bind['BillToCountryCode'] = $billing_address['country_id'];
					$bind['ShipToCode'] = $shipping_address['entity_id'];
					$bind['ShipToName'] = $shipping_address['firstname'] . '' . $shipping_address['lastname'];
					$bind['ShipToAddress1'] = $shipping_address['street'];
					$bind['ShipToAddress2'] = 'No';
					$bind['ShipToAddress3'] = 'No';
					$bind['ShipToCity'] = $shipping_address['city'];
					$bind['ShipToState'] =  substr($shipping_address['region'], 0, 2);
					$bind['ShipToZipCode'] = $shipping_address['postcode'];
					$bind['ShipToCountryCode'] = $shipping_address['country_id'];
					$bind['EmailAddress'] = $order->getCustomerEmail();
					$bind['ConfirmTo'] = $order->getCustomerEmail();
					try {
						$db->insert($salesOrderHeader , $bind);
					} catch (Exception $e) {
						Mage::getSingleton('core/session')->addError($e->getMessage());
					}
				}
			}
			
		}
		
     	
	}
	public function salesorder($observer)
	{
		$order = $observer->getEvent()->getOrder()->getData();
		//var_dump($order); die();
		//$order_id = $order->getEntityId();
		$db = Mage::helper('sberpconnect')->getConnection();
		$bind = array();
		$salesOrderHeader = 'tblSalesOrderHeader';
		
		if(isset($order['entity_id'])  && $order['entity_id'])
		{
			$where = 'MagSalesOrderNo ='.$order['increment_id'];
			//var_dump($order->getIncrementId()); die();
			/* $orderComments = $order->getAllStatusHistory();
			foreach ( $orderComments as $comment)
			{
				$bind['Comment'] = $comment->getData('comment');
			} */
			//var_dump(  $order->getAllStatusHistory()); die();
			$bind['OrderStatus'] = substr($order['status'] ,0 , 1);
			$bind['FreightAmt'] = $order['shipping_amount'];
			$bind['TaxableAmt'] = $order['grand_total'];
		}
		else 
		{
			return  false;
		}
		try {
			$db->update($salesOrderHeader , $bind , $where);
			//$db->insert($salesOrderDetail , $detail);
			//$db->insert($salesOrderAdjustments , $adjustment);
			//$db->insert($shippeditem , $shippitem);
			//$db->insert($shippingtracking , $shipingitem);
				
		} catch (Exception $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
		}
	}
	public function loadcommentorder($observer)
	{
		$order = $observer->getEvent();
		//$order = $observer->getEvent()->getStatusHistory();
		$comment = $order->getComment();
		$db = Mage::helper('sberpconnect')->getConnection();
		$bind = array();
		$salesOrderHeader = 'tblSalesOrderHeader';
		if(isset($comment) && $comment)
		{
			$bind['Comment'] = $comment;
			try {
				$db->insert($salesOrderHeader , $bind);
			} catch (Exception $e) {
				Mage::getSingleton('core/session')->addError($e->getMessage());
			}
		}
		else 
		{
			
		}
		
		
	}
	/* public function initFrontController($observer)
	{
		die('187');
	} */
}