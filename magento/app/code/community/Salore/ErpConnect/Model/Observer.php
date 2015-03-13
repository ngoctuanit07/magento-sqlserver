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
        $currency = $order->getRwrdCurrencyAmountInvoiced();
        $db = $this->_helper->getConnection ();
        $dataOrderHeader = array ();
        $dataOrderDetail = array ();
        try {
        	$this->prepareDataForTableSalesOrderHeader($dataOrderHeader, $quote, $order);
            $db->insert ( static::TABLE_SALES_ORDER_HEADER, $dataOrderHeader );
            $this->insertDataSalreOrderDetail($db, $order, $dataOrderDetail);
        } catch ( Exception $e ) {
             Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
    protected function insertDataSalreOrderDetail($db , $order , $dataOrderDetail ) {
    	$orderId = $order->getId();
    	$orderIncrementId = $order->getIncrementId ();
    	$orderIncrement = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    	$giftcardcode = unserialize($orderIncrement->getGiftCards());
    	$couponCode = $orderIncrement->getData('coupon_code');
    	if(isset($couponCode) && $couponCode) {
    		$this->checkCouponCode($couponCode, $dataOrderDetail, $order);
    	} else {
    		$this->prepareDataOrderDetailDefault($orderId, $dataOrderDetail);
    	}
    	if(isset($giftcardcode) && $giftcardcode) {
    		$this->checkGiftCardCode($giftcardcode, $dataOrderDetail);
    	}
    	$orderItemIds  = $this->_helper->getItemIdFromOrder($orderId);
    	$dataOrderDetail['MagSalesOrderNo'] = $order->getIncrementId ();
    	$dataOrderDetail['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
    	// still not sure when use Y or N
    	$dataOrderDetail['DropShip'] = "N";
    	foreach ($orderItemIds as $id) {
    		$dataOrderDetail['MagLineNo'] = $id;
    		try {
    			$db->insert(static::TABLE_SALES_ORDER_DETAIL, $dataOrderDetail);
    		} catch ( Exception $e ) {
    			Mage::log($e->getMessage(), null, 'erpconnection.log');
    		}
    	}
    }
    protected function prepareDataForTableSalesOrderHeader(&$dataOrderHeader , $quote , $order ) {
    	$shippingmethod = $quote->getShippingAddress ()->getShippingMethod ();
    	$cartItems = $quote->getAllVisibleItems ();
    	foreach($cartItems as $item) {
    		$productId = $item->getProductId ();
    		$product = Mage::getModel ( 'catalog/product' )->load ( $productId );
    		$taxClassId = $product->getTaxClassId ();
    		$billingAddress = $order->getBillingAddress ()->getData ();
    		$shippingAddress = $order->getShippingAddress ()->getData ();
    		$dataOrderHeader['MagSalesOrderNo'] = $order->getIncrementId ();
    		$dataOrderHeader['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
    		$dataOrderHeader['OrderDate'] = $order->getCreatedAt ();
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
    		$dataOrderHeader ['DiscountAmt'] = $order->getDiscountAmount ();
    		$grandTotal = $order->getGrandTotal ();
    		$taxAmount  = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
    		$dataOrderHeader['TaxableAmt'] = ($dataOrderHeader['TaxSchedule'] === "AVATAX") ? ($grandTotal - $taxAmount ) : "0"; 
    		$dataOrderHeader ['NonTaxableAmt'] = ($dataOrderHeader['TaxSchedule'] === "NA") ? $grandTotal : "0";
    		$dataOrderHeader ['SalesTaxAmt'] = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
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
    	$db = $this->_helper->getConnection ();
    	$dataOrderHeader = array ();
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
    		$this->setOrderShipmentSaveAfter($order , $dataOrderHeader );
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
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * 1);
    		}
    		if(($couponPregMatch ==0) && (is_null($currencyAmt))) {
    			$dataOrderDetail ['ItemCode'] = 'COUPON';
    			$dataOrderDetail ['UnitOfMeasure'] = 'DOL';
    			$dataOrderDetail ['ItemCodeDesc'] = "contents of"."{$couponCode}";
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * 1);
    		}
    		if(isset($currencyAmt) && $currencyAmt > 0) {
    			$dataOrderDetail ['ItemCode'] = "/REWARDS POINTS";
    			$dataOrderDetail ['UnitOfMeasure'] = "DOL";
    			$dataOrderDetail ['ItemCodeDesc'] = "Rewards Points";
    			$dataOrderDetail ['ExtensionAmt'] = ($currencyAmt * (-1));
    		}
    	}
    }
    protected function prepareDataOrderDetailDefault($orderId , &$dataOrderDetail) {
    	$orderCollection = Mage::getModel('sales/order')->load($orderId);
    	$orderItem = $orderCollection->getAllItems();
    	foreach ($orderItem as $item) {
    		$dataOrderDetail ['ItemCode'] = $item->getSku();
    		$dataOrderDetail ['ItemCodeDesc'] =$item ->getName();
    		$dataOrderDetail ['LineWeight'] = $item->getWeight();
    		$dataOrderDetail ['ExtensionAmt'] = $item->getRowTotal();
    	}
    }
    protected function checkGiftCardCode($giftcardcode , &$dataOrderDetail) {
    	foreach ($giftcardcode as $card ) {
    		if(isset( $card['c']) &&  $card['c']) {
    			$dataOrderDetail ['ItemCode'] = "/GIFT CARD";
    			$dataOrderDetail ['UnitOfMeasure'] = "DOL";
    			$dataOrderDetail ['ItemCodeDesc'] = "to be determined";
    		} else {
    			return;
    		}
    	}
    }
    static public function dailyCatalogUpdate() {
       /**
        * $currentTimestamp = Mage::getModel ( 'core/date' )->timestamp ( time () );
        * $date = date ( 'Y-m-d H:i:s', $currentTimestamp );
        * Mage::log ( $date, null, 'erpconnect.log' );
        */
    	Mage::helper('sberpconnect/product')->import();
    	Mage::helper('sberpconnect/customer')->import();
    }
}