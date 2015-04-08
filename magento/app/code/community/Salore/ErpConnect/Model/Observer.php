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
    protected $street = null;
    protected $firstname = null;
    protected $lastname = null;
    public function __construct() {
        $this->_helper = Mage::helper ( 'sberpconnect' );
        $this->street = 'street';
        $this->firstname = 'firstname';
        $this->lastname = 'lastname';
    }
    /**
     * Insert order data from magento To Sage()
     * 
     * @param
     *            $observer
     * @return statement resources
     */
    public function salesPlaceOrderAfter($observer) {
    	
        $quote = Mage::getSingleton ( 'checkout/session' )->getQuote ();
        $order = $observer->getEvent ()->getOrder ();
		//$order->setData("created_at" , $this->_helper->formatDate($order->getCreatedAt ()));
        $currency = $order->getRwrdCurrencyAmountInvoiced();
        $db = $this->_helper->getConnection ();
        $dataOrderHeader = array ();
        $dataOrderDetail = array ();
        try {
        	$this->prepareDataForTableSalesOrderHeader($dataOrderHeader, $quote, $order);
        	$this->prepareDataTaxAmtTableOrderHeader($order, $dataOrderHeader);
            $db->insert ( static::TABLE_SALES_ORDER_HEADER, $dataOrderHeader );
            $this->insertDataSalreOrderDetail($db, $order, $dataOrderDetail);
        } catch ( Exception $e ) {
             Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
  protected function  prepareDataTaxAmtTableOrderHeader($order , &$dataOrderHeader) {
    	$orderId = $order->getId();
    	$orderItems = $this->_helper->getItemIdFromOrder($orderId);
		$taxableAmt = 0;
    	$nonTaxbleAmt = 0 ;
		$taxAmt = 0;
    	foreach ($orderItems as $item) {
    		
    		if($item['tax_amount'] > 0) {
			$taxAmt +=$item['tax_amount'];     			
    			$taxableAmt += $item['row_total'];
    		} else {
    			$nonTaxbleAmt += $item['row_total'];
    		}
    	}
		if($taxAmt > 0) {
			$dataOrderHeader['SalesTaxAmt'] = $taxAmt;
		} 
		$dataOrderHeader['TaxableAmt'] = $taxableAmt;
		$dataOrderHeader['NonTaxableAmt'] = $nonTaxbleAmt;
    }
  	protected function insertDataSalreOrderDetail($db , $order , $dataOrderDetail ) {
		$coupon = false;
		$giftcard = false;
    	$orderId = $order->getId();
    	$orderIncrementId = $order->getIncrementId ();
    	$orderIncrement = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    	$giftcardcode = unserialize($orderIncrement->getGiftCards());
    	$couponCode = $orderIncrement->getData('coupon_code');
    	$orderItems  = $this->_helper->getItemIdFromOrder($orderId);
    	if(isset($couponCode) && $couponCode) {
			$coupon = true;
    		$this->checkCouponCode($couponCode, $dataOrderDetail, $order);
    	}elseif(is_array($giftcardcode) && count($giftcardcode)) {
			$giftcard = true;
    		$this->checkGiftCardCode($giftcardcode, $dataOrderDetail);
    	}
    	$dataOrderDetail['MagSalesOrderNo'] = $order->getIncrementId ();
    	$dataOrderDetail['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
    	foreach ($orderItems as $item) {
    		$dataOrderDetail['MagLineNo'] = $item['item_id'];
			$dataOrderDetail ['QuantityOrdered'] = $item['qty_ordered'];
			$dataOrderDetail ['QuantityBackordered'] = $item['qty_backordered'];
			$dataOrderDetail ['LineWeight'] = $item['weight'];
			$dataOrderDetail['DiscountAmt'] = $order->getDiscountAmount ();
			if(!$coupon && !$giftcard) {
				$this->prepareDataAnyColumTableOrderDetail($item , $dataOrderDetail);		
			}
    		try {
    			$db->insert(static::TABLE_SALES_ORDER_DETAIL, $dataOrderDetail);
    		} catch ( Exception $e ) {
    			Mage::log($e->getMessage(), null, 'erpconnection.log');
    		}
    	}
    }
    protected function prepareDataAnyColumTableOrderDetail($item , &$dataOrderDetail) {
    		$dataOrderDetail ['ItemCode'] = $item['sku'];
    		$dataOrderDetail ['ItemCodeDesc'] =$item['name'];
    		$dataOrderDetail ['ExtensionAmt'] = $item['row_total'];
    }
    protected function prepareDataForTableSalesOrderHeader(&$dataOrderHeader , $quote , $order ) {
    	$shippingmethod = $quote->getShippingAddress ()->getShippingMethod ();
    	$cartItems = $quote->getAllVisibleItems ();
    	foreach($cartItems as $item) {
    		$productId = $item->getProductId ();
    		$product = Mage::getModel ( 'catalog/product' )->load ( $productId );
    		$taxClassId = $product->getTaxClassId ();
    		$billingAddress = $order->getBillingAddress ();
    		if(isset($billingAddress) && $billingAddress ) {
    			$billingAddress = $order->getBillingAddress ()->getData ();
    		}
    		$shippingAddress = $order->getShippingAddress ();
    		if(isset($shippingAddress) && $shippingAddress ) {
    			$shippingAddress = $order->getShippingAddress ()->getData ();
    		}
    		$dataOrderHeader['MagSalesOrderNo'] = $order->getIncrementId ();
    		$dataOrderHeader['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
			$dataOrderHeader['OrderDate'] = $this->_helper->formatDate($order->getCreatedAt ());
    		$dataOrderHeader['CustomerNo'] = $order->getCustomerId () ? $order->getCustomerId()  : ""  ;
    		$dataOrderHeader['BillToName'] = $this->_helper->getAddressField ( $billingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $billingAddress, $this->lastname );
    		$dataOrderHeader['BillToAddress1'] =  $this->_helper->getAddressField ( $billingAddress, $this->street );
    		$dataOrderHeader['BillToCity'] = $this->_helper->getAddressField ( $billingAddress, 'city' );
    		$dataOrderHeader['BillToState'] = $this->_helper->getAddressField ( $billingAddress, 'region' );
    		$dataOrderHeader['BillToZipCode'] = $this->_helper->getAddressField ( $billingAddress, 'postcode' );
    		$dataOrderHeader['BillToCountryCode'] =  $billingAddress ['country_id'];
    		$dataOrderHeader['ShipToName'] = $this->_helper->getAddressField ( $shippingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $shippingAddress, $this->lastname );
    		$dataOrderHeader['ShipToAddress1'] = $this->_helper->getAddressField ( $shippingAddress, $this->street );
    		$dataOrderHeader['ShipToCity'] = $this->_helper->getAddressField ( $shippingAddress, 'city' );
    		$dataOrderHeader['ShipToState'] = $this->_helper->getAddressField ( $shippingAddress, 'region' );
    		$dataOrderHeader['ShipToZipCode'] = $this->_helper->getAddressField ( $shippingAddress, 'postcode' );
    		$dataOrderHeader['ShipToCountryCode'] = $this->_helper->getAddressField ( $shippingAddress, 'country_id' ); 
    		$dataOrderHeader ['ConfirmTo'] = $this->_helper->getAddressField ( $billingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $billingAddress, $this->lastname );
    		// still not sure assign it to default "NA"
    		$dataOrderHeader ['TaxSchedule'] = 'NA';
    		$dataOrderHeader ['EmailAddress'] = $order->getCustomerEmail ();
    		$grandTotal = $order->getGrandTotal ();
    		$taxAmount  = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
    		//$dataOrderHeader['TaxableAmt'] = ($dataOrderHeader['TaxSchedule'] === "AVATAX") ? ($grandTotal - $taxAmount ) : "0"; 
    		//$dataOrderHeader ['NonTaxableAmt'] = ($dataOrderHeader['TaxSchedule'] === "NA") ? $grandTotal : "0";
    		//$dataOrderHeader ['SalesTaxAmt'] = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
    		$dataOrderHeader ['DepositAmt'] = $order->getGrandTotal ();
    		$dataOrderHeader ['CustomerPONo'] = $order->getIncrementId ();
    		$dataOrderHeader ['FreightAmt'] = $order->getShippingAmount();
    		$dataOrderHeader ['OrderLength'] = $quote->getItemsCount();
    		$shipVia = $this->_helper->checkLenghtShippingMethod($shippingmethod);
    		$dataOrderHeader ['ShipVia'] = strtoupper($shipVia);
    	}
    }
    public function changeStatusOrderInAdmin($observer) {
    	$order = $observer->getEvent()->getOrder();
    	$billingAddress = $order->getBillingAddress ()->getData ();
    	$orderIncrementId = $order->getIncrementId();
    	$orderStatus = $order->getStatus();
    	$orderIncrementModel = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		$giftcardcode = unserialize($orderIncrementModel->getGiftCards());
    	$db = $this->_helper->getConnection ();
    	$dataOrderHeader = array ();
    	$dataOrderDetail = array();
    	$where = "MagSalesOrderNo = " . $orderIncrementId;
    	try {
    		switch ($orderStatus) {
    			case $orderStatus === "processing":
    				$dataOrderHeader['OrderStatus'] = "NEW";
    				$db->update (static::TABLE_SALES_ORDER_HEADER , $dataOrderHeader , $where);
    				break;
    			case $orderStatus === "Shipped":
    				$dataOrderHeader['OrderStatus'] = "OPEN";
    				$db->update (static::TABLE_SALES_ORDER_HEADER , $dataOrderHeader , $where);
    				break;

    		}
    		$db->update(static::TABLE_SALES_ORDER_DETAIL , $dataOrderDetail , $where);
    		
    		
    	} catch (Exception $e) {
    		Mage::log($e->getMessage(), null, 'erpconnection.log');
    	}
    }
    public function salesOrderShipmentSaveAfter($observer) {
    	$order = $observer->getEvent()->getShipment()->getOrder();
    	$db = $this->_helper->getConnection ();
    	$dataOrderHeader = array ();
		$where = "MagSalesOrderNo = " . $order->getIncrementId ();
    	try {
    	    $this->setOrderShipmentSaveAfter($order , $dataOrderHeader  );
    	    $db->update (static::TABLE_SALES_ORDER_HEADER , $dataOrderHeader , $where);
    	} catch (Exception $e) {
    		Mage::log($e->getMessage(), null, 'erpconnection.log');
    	}
    
    }
    protected function setOrderShipmentSaveAfter($order , &$dataOrderHeader ) {
    	foreach($order->getShipmentsCollection() as $shipment) {
    		// Tempority use shipped date , need to correct this later
    		$dataOrderHeader ['ShipExpireDate'] = $shipment->getCreatedAt();
    	}
    }
    protected function checkCouponCode($couponCode , &$dataOrderDetail , $order) {
    	$currencyAmt = $order->getRwrdCurrencyAmountInvoiced();
    	$qty = $order->getDiscountAmount ();
    	$orderId = $order->getId();
    	if(empty($couponCode)) {
    		return;
    	}  else {
    		$couponPregMatch = (int)preg_match('/^ECENTER[0-9]{1,}$/',strtoupper($couponCode));
    		if(($couponPregMatch == 1) && (is_null($currencyAmt))) {
    			$dataOrderDetail ['ItemCode'] = "/ECENTER COUPON";
    			$dataOrderDetail ['UnitOfMeasure'] = "DOL";
    			$dataOrderDetail ['ItemCodeDesc'] = "{$couponCode}";
			$dataOrderDetail ['Discount'] = $dataOrderDetail['ItemCode'];
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * 1);
    		}
    		if(($couponPregMatch ==0) && (is_null($currencyAmt))) {
    			$dataOrderDetail ['ItemCode'] = 'COUPON';
    			$dataOrderDetail ['UnitOfMeasure'] = 'DOL';
			$dataOrderDetail ['Discount'] = $dataOrderDetail['ItemCode'];
    			$dataOrderDetail ['ItemCodeDesc'] = "contents of"."{$couponCode}";
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * 1);
    		}
    		if(isset($currencyAmt) && $currencyAmt > 0) {
    			$dataOrderDetail ['ItemCode'] = "/REWARDS POINTS";
    			$dataOrderDetail ['UnitOfMeasure'] = "DOL";
			$dataOrderDetail ['Discount'] = $dataOrderDetail['ItemCode'];
    			$dataOrderDetail ['ItemCodeDesc'] = "Rewards Points";
    			$dataOrderDetail ['ExtensionAmt'] = ($currencyAmt * (-1));
    		}
    	}
    }
    protected function checkGiftCardCode($giftcardcode , &$dataOrderDetail) {
    	foreach ($giftcardcode as $card ) {
    		if(isset( $card['c']) &&  $card['c']) {
    			$dataOrderDetail ['ItemCode'] = "/GIFT CARD";
    			$dataOrderDetail ['UnitOfMeasure'] = "DOL";
    			$dataOrderDetail ['ItemCodeDesc'] = "to be determined";
    		}
    	}
    }
	public function getInvoiceDateFromAdmin($observer) {
    	 $invoice = $observer->getEvent()->getInvoice();
    	 $orderIncrementId = $invoice->getOrder()->getIncrementId();
    	 $items = $invoice->getAllItems();
    	 $dataOrderDetail = array();
    	 $db = $this->_helper->getConnection ();
    	 $where = "MagSalesOrderNo = " . $orderIncrementId;
    	 foreach ($items as $item) {
    	 	// Tempority use invoice date , need to correct this later
			$dataOrderDetail ['PromiseDate'] = $item->getCreatedAt();
			$db->update(static::TABLE_SALES_ORDER_DETAIL , $dataOrderDetail , $where);
    	 }
    }
  /* public function getOrderAfterSaveInAdmin($observer) {
        $order = $observer->getEvent ()->getOrder ();
	 $db = $this->_helper->getConnection ();
        $dataOrderDetail = array ();
        $where = "MagSalesOrderNo = " . $order->getIncrementId ();
        try {
            $this->setOrderDataAfterSaveInAdmin ( $order , $dataOrderDetail );
            $db->update ( static::TABLE_SALES_ORDER_DETAIL, $dataOrderDetail, $where );
        } catch ( Exception $e ) {
            Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
    protected function setOrderDataAfterSaveInAdmin( &$order , &$dataOrderDetail) {
        $dataOrderDetail ['QuantityOrdered'] = ( int ) ($order->getQtyOrdered ());
        $dataOrderDetail ['QuantityShipped'] = ( int ) ($order->getQtyShipped ());
        $dataOrderDetail ['QuantityBackordered'] = ( int ) ($order->getQtyBackordered ());
    }*/
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

