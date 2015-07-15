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
class Salore_ErpConnect_Model_Observer {
	
	const TABLE_SALES_ORDER_HEADER = 'tblSalesOrderHeader';
	const TABLE_SALES_ORDER_DETAIL = 'tblSalesOrderDetail';
	
	protected $_helper = null;
	protected $_incrementNumber = 0;
	
	public function __construct() {
		$this->_helper = Mage::helper('sberpconnect');
	}
	
	public function saveOrderToMssql( $orderHeaderData = array(), $orderDetailData = array() ) {
		$mssqlDb = $this->getMssqlConnection();

		try {
			$mssqlDb->insert(static::TABLE_SALES_ORDER_HEADER, $orderHeaderData);
		} catch ( Exception $e ) {
			$this->_helper->log( $e->getMessage() );
		}
		try {
			$mssqlDb->insertMultiple(static::TABLE_SALES_ORDER_DETAIL, $orderDetailData);
			$this->alterTableIncrementNumber($this->_incrementNumber);
		} catch (Exception $e) {
			$this->_helper->log( $e->getMessage() );
		}
	}
	
	public function getMssqlConnection() {
		return $this->_helper->getConnection();
	}
	
	public function salesPlaceOrderAfter( $observer ) {
		
		
		$order = $observer->getEvent()->getOrder();
		
		//Prepare Data for table OrderHeader 
		$orderHeaderData = $this->prepareOrderHeaderData( $order );
		//Prepare Data for table OrderDetail
		$orderDetailData = $this->prepareOrderDetailData( $order );
		
		$this->saveOrderToMssql( $orderHeaderData, $orderDetailData );
	}
	
	public function prepareOrderDetailData( $order ) {
		$items = array();
		$orderItems = $order->getAllItems();
		$currencyAmt = $order->getRewardCurrencyAmount();
		$rewardPointSummary  = 0;
		$subscriptionSummary = 0;
		$couponSummary		 = 0;
		$incrementNumber = 0; 
		$totalSubPrice = 0;
		$couponcode = $order->getData('coupon_code');
		
		foreach ($orderItems as $orderItem) {
			$items[] = $this->prepareOrderDetailItem($order, $orderItem );
			$productId = $orderItem->getProductId();
			$productObject = Mage::getModel('catalog/product')->load($productId);
			$qty = $orderItem->getQtyOrdered();
			
			//calculate REWARDS POINTS summary
			if( $currencyAmt > 0 ) {
					$rewardPointSummary += $currencyAmt;
			} 
			
			if( 'Y' == $this->prepareDropShip( $orderItem ) ) {
				if(isset($couponcode) && $couponcode) {
					$productPrice = $productObject->getPrice();
					$platformProduct = Mage::helper('autoship/platform')->getPlatformProduct($productObject);
					$subPrice = Mage::helper('autoship/subscription')->getSubscriptionPrice($platformProduct , $productObject , $qty , false);
					$totalSubPrice += round(($productPrice - $subPrice) ,0);
					$couponSummary +=( ($orderItem->getDiscountAmount() ) - $totalSubPrice);
				} else {
					$productPrice = $productObject->getPrice();
					$platformProduct = Mage::helper('autoship/platform')->getPlatformProduct($productObject);
					$subPrice = Mage::helper('autoship/subscription')->getSubscriptionPrice($platformProduct , $productObject , $qty , false);
					$totalSubPrice += round(($productPrice - $subPrice) ,0);
				}
			} else {
				$couponSummary += $orderItem->getDiscountAmount(); 
			} 
			
			
			$incrementNumber = $orderItem->getItemId();
		}
		
		//prepare discount items
		$discounts = $this->getDiscountCases($order, $rewardPointSummary, $totalSubPrice, $couponSummary );
		$index = 0;
		foreach ($discounts as $discountInfo){
			$incrementNumber++;
			$items[] = $this->prepareOrderDetailDiscountItem($order, $incrementNumber, $discountInfo);
			$index++;
		}
		if( $index > 0 ) {
			$this->_incrementNumber = ($incrementNumber + 1);
		}
		return $items;
	}
	
