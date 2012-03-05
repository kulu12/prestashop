<?php
include_once(dirname(__FILE__).'/../../config/config.inc.php');

class GopayConfig {
	
	/**
	 *  Konfiguracni trida pro ziskavani URL pro praci s platbami
	 *  
	 */

	const TEST = "https://testgw.gopay.cz/zaplatit-plna-integrace";
	const PROD = "https://gate.gopay.cz/zaplatit-plna-integrace";
	
	/**
	 * URL platebni brany pro uplnou integraci
	 *
	 * @return URL
	 */
	public static function fullIntegrationURL() {
		
		if (trim(Configuration::get('GOPAY_GW_URL')) == self::PROD) {
			return 'https://gate.gopay.cz/zaplatit-plna-integrace';		
			
		} else {
			return 'https://testgw.gopay.cz/zaplatit-plna-integrace';		
			
		}
	}

	/**
	 * URL webove sluzby GoPay
	 *
	 * @return URL - wsdl
	 */
	public static function ws() {
		
		if (trim(Configuration::get('GOPAY_GW_URL')) == self::PROD) {
			return 'https://gate.gopay.cz/axis/EPaymentService?wsdl';		
			
		} else {
			return 'https://testgw.gopay.cz/axis/EPaymentService?wsdl';	
			
		}
	}		

}
?>