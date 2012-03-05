<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/gopay.php');

require_once(_PS_MODULE_DIR_.'gopay/gopay_tools.php');

$gpErrors = '';
$gopayTools = new GopayTools();
		
$returnedPaymentSessionId = $_GET['paymentSessionId'];
$returnedGoId = $_GET['eshopGoId'];
$returnedOrderId = $_GET['variableSymbol'];
$returnedEncryptedSignature = $_GET['encryptedSignature'];
		
$goId = trim(Configuration::get('GOID'));
$gopaySecret = trim(Configuration::get('GOPAY_SECRET'));
$infopageUrl = trim(Configuration::get('GOPAY_INFOPAGE_URL'));
		
$order = new Order($returnedOrderId);
		
if (isset($order->id)) {
	
	$amount = round($order->total_paid * 100);
	$productNameConcat = $gopayTools->concatProductsNames($order);
				
	if(GopayHelper::checkPaymentIdentity(
		$returnedGoId,
		$returnedPaymentSessionId,
		$returnedOrderId,
		$returnedEncryptedSignature,
		$goId,
		$order->id,
		$gopaySecret)) 
	{

	$result = GopaySoap::isEshopPaymentDone(
		$returnedPaymentSessionId,
		$goId,
		$order->id,
		$amount,
		$productNameConcat,
		$gopaySecret);
		
		$gpErrors = $gopayTools->processPayment($result, $order->id);

	} else {
		$gpErrors = 'faultyPaymentIdentity';
	}

} else {
	$gpErrors = 'undefinedOrderFaultyState';
}
						
if (empty($gpErrors)) {
	$redirectParams = "paymentState=done";
} 
else {
	$redirectParams = "gp_errors=".$gpErrors;
}

Tools::redirectLink($infopageUrl . "?" . $redirectParams);
		
?>