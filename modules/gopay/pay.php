<?php

include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/gopay.php');
include_once(dirname(__FILE__).'/gopay_tools.php');
include_once(dirname(__FILE__).'/country_code.php');

$gpErrors = '';

$gopay = new Gopay();
$gopayTools = new GopayTools();
					
// parametry zadane v konfiguraci
$goId = trim(Configuration::get('GOID'));
$gopaySecret = trim(Configuration::get('GOPAY_SECRET'));
$failedUrl = trim(Configuration::get('GOPAY_FAILED_URL'));
$successUrl = trim(Configuration::get('GOPAY_SUCCESS_URL'));
$gwUrl = trim(Configuration::get('GOPAY_GW_URL'));
$infopageUrl = trim(Configuration::get('GOPAY_INFOPAGE_URL'));

// redirect param
$param = $_POST['param'];

// cart z parametru
$cartId = $_POST['cartId'];
$cart = new Cart($cartId);

$paymentChannels = Array();
				
foreach($_POST as $key) {
	if (substr($key, 0, 13) == "method_gopay_") {
		 $paymentChannels[] = substr($key, 13);
	}
}

// castka predavana validateOrder
$cartSummary = $cart->getSummaryDetails();
$nonConvertedAmount = $cartSummary['total_price'];
	
if ($cart->OrderExists()) {
	$gpErrors = 'alreadyClosed';
	Tools::redirectLink("$infopageUrl?gp_errors=$gpErrors");			
}
		
// ziskani id ceske meny
$czk_id = Currency::getIdByIsoCode('CZK');

// overeni existence ceske meny s ISO CZK
if ($czk_id == '') {
	$gpErrors = 'czk';
	Tools::redirectLink("$infopageUrl?gp_errors=$gpErrors");

} else {
	// cart v CZK
	$cart->id_currency = intval($czk_id);
	$cart->save();
	
	// vytvoreni a nacteni nove objednavky			
	$gopay->validateOrder($cart->id, _PS_OS_GOPAY_, $nonConvertedAmount, $gopay->displayName, null, array(), $cart->id_currency, false, $cart->secure_key);
	
	$orderId = Order::getOrderByCartId($cart->id);
	$order = new Order($orderId);
	
	$productNameConcat = $gopayTools->concatProductsNames($order);
	$amount = round($order->total_paid * 100);

	$successUrl = trim(Configuration::get('GOPAY_SUCCESS_URL'));
	
	$customerId = $order->id_customer;
	$customer = new Customer($customerId);
	
	$addressId = $order->id_address_invoice;
	$address = new Address($addressId);
	
	// customerData
	$customerData = trim(Configuration::get('GOPAY_CUSTOMER_DATA'));
	
	if ($customerData == '1') {
		$firstName = $customer->firstname;
		$lastName = $customer->lastname;
		$city = $address->city;
		$street = $address->address1;
		$postalCode = $address->postcode;
		$email = $customer->email;
		$phoneNumber = $address->phone;
		
		$countryId = $address->id_country;
		$country = new Country($countryId);
		
		$convertedCountryCode = GopayTools::getConvertedCountryCode($country->iso_code);
		
	}
	
	if (isset($order)) {
		// vytvoreni platby
		$paymentSessionId = GopaySoap::createCustomerEshopPayment($goId,
																$productNameConcat, 
																$amount,
																$orderId,
																$successUrl,
																$failedUrl,
																$gopaySecret,
																$paymentChannels,
																$firstName,
																$lastName,
																$city,
																$street,
																$postalCode,
																$convertedCountryCode,
																$email,
																$phoneNumber							
																);
							
		if ($paymentSessionId > 0) {
			$encryptedSignature = GopayHelper::encrypt(
										GopayHelper::hash(
											GopayHelper::concatPaymentSession(
													$goId,
													$paymentSessionId,
													$gopaySecret
												)
										),
										$gopaySecret);

		} else {
			$gpErrors = 'paymentCreationFailed';
		}

	} else {
		$gpErrors = 'undefinedOrderFaultyState';
	}																
	
	if (empty($gpErrors)) {
		$redirectUrl = "$gwUrl?sessionInfo.paymentSessionId=$paymentSessionId&sessionInfo.eshopGoId=$goId&sessionInfo.encryptedSignature=$encryptedSignature";
		if (isset($param)) {
			$redirectUrl .= "$param";	
		}
		Tools::redirectLink($redirectUrl);
					
	} else {
		Tools::redirectLink("$infopageUrl?gp_errors=$gpErrors");
	}
}
?>