	public function prepareOrderDetailItem( $order, $orderItem ){
		$data = array();
		//Basic fields
		$data['MagSalesOrderNo'] 	 = $order->getIncrementId() ;
		$data['SalesOrderNo'] 		 =  $this->_helper->prefixOrderNo($order->getId());
		$data['MagLineNo'] 			 = $orderItem->getItemId();
		$data['QuantityOrdered']	 = $orderItem->getQtyOrdered();
		$data['QuantityBackordered'] = $orderItem->getQtyBackordered();
		$data['LineWeight'] 		 = $orderItem->getWeight();
		$data['UnitOfMeasure'] 		 = "EACH";
		//Discount-will be inserted as separated row
		$data ['ItemCode'] 			 =  $orderItem->getSku();
		$data ['ItemCodeDesc'] 		 = $orderItem->getName();
		$data ['ExtensionAmt'] 		 = $orderItem->getRowTotal();
		$data['DropShip'] 			 = $this->prepareDropShip($orderItem);
		$data ['Discount']         	 = '';
		$data['DiscountAmt'] 		 = '';
		return $data;
	}
	
	public function getDiscountCases($order, $rewardPointSummary, $subscriptionSummary, $couponSummary ) {
		$discounts = array();
		//CouponCode
		$couponcode = $order->getData('coupon_code');
		if(isset($couponcode) && $couponcode && $couponSummary > 0) {
			$discounts[] = array($couponcode , $couponSummary);
		}
		//RewardPoint
		$currencyAmt = $order->getRewardCurrencyAmount();
		if( $currencyAmt > 0 && $rewardPointSummary > 0 ){
			$discounts[] = array('/REWARDS POINTS', $rewardPointSummary);
		}
		//Subscription
		if( $subscriptionSummary > 0 ){
			$discounts[] = array('Subscription', $subscriptionSummary);
		}
		//GiftCard
		$giftcardcode = unserialize($order->getGiftCards());
		$giftcardSummary = 0;
		foreach ($giftcardcode as $card ) {
			$giftCardAmount = $order->getGiftCardsAmount();
			if( isset( $card['c'] ) &&  !empty($card['c']) ) {
				$giftcardSummary += $giftCardAmount;
			}
		}
		if( $giftcardSummary > 0 ){
			$dicounts[] = array('/GIFT CARD' , $giftcardSummary );
		}
		return $discounts;
	}
	public function prepareOrderDetailDiscountItem($order, $incrementNumber, $discountInfo){
		$data = array();
		//Discount-will be inserted as separated row
		if(isset($discountInfo[0]) && isset($discountInfo[1])) {
			$data['MagSalesOrderNo'] 	 = $order->getIncrementId()   ;
			$data['SalesOrderNo'] 		 =  $this->_helper->prefixOrderNo($order->getId());
			$data['MagLineNo'] 			 = $incrementNumber;
			$data['QuantityOrdered'] 	 = '';
			$data['QuantityBackordered'] = '';
			$data['LineWeight']			 = '';
			$data['UnitOfMeasure']		 = '';
			$data['ItemCode']			 = '';
			$data['ItemCodeDesc']		 = '';
			$data['ExtensionAmt']		 = '';
			$data['DropShip']		 = '';
			$data ['Discount']         	 = $discountInfo[0];
			$data['DiscountAmt'] 		 = $discountInfo[1];
			
		}
		return $data;
	}
	public function alterTableIncrementNumber($number) {
    	$resource = Mage::getSingleton('core/resource');
    	$writeConnection = $resource->getConnection('core_write');
    	$tableName = $resource->getTableName('sales/order_item');
    	$writeConnection->query('ALTER TABLE '.$tableName.' AUTO_INCREMENT = '.$number);
    }
	public function prepareDropShip( $orderItem ){
		$dropShip = 'N';
		$productOption = unserialize($orderItem->getData('product_options'));
		
		if( isset($productOption['info_buyRequest']) && count($productOption['info_buyRequest']) > 0 ) {
			
			if( isset($productOption['info_buyRequest']['delivery-option']) ) {
				$option =  strcmp($productOption['info_buyRequest']['delivery-option'] , "subscribe");
				if((int) $option === 0) {
					$dropShip = 'Y';
				}
			}
			
		}
		return $dropShip;
	}
	public function prepareOrderHeaderData( $order ) {
		
		$data = array();
		
		$data['MagSalesOrderNo'] 	= $order->getIncrementId()  ;
		$data['SalesOrderNo'] 		=  $this->_helper->prefixOrderNo($order->getId());
		$data['OrderDate'] 			= $this->_helper->formatDate($order->getCreatedAt ());
		$data['CustomerNo'] 		= $order->getCustomerId() ? $order->getCustomerId() : '';
		$data['BillToName'] 		= $this->getBillingAddressField($order, 'firstname') . ' ' . $this->getBillingAddressField($order, 'lastname');
		$data['BillToAddress1'] 	= $this->getBillingAddressField($order, 'street');
		$data['BillToCity'] 		= $this->getBillingAddressField($order, 'city');
		$data['BillToState'] 		= $this->getRegionCode( $order, 'billing' );
		$data['BillToZipCode'] 		= $this->getBillingAddressField($order, 'postcode');
		$data['BillToCountryCode'] 	= $this->getBillingAddressField($order, 'country_id');
		$data['ShipToName'] 		= $this->getShippingAddressField($order, 'firstname') . ' ' . $this->getShippingAddressField($order, 'lastname');
		$data['ShipToAddress1'] 	= $this->getShippingAddressField($order, 'street');
		$data['ShipToCity'] 		= $this->getShippingAddressField($order, 'city');
		$data['ShipToState'] 		= $this->getRegionCode( $order, 'shipping' );
		$data['ShipToZipCode'] 		= $this->getShippingAddressField($order, 'postcode');
		$data['ShipToCountryCode'] 	= $this->getShippingAddressField($order, 'country_id');
		$data['ConfirmTo'] 			= $this->getBillingAddressField($order, 'firstname') . ' ' . $this->getBillingAddressField($order, 'lastname');
		
		$data['EmailAddress'] 		= $order->getCustomerEmail ();
		$grandTotal 				= $order->getGrandTotal ();
		$taxAmount  				= $order->getTaxAmount();
		
		$taxSchedule 				= $this->prepareTaxSchedule( $taxAmount );
		$data['TaxSchedule'] 		= $taxSchedule;
		
		$data['TaxableAmt']			= $this->prepareTaxableAmt($order, $taxSchedule );
		$data['NonTaxableAmt']		= $this->prepareNonTaxableAmt($order, $taxSchedule );
		
		$data ['SalesTaxAmt'] 		= $order->getTaxAmount();
		$data ['DepositAmt'] 		= $order->getGrandTotal ();
		$data ['CustomerPONo'] 		= $order->getIncrementId ();
		$data ['FreightAmt'] 		= $order->getShippingAmount();
		$data ['OrderLength'] 		= $this->getQuote()->getItemsCount();
		$data ['ShipVia'] 			= $this->prepareShiVia();
		return $data;
	}
	
