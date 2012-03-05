<html>
	<head>
    	<title>GoPay - testovací skript</title>
    	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>
	<body>
	
		Pokud se u kterékoliv položky zobrazuje stav "CHYBA", zašlete nám kompletní výpis či přímo printscreen obrazovky.
		<br><br>
  		<?php

  		$goid = !empty($_POST["goid"]) ? $_POST["goid"] : null;
  		$secret = !empty($_POST["secret"]) ? $_POST["secret"] : null;

  		$admin = !empty($_GET["admin"]) ? $_GET["admin"] : false;
  		GopayTester::setAdmin($admin);

		GopayTester::runTest($goid, $secret);
  		?>
  		<br><br>
  		
  		Testovací platba
  		<form method="POST">
			GoID: <input type="text" name="goid"><br>
			Secret: <input type="text" name="secret"><br>
			<input type="submit" name="testCreatePayment" value="Vytvořit platbu">
  		</form>
  		<br><br>

  		<?php
  		GopayTester::showPhpinfo()
  		?>
	</body>
</html>

<?php
class GopayTester {

	static $admin = false;

	const test_wsdl_ssl    = "https://testgw.gopay.cz/axis/EPaymentService?wsdl"; // URL na testovaci WSDL na SSL kanale
	const test_wsdl_nonssl = "http://testgw.gopay.cz/axis/EPaymentService?wsdl"; // URL na testovaci WSDL bez SSL kanalu
	const prod_wsdl_ssl    = "https://gate.gopay.cz/axis/EPaymentService?wsdl"; // URL na provozni WSDL
	const prod_wsdl_nonssl = "http://gate.gopay.cz/axis/EPaymentService?wsdl"; // URL na provozni WSDL

	public static function runTest($goid, $secret) {

		if (self::$admin == true) {
			error_reporting(E_ALL|E_STRICT);
			ini_set('display_errors', 1);
		}

		ini_set('soap.wsdl_cache', '0');
		ini_set('soap.wsdl_cache_enabled', '0'); 
		ini_set('date.timezone', 'Europe/Prague');

		$soapOK = 1;
		$sslOK = 1;
		
		echo "Modul mcrypt: ";
		echo self::testMcrypt() == 0 ? self::koResult() : self::okResult();

		echo "Modul mhash / sha1: ";
		echo self::testMhash() == 0 ? self::koResult() : self::okResult();

		echo "Modul SOAP: ";
		if (self::testSOAP() == 0) {
			echo self::koResult();
			$soapOK = 0;
		} else {
			echo self::okResult();
		}

		if ($soapOK == 1) {
			
			echo "<br>Kontrola dostupnosti WS<br>";
			self::wsdlAvailibility(self::prod_wsdl_nonssl);

			echo "<br>Kontrola dostupnosti WS - SSL<br>";
			self::wsdlAvailibility(self::prod_wsdl_ssl);
			
			echo "<br>";
			
			echo "Test volání metody WSDL: ";
			echo self::callWsdlFunction() == 0 ? self::koResult() : self::okResult();
	
			echo "Modul OpenSSL: ";
			if (self::testOpenSSL() == 0) {
				echo self::koResult();
				$sslOK = 0;
			} else {
				echo self::okResult();
			}
	
			if ($sslOK == 1) {
				$result = self::testTestCertificate();
				if ($result == 0) {
					echo "Komunikace s Test certifikátem: ".self::koResult();
				} else if ($result == 1) {
					echo "Komunikace s Test certifikátem: ".self::okResult();
				} else {
					echo "Chyba SOAPu při testování certifikátu";
				}
		  		
		  		if (!empty($goid) && !empty($secret)) {
		  			echo self::testCreatePayment($_POST["goid"], $_POST["secret"]);
		  		}
			}
		}
		
	}
	
	private static function okResult() {
		return "<span style='color:green'>OK</span><br>";
	}
	
	private static function koResult() {
		return "<span style='color:red'>CHYBA</span><br>";
	}

	// kontrola PHP modulu mcrypt
	private static function testMcrypt() {
		return (function_exists("mcrypt_module_open")!=true) ? 0 : 1;
	}
	
	// kontrola PHP modulu mhash / sha1
	private static function testMhash() {
		return (function_exists("sha1")!=true && function_exists("mhash")!=true) ? 0 : 1;
	}

	// kontrola PHP modulu SOAP
	private static function testSOAP() {
		return (function_exists("is_soap_fault")!=true) ? 0 : 1;
	}
	
	// kontrola volani WSDL metod
	private static function callWsdlFunction() {
		try{
			$go_client = new SoapClient(self::test_wsdl_nonssl);
			$go_client->__call('touch', array());
			return 1;

		} catch (SoapFault $f){
			return 0;
		}
	}
	
