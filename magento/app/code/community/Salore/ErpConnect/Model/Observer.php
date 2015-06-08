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
    	}elseif(is_array($giftcardcode) && count($giftcardcode)) {
    		$giftcard = true;
    		$this->checkGiftCardCode($giftcardcode, $dataOrderDetail);
    	}
    	$dataOrderDetail['MagSalesOrderNo'] = $order->getIncrementId () ;
    	$dataOrderDetail['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
  			
    	foreach ($orderItems as $item) {
    		
			$dataOrderDetail['MagLineNo'] = $item['item_id'];
			$dataOrderDetail ['QuantityOrdered'] = $item['qty_ordered'];
			$dataOrderDetail ['QuantityBackordered'] = $item['qty_backordered'];
			$dataOrderDetail ['LineWeight'] = $item['weight'];
			$productId = $item['product_id'];
			$finalPrice = $item['price'];
			$productCollection = Mage::getModel('catalog/product')->load($productId);
			$productPrice = $productCollection->getPrice();
			if((int)$finalPrice === 0) {
				$dataOrderDetail['DiscountAmt'] = 0;
			}elseif($productPrice > $finalPrice) {
				$dataOrderDetail['DiscountAmt'] = ($productPrice - $finalPrice);
			}elseif($item['discount_amount']){
				$dataOrderDetail['DiscountAmt'] = $item['discount_amount'];
			}else {
				$dataOrderDetail['DiscountAmt'] = $order->getDiscountAmount ();
			}
			if($coupon === true) {
				$this->checkCouponCode($couponCode, $dataOrderDetail, $order , $item);
			}
			$productOption = unserialize($item['product_options']);
			if(isset($productOption['info_buyRequest']) && count($productOption['info_buyRequest']) > 0) {
				$option =  strcmp($productOption['info_buyRequest']['delivery-interval'] , "Monthly");
				if((int) $option === 0) {
					$dataOrderDetail ['DropShip'] = 'Y';
				}else {
					$dataOrderDetail ['DropShip'] = 'N';
				}
			}	
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
    		$dataOrderDetail ['ItemCode'] =  $item['sku'];
    		$dataOrderDetail ['ItemCodeDesc'] = $item['name'];
    		$dataOrderDetail ['ExtensionAmt'] = $item['row_total'];
    }
    protected function prepareDataForTableSalesOrderHeader(&$dataOrderHeader , $quote , $order ) {
    	$shippingmethod = $quote->getShippingAddress ()->getShippingMethod ();
    	$cartItems = $quote->getAllVisibleItems ();
    	foreach($cartItems as $item) {
    		$productId = $item->getProductId ();
    		Mage::getSingleton('core/session')->setProductId($productId);
    		$product = Mage::getModel ( 'catalog/product' )->load ( $productId );
    		$taxClassId = $product->getTaxClassId ();
    		$billingAddress = $order->getBillingAddress ();
			$customerGroupId = $order->getCustomerGroupId();
			$customerTaxId = Mage::getModel('customer/group')->load($customerGroupId)->getTaxClassId();
			$tax = Mage::getModel('tax/calculation');
			$taxClassId= $tax->load($customerTaxId, 'customer_tax_class_id')->getCustomerTaxClassId();
			$taxClass = Mage::getModel('tax/class')->load($taxClassId);
			$taxCodeName = $taxClass->getOpAvataxCode();
			if(isset($billingAddress) && $billingAddress ) {
					$billingAddress = $order->getBillingAddress ()->getData ();
				$regionId =  $this->_helper->getAddressField ( $billingAddress, 'region_id' );
				$regionCode =  Mage::getModel('directory/region')->load($regionId)->getCode();
			}
    		$shippingAddress = $order->getShippingAddress ();
    		if(isset($shippingAddress) && $shippingAddress ) {
    			$shippingAddress = $order->getShippingAddress ()->getData ();
			$regionId =  $this->_helper->getAddressField (  $shippingAddress, 'region_id' );
			$regionCode =  Mage::getModel('directory/region')->load($regionId)->getCode();
    		}
			$dataOrderHeader['MagSalesOrderNo'] = $order->getIncrementId ();
    		$dataOrderHeader['SalesOrderNo'] =  $this->_helper->prefixOrderNo($order->getId());
			$dataOrderHeader['OrderDate'] = $this->_helper->formatDate($order->getCreatedAt ());
    		$dataOrderHeader['CustomerNo'] = $order->getCustomerId () ? $order->getCustomerId()  : ""  ;
    		$dataOrderHeader['BillToName'] = $this->_helper->getAddressField ( $billingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $billingAddress, $this->lastname );
    		$dataOrderHeader['BillToAddress1'] =  $this->_helper->getAddressField ( $billingAddress, $this->street );
    		$dataOrderHeader['BillToCity'] = $this->_helper->getAddressField ( $billingAddress, 'city' );
    		$dataOrderHeader['BillToState'] = $regionCode;
    		$dataOrderHeader['BillToZipCode'] = $this->_helper->getAddressField ( $billingAddress, 'postcode' );
    		$dataOrderHeader['BillToCountryCode'] =  $billingAddress ['country_id'];
    		$dataOrderHeader['ShipToName'] = $this->_helper->getAddressField ( $shippingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $shippingAddress, $this->lastname );
    		$dataOrderHeader['ShipToAddress1'] = $this->_helper->getAddressField ( $shippingAddress, $this->street );
    		$dataOrderHeader['ShipToCity'] = $this->_helper->getAddressField ( $shippingAddress, 'city' );
    		$dataOrderHeader['ShipToState'] =  $regionCode;
    		$dataOrderHeader['ShipToZipCode'] = $this->_helper->getAddressField ( $shippingAddress, 'postcode' );
    		$dataOrderHeader['ShipToCountryCode'] = $this->_helper->getAddressField ( $shippingAddress, 'country_id' ); 
    		$dataOrderHeader ['ConfirmTo'] = $this->_helper->getAddressField ( $billingAddress, $this->firstname ) . ' ' . $this->_helper->getAddressField ( $billingAddress, $this->lastname );
    		// still not sure assign it to default "NA"
    		$dataOrderHeader ['EmailAddress'] = $order->getCustomerEmail ();
    		$grandTotal = $order->getGrandTotal ();
    		$taxAmount  = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
    		  if( $taxAmount > 0 ) {
                                 $dataOrderHeader ['TaxSchedule'] = 'AVATAX' ;
                        } else {
                                 $dataOrderHeader ['TaxSchedule'] = 'NA' ;
            }
			$taxablAmt =  strcmp($dataOrderHeader['TaxSchedule'] , "AVATAX");
            $nontaxbAmt = strcmp($dataOrderHeader['TaxSchedule'] , "NA");
            if((int) $taxablAmt === 0) {
                    $dataOrderHeader['TaxableAmt'] =  Mage::helper('core')->formatPrice(($grandTotal - $taxAmount), false);
            } else {
                    $dataOrderHeader['TaxableAmt'] =  0;
            }
            if((int) $nontaxbAmt === 0) {
                    $dataOrderHeader['NonTaxableAmt'] =  $grandTotal ;
            } else {
                        $dataOrderHeader['NonTaxableAmt'] =  0 ;
            }

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
    protected function checkCouponCode($couponCode , &$dataOrderDetail , $order , &$item) {
    	$currencyAmt = $order->getRwrdCurrencyAmountInvoiced();
    	$qty = $item['qty_ordered'];		
    	$orderId = $order->getId();
    	$productCollection = Mage::getModel('catalog/product')->load($item['product_id']);
    	$price = $item['price'];	     
	if(empty($couponCode)) {
    		return;
    	}  else {
    		$couponPregMatch = (int)preg_match('/^ECENTER[0-9]{1,}$/',strtoupper($couponCode));
    		if(($couponPregMatch == 1) && (is_null($currencyAmt))) {
    			if((int) $price === 0 ) {
					$dataOrderDetail ['Discount']   = '';
					$dataOrderDetail ['ItemCode'] = $productCollection->getSku();
            	    $dataOrderDetail ['UnitOfMeasure'] = "EACH";
                    $dataOrderDetail ['ItemCodeDesc'] = $item['name'];
					$dataOrderDetail ['ExtensionAmt'] = ($qty * $price);

			}else {
					$dataOrderDetail ['ItemCode'] = $productCollection->getSku();
    				$dataOrderDetail ['UnitOfMeasure'] = "EACH";
    				$dataOrderDetail ['ItemCodeDesc'] = $item['name'];
    				$dataOrderDetail ['Discount']   = $couponCode;
    				$dataOrderDetail ['ExtensionAmt'] = ($qty * $price);
			}
    		}
    		if(($couponPregMatch ==0) && (is_null($currencyAmt))) {
		 	if((int) $price === 0 ) {
                                        $dataOrderDetail ['Discount']   = '';
                                        $dataOrderDetail ['ItemCode'] = $productCollection->getSku();
                                        $dataOrderDetail ['UnitOfMeasure'] = "EACH";
                                        $dataOrderDetail ['ItemCodeDesc'] = $item['name'];
                                        $dataOrderDetail ['ExtensionAmt'] = ($qty * $price);
                        }else {

    			$dataOrderDetail ['ItemCode'] = $productCollection->getSku();
    			$dataOrderDetail ['UnitOfMeasure'] = 'EACH';
    			$dataOrderDetail ['Discount']   = $couponCode;
    			$dataOrderDetail ['ItemCodeDesc'] = $item['name'];
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * $price);
    		}
		}
    		if(isset($currencyAmt) && $currencyAmt > 0) {
    			$dataOrderDetail ['ItemCode'] = $productCollection->getSku();
    			$dataOrderDetail ['UnitOfMeasure'] = "EACH";
			$dataOrderDetail ['Discount'] = "/REWARDS POINTS";
    			$dataOrderDetail ['ItemCodeDesc'] = /* "Rewards Points"; */$item['name'];
    			$dataOrderDetail ['ExtensionAmt'] = ($qty * $price);
    		}
    	}
    }
    protected function checkGiftCardCode($giftcardcode , &$dataOrderDetail) {
    	foreach ($giftcardcode as $card ) {
    		if(isset( $card['c']) &&  $card['c']) {
    			$dataOrderDetail ['ItemCode'] = "/GIFT CARD";
    			$dataOrderDetail ['UnitOfMeasure'] = "EACH";
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