	public function prepareTaxSchedule( $taxAmount ) {
		$taxSchedule = 'NA';
		if( $taxAmount > 0 ) {
			$taxSchedule = 'AVATAX' ;
		}
		return $taxSchedule;
	}
	
	public function prepareTaxableAmt($order, $taxSchedule ) {
		$value = 0;
		$grandTotal = $order->getGrandTotal();
		$taxAmount  = $order->getTaxAmount();
		if( (int)strcmp($taxSchedule , "AVATAX") === 0 ) {
			$value =  Mage::helper('core')->formatPrice(($grandTotal - $taxAmount), false);
		}
		return $value;
	}
	
	public function prepareNonTaxableAmt($order, $taxSchedule ) {
		$value = 0;
		$grandTotal = $order->getGrandTotal();
		if( (int)strcmp($taxSchedule , 'NA') === 0 ) {
			$value =  Mage::helper('core')->formatPrice(($grandTotal), false);
		}
		return $value;
	}
	
	public function prepareShiVia() {
		$shipVia = '';
		$shippingmethod = $this->getQuote()->getShippingAddress()->getShippingMethod();
		if(isset($shippingmethod) && $shippingmethod) {
			$shipVia = $this->_helper->checkLenghtShippingMethod($shippingmethod);
		}
		return strtoupper($shipVia);
		
		
	}
	
	public function getQuote() {
		return Mage::getSingleton ( 'checkout/session' )->getQuote();
	}
	