	// kontrola PHP modulu OpenSSL
	private static function testOpenSSL() {
		try {
			$client = new SoapClient(self::prod_wsdl_ssl);
			return 1;

		} catch (SoapFault $f){
			return 0;
		}
	}
	
	// test komunikace se SSL certifikatem na test prostredi
	private static function testTestCertificate() {
		try {
			$client = new SoapClient(self::test_wsdl_ssl);
			return 1;

		} catch (SoapFault $f) {
			
			try {
				$client = new SoapClient(self::test_wsdl_nonssl);
				return 0;
			
			} catch (SoapFault $f) {
				return 2;
			}
		}
	}
	
	// vytvoreni pokusne platby - kontrola intepretace parametru
	private static function testCreatePayment($goid, $secret) {
		$result = "<br><br><br>";
		
		try {
			$totalPrice = 100;
			$variableSymbol = "gopay_test_".$goid;
			$productName = "productName";
			$failedURL = "http://www.failed_url.cz";
			$successURL = "http://www.success_url.cz";

			$encryptedSignature = self::encrypt(
					self::hash(
							self::concatPaymentCommand(
											(float)$goid,
											$productName, 
											(float)$totalPrice,
											$variableSymbol,
											$failedURL,
											$successURL,
											$secret)
									),
									$secret);

			$payment_command = array(
						"eshopGoId" => (float)$goid,
						"productName" => trim($productName),
						"totalPrice" => (float)$totalPrice,
						"variableSymbol" => trim($variableSymbol),
						"successURL" => trim($successURL),
						"failedURL" => trim($failedURL),
						"encryptedSignature" => $encryptedSignature
		     );

			$go_client = new SoapClient(self::test_wsdl_ssl, array());
			$payment_status = $go_client->__call('createPaymentSession', array('paymentCommand'=>$payment_command));
			
			$result .= "Vytváření platby: ".self::okResult()."<br>";
			$result .= "paymentSessionId = ".$payment_status->paymentSessionId."<br>";
			$result .= "eshopGoId = ".$payment_status->eshopGoId."<br>";
			$result .= "productName = ".$payment_status->productName."<br>";
			$result .= "totalPrice = ".$payment_status->totalPrice."<br>";
			$result .= "variableSymbol = ".$payment_status->variableSymbol."<br>";
			$result .= "encryptedSignature = ".$payment_status->encryptedSignature."<br>";
			$result .= "result = ".$payment_status->result."<br>";
			$result .= "sessionState = ".$payment_status->sessionState."<br>";
			$result .= "resultDescription = ".$payment_status->resultDescription."<br>";

		} catch (SoapFault $f) {
			$result .= "Vytváření platby: ".self::koResult()."<br>";
			$result .= print_r($f, true);
		}
		
		return $result;
	}
	
	private static function encrypt($data, $secret) {
  		$td = mcrypt_module_open (MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        $mcrypt_iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init ($td, substr($secret, 0, mcrypt_enc_get_key_size($td)), $mcrypt_iv);
        $encrypted_data = mcrypt_generic ($td, $data);
        mcrypt_generic_deinit ($td);
        mcrypt_module_close ($td);

        return bin2hex($encrypted_data);
  	}

	private static function hash($data) {
  		if (function_exists("sha1") == true) {
  			$hash = sha1($data, true);

  		} else {
  			$hash = mhash(MHASH_SHA1, $data);
  		}

  		return bin2hex($hash);   		
  	}

	private static function concatPaymentCommand(
  		$goId,
  		$productName, 
  		$totalPriceInCents, 
  		$variableSymbol,
  		$failedURL,
  		$successURL, 
  		$secret) {

        return $goId."|".trim($productName)."|".$totalPriceInCents."|".trim($variableSymbol)."|".trim($failedURL)."|".trim($successURL)."|".$secret; 
  	}
	
	public static function setAdmin($new_admin) {
		self::$admin = $new_admin;
	}
	
	private static function wsdlAvailibility($wsdl_url) {
		if (function_exists("curl_init") == true) {
		
			$tuCurl = curl_init(); 
			curl_setopt($tuCurl, CURLOPT_URL, $wsdl_url);
			curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, 2);
			curl_setopt($tuCurl, CURLOPT_VERBOSE, 1);
		
			$tuData = curl_exec($tuCurl); 
	
			echo "Stažení definice WS pomocí CURL - ";
			echo ($tuData == true) ? self::okResult() : self::koResult();
	
			$headers = curl_getinfo($tuCurl);
			echo "CURL - HEADERS WS - ";
			echo ($headers["content_type"] != null) ? self::okResult() : self::koResult();
			if (self::$admin == true) {
				var_dump($headers);
				echo "<br>";
			}
	
			curl_close($tuCurl);
		
		} else {
			echo "CURL není přítomen<br> ";
			
		}
	}
	
	public static function showPhpinfo() {
		if (self::$admin == true) {
			phpinfo();
		}
		
	}
}
?>