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
	const TABLE_SALES_ORDER_ADJUSTMENT = 'tblSOAdjustment';
	const TABLE_SHIP_BY_ITEM = 'tblShippedByItem';
	const TABLE_SHIP_TRACKING = 'tblShippedTracking';
	
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
        $shippingmethod = $quote->getShippingAddress ()->getShippingMethod ();
        $orderId = $observer->getEvent ()->getOrder ()->getId ();
        $cartItems = $quote->getAllVisibleItems ();
        $db = $this->_helper->getConnection ();
        $insertData = array ();
        $dataOrderDetail = array ();
        $dataAdjustment = array ();
        $dataShippingItem = array ();
        $dataShipingTracking = array ();
        
        try {
            $this->setOrderData ( $shippingmethod, $insertData, $dataOrderDetail, $dataAdjustment, $dataShippingItem, $dataShipingTracking, $cartItems, $orderId );
            $db->insert ( static::TABLE_SALES_ORDER_HEADER, $insertData );
            $db->insert ( static::TABLE_SALES_ORDER_DETAIL, $dataOrderDetail );
            $db->insert ( static::TABLE_SALES_ORDER_ADJUSTMENT, $dataAdjustment );
            $db->insert ( static::TABLE_SHIP_BY_ITEM, $dataShippingItem );
            $db->insert ( static::TABLE_SHIP_TRACKING, $dataShipingTracking );
        } catch ( Exception $e ) {
             Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
    protected function setOrderData(&$shippingmethod, &$insertData, &$dataOrderDetail, &$dataAdjustment, &$dataShippingItem, &$dataShipingTracking, &$cartItems, &$orderId) {
        foreach ( $cartItems as $item ) {
            $productId = $item->getProductId ();
            $product = Mage::getModel ( 'catalog/product' )->load ( $productId );
            $taxClassId = $product->getTaxClassId ();
            $taxClassName = Mage::getModel ( 'tax/class' )->load ( $taxClassId )->getClassName ();
            $order = Mage::getModel ( 'sales/order' )->load ( $orderId );
            $termCode = Mage::helper ( 'checkout' )->getRequiredAgreementIds ();
            $billingAddress = $order->getBillingAddress ()->getData ();
            $shippingAddress = $order->getShippingAddress ()->getData ();
            $insertData ['MagSalesOrderNo'] = $order->getIncrementId ();
            $insertData ['SalesOrderNo'] = $order->getId ();
            $insertData ['OrderDate'] = $order->getCreatedAt ();
            $insertData ['OrderType'] = 'N';
            $insertData ['OrderStatus'] = 'N';
            $insertData ['ShipExpireDate'] = date ( "m.d.Y" );
            $insertData ['ARDivisionNo'] = 'No';
            $insertData ['CustomerNo'] = $order->getCustomerId ();
            $insertData ['BillToName'] = $this->_helper->getAddressField ( $billingAddress, $this->firstname ) . '' . $this->_helper->getAddressField ( $billingAddress, $this->lastname );
            $insertData ['BillToAddress1'] = $this->_helper->getAddressField ( $billingAddress, $this->street );
            $insertData ['BillToAddress2'] = 'No';
            $insertData ['BillToAddress3'] = 'No';
            $insertData ['BillToCity'] = $this->_helper->getAddressField ( $billingAddress, 'city' );
            $insertData ['BillToState'] = 'NA';
            $insertData ['BillToZipCode'] = $this->_helper->getAddressField ( $billingAddress, 'postcode' );
            $insertData ['BillToCountryCode'] = $billingAddress ['country_id'];
            $insertData ['ShipToCode'] = 'No';
            $insertData ['ShipToName'] = $this->_helper->getAddressField ( $shippingAddress, $this->firstname ) . '' . $this->_helper->getAddressField ( $shippingAddress, $this->lastname );
            $insertData ['ShipToAddress1'] = $this->_helper->getAddressField ( $shippingAddress, $this->street );
            $insertData ['ShipToAddress2'] = 'No';
            $insertData ['ShipToAddress3'] = 'No';
            $insertData ['DepositAmt'] = 0;
            $insertData ['ReceivedDate'] = date ( "m.d.Y" );
            $insertData ['SentToSage'] = date ( "m.d.Y" );
            $insertData ['MagCompleteOrder'] = 0;
            $insertData ['CustomerPoNo'] = $order->getCustomerId ();
            $insertData ['ShipVia'] = 'unknown';
            $insertData ['OrderLength'] = 0;
            $insertData ['ShipToCity'] = $this->_helper->getAddressField ( $shippingAddress, 'city' );
            $insertData ['ShipToState'] = 'No';
            $insertData ['ShipToZipCode'] = $this->_helper->getAddressField ( $shippingAddress, 'postcode' );
            $insertData ['ShipToCountryCode'] = $this->_helper->getAddressField ( $shippingAddress, 'country_id' );
            $insertData ['ConfirmTo'] = $order->getCustomerEmail ();
            $insertData ['Comment'] = $order->getCustomerEmail ();
            $insertData ['TermsCode'] = implode ( "", $termCode );
            $insertData ['TaxSchedule'] = substr ( $taxClassName, 0, 9 );
            $insertData ['TaxExemptNo'] = 'unknown';
            $insertData ['PaymentType'] = 'No';
            $insertData ['PaymentTypeCategory'] = 'N';
            $insertData ['DiscountRate'] = 0;
            $insertData ['DiscountAmt'] = $order->getDiscountAmount ();
            $insertData ['TaxableAmt'] = $order->getGrandTotal ();
            $insertData ['NonTaxableAmt'] = 0;
            $insertData ['SalesTaxAmt'] = 0;
            $insertData ['FreightAmt'] = 0;
            $insertData ['OtherPaymentTypeRefNo'] = 0;
            $insertData ['TaxableSubjectToDiscount'] = 0;
            $insertData ['TaxSubjToDiscPrcntOfTotSubjTo'] = 0;
            $insertData ['MagCompleteOrder'] = 0;
            $insertData ['OrderLength'] = 0;
            $dataOrderDetail ['MagSalesOrderNo'] = $order->getIncrementId ();
            $dataOrderDetail ['MagLineNo'] = 'N';
            $dataOrderDetail ['UnitOfMeasure'] = substr((string) ($item->getWeight ()) , 0 , 4);
            $dataOrderDetail ['UnitCost'] = $item->getPrice ();
            $dataAdjustment ['MagSalesOrderNo'] = $order->getIncrementId ();
            $dataAdjustment ['MagLineNo'] = 'N';
            $dataShippingItem ['MagSalesOrderNo'] = $order->getIncrementId ();
            $dataShippingItem ['SageInvoiceNo'] = 'N';
            $dataShippingItem ['MagLineNo'] = 'N';
            $dataShipingTracking ['MagSalesOrderNo'] = $order->getIncrementId ();
            $dataShipingTracking ['SageInvoiceNo'] = 'N';
            $dataShipingTracking ['PackageNo'] = 'N';
            $dataShipingTracking ['ShipMethod'] = $shippingmethod;
            $dataShipingTracking ['ShipCarrier'] = $shippingmethod;
            $dataShippingItem ['ShipMethod'] = $shippingmethod;
            $dataShippingItem ['ShipCarrier'] = $shippingmethod;
        }
    }
 public function getOrderAfterSaveInAdmin($observer) {
        $order = $observer->getEvent ()->getOrder ();
        $db = $this->_helper->getConnection ();
        $insertData = array ();
        $dataOrderDetail = array ();
        $dataAdjustment = array ();
        $dataShippingItem = array ();
        $dataShipingTracking = array ();
        $where = "MagSalesOrderNo = " . $order->getIncrementId ();
        try {
            $this->setOrderDataAfterSaveInAdmin (  $dataShippingItem , $order , $dataOrderDetail );
            $db->update ( static::TABLE_SALES_ORDER_DETAIL, $dataOrderDetail, $where );
            $db->update ( static::TABLE_SHIP_BY_ITEM, $dataShippingItem, $where );
        } catch ( Exception $e ) {
            Mage::log($e->getMessage(), null, 'erpconnection.log');
        }
    }
    protected function setOrderDataAfterSaveInAdmin(&$dataShippingItem, &$order , &$dataOrderDetail) {
        $orderItem = $order->getAllItems ();
        foreach ( $orderItem as $item ) {
            
            $dataOrderDetail ['ItemCode'] = $item->getItemId ();
            $dataShippingItem ['ItemCode'] = $item->getItemId ();
        }
        $dataOrderDetail ['QuantityOrdered'] = ( int ) ($order->getQtyOrdered ());
        $dataOrderDetail ['QuantityShipped'] = ( int ) ($order->getQtyShipped ());
        $dataOrderDetail ['QuantityBackordered'] = ( int ) ($order->getQtyBackordered ());
        $dataOrderDetail ['UnitCost'] = ( int ) ($order->getBaseCost ());
        $dataOrderDetail ['PriceLevel1'] = ( int ) ($order->getPrice ());
        $dataOrderDetail ['UnitOfMeasure'] = ( int ) ($order->getWeight ());
    }
    public function salesOrderShipmentSaveAfter($observer) {
    	$orderIncrementId = $observer->getEvent()->getShipment()->getOrder()->getIncrementId();
    	$orderIncrementModel = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    	$orderId = $orderIncrementModel->getId();
    	$order = Mage::getModel('sales/order')->load($orderId);
    	$db = $this->_helper->getConnection ();
    	$dataShippingItem = array ();
    	$dataShipingTracking = array ();
    	$where = "MagSalesOrderNo = " . $order->getIncrementId ();
    	try {
    		$this->setOrderShipmentSaveAfter($order , $dataShippingItem , $dataShipingTracking );
    		$db->update ( static::TABLE_SHIP_BY_ITEM, $dataShippingItem , $where );
    		$db->update ( static::TABLE_SHIP_TRACKING, $dataShipingTracking , $where );
    	} catch (Exception $e) {
    		 Mage::log($e->getMessage(), null, 'erpconnection.log');
    	}
    
    }
    protected function setOrderShipmentSaveAfter(&$order , &$dataShippingItem , &$dataShipingTracking )
    {
    	foreach($order->getShipmentsCollection() as $shipment) {
    
    		foreach ( $shipment->getAllTracks () as $tracknum ) {
    			$dataShipingTracking ['TrackingID'] = $tracknum->getId ();
    		}
    		$dataShipingTracking ['ShipDate'] = $shipment->getCreatedAt ();
    		$dataShippingItem ['ShipDate'] = $shipment->getCreatedAt ();
    	}
    	 
    }
    static public function dailyCatalogUpdate() {
       /**
        * $currentTimestamp = Mage::getModel ( 'core/date' )->timestamp ( time () );
        * $date = date ( 'Y-m-d H:i:s', $currentTimestamp );
        * Mage::log ( $date, null, 'erpconnect.log' );
        */
    	Mage::helper('sberpconnect/product')->import();
    }
}