	public function getRegionCode( $order, $type = 'billing' ) {
		
		$regionCode = '';
		if($type === 'billing') {
			$billingAddress = $order->getBillingAddress();
			if( !empty($billingAddress) ) {
				$billingAddressData = $billingAddress->getData();
				$regionId =  $this->_helper->getAddressField( $billingAddressData, 'region_id' );
				$regionCode =  Mage::getModel('directory/region')->load($regionId)->getCode();
			}
		} else {
			$shippingAddress = $order->getShippingAddress();
			if( !empty($shippingAddress) ) {
				$shippingAddressData = $shippingAddress->getData();
				$regionId =  $this->_helper->getAddressField( $shippingAddressData, 'region_id' );
				$regionCode =  Mage::getModel('directory/region')->load($regionId)->getCode();
			}
		}
		return $regionCode;
	}
	
	public function getBillingAddressField( $order, $fieldName ){
		$billingAddress = $order->getBillingAddress();
		return $this->_helper->getAddressField($billingAddress, $fieldName);
	}
	
	public function getShippingAddressField( $order, $fieldName ){
		$shippingAddress = $order->getShippingAddress();
		return $this->_helper->getAddressField($shippingAddress, $fieldName);
	}
	
	public function changeStatusOrderInAdmin($observer) {
		$order = $observer->getEvent()->getOrder();
		$orderStatus = $order->getStatus();
		$db = $this->getMssqlConnection();
		$orderHeaderData = array ();
		$orderDetailData = array();
		$where = "MagSalesOrderNo = " .$order->getIncrementId()  ;
		try {
			switch ($orderStatus) {
				case $orderStatus === "processing":
					$orderHeaderData['OrderStatus'] = "NEW";
					$db->update (static::TAB_SALES_ORDER_HEADER , $orderHeaderData , $where);
					break;
				case $orderStatus === "Shipped":
					$orderHeaderData['OrderStatus'] = "OPEN";
					$db->update (static::TABLE_SALES_ORDER_HEADER , $orderHeaderData , $where);
					$db->update ('tblSalesOrderHeader' , $orderHeaderData , $where);
					break;
			}
		} catch (Exception $e) {
			$this->_helper->log( $e->getMessage() );
		}
	}
	
	public function salesOrderShipmentSaveAfter($observer) {
		$order = $observer->getEvent()->getShipment()->getOrder();
		$db = $this->getMssqlConnection();
		$orderHeaderData = array ();
		$where = "MagSalesOrderNo = " . $order->getIncrementId ();
		try {
			$this->setOrderShipmentSaveAfter($order , $orderHeaderData  );
			$db->update (static::TABLE_SALES_ORDER_HEADER , $orderHeaderData , $where);
		} catch (Exception $e) {
			$this->_helper->log( $e->getMessage() );
		}
	
	}
	
	protected function setOrderShipmentSaveAfter($order , &$orderHeaderData ) {
		foreach($order->getShipmentsCollection() as $shipment) {
			// Tempority use shipped date , need to correct this later
			$orderHeaderData ['ShipExpireDate'] = $shipment->getCreatedAt();
		}
	}
	
	public function getInvoiceDateFromAdmin($observer) {
		$invoice 		  = $observer->getEvent()->getInvoice();
		$orderIncrementId = $invoice->getOrder()->getIncrementId();
		$items 			  = $invoice->getAllItems();
		$dataOrderDetail  = array();
		$db 		  	  = $this->getMssqlConnection();
		$where = "MagSalesOrderNo = " . $orderIncrementId;
		foreach ($items as $item) {
			// Tempority use invoice date , need to correct this later
			$dataOrderDetail ['PromiseDate'] = $item->getCreatedAt();
			$db->update(static::TABLE_SALES_ORDER_DETAIL , $dataOrderDetail , $where);
		}
	}
	
	static public function dailyCatalogUpdate() {
		/**
		 * $currentTimestamp = Mage::getModel ( 'core/date' )->timestamp ( time () );
		 * $date = date ( 'Y-m-d H:i:s', $currentTimestamp );
		 * Mage::log ( $date, null, 'erpconnect.log' );
		 */
		Mage::helper('sberpconnect/product')->import();
		//	Mage::helper('sberpconnect/customer')->import();
	}
	
	
	
}
