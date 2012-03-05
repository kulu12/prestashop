<?php

/**
  * Payment module class, Gopay.php
  
  * @category modules
  *
  * @author Gopay <integrace@gopay.cz>
  * @version 1.9
  *
  */

$orderStateId = Db::getInstance()->getValue('
SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_state_lang` WHERE `name` = "Čeká na platbu GoPay"');
if(isset($orderStateId)) {
	define('_PS_OS_GOPAY_', $orderStateId);
	}
	
require_once(_PS_MODULE_DIR_.'gopay/gopay_soap.php');
require_once(_PS_MODULE_DIR_.'gopay/gopay_config.php');

class Gopay extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'gopay';
		$this->tab = 'payments_gateways';
		$this->version = '1.9';
		
		$this->currencies = true;
		$this->currencies_mode = 'radio';

        parent::__construct();

		$this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('GoPay');
        $this->description = $this->l('Platí GoPay');
		$this->confirmUninstall = $this->l('Opravdu chcete smazat platební modul GoPay?');
		
	}
	
	public function install()
	{
	
		$serverURL = 'http';
 		
		if ($_SERVER["HTTPS"] == "on") {$serverURL .= "s";}
 		$serverURL .= "://";
 		if ($_SERVER["SERVER_PORT"] != "80") {
  			$serverURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
 		}
 		else {
 			$serverURL .= $_SERVER["SERVER_NAME"];
 		}
 		
 		$paymentMethodList = GopaySoap::paymentMethodList();
 		
		for ($i = 0; $i < count($paymentMethodList); $i++) {
			Configuration::updateValue($paymentMethodList[$i]->code, '0');
		}
 		
 		if (!parent::install()
			
			OR !Configuration::updateValue('GOID', '1234567890')
			OR !Configuration::updateValue('GOPAY_SECRET', 'abcdefgh12345678abcdefgh')
			OR !Configuration::updateValue('GOPAY_SUCCESS_URL', $serverURL.__PS_BASE_URI__.'modules/gopay/validation.php')
			OR !Configuration::updateValue('GOPAY_FAILED_URL', $serverURL.__PS_BASE_URI__.'?gp_errors=canceled')
			OR !Configuration::updateValue('GOPAY_GW_URL', 'https://testgw.gopay.cz/zaplatit-plna-integrace')
			OR !Configuration::updateValue('GOPAY_WS_URL', 'https://testgw.gopay.cz/axis/EPaymentService?wsdl')
			OR !Configuration::updateValue('GOPAY_INFOPAGE_URL', $serverURL.__PS_BASE_URI__)
			OR !Configuration::updateValue('GOPAY_PAY_MODE', 'single')
			OR !Configuration::updateValue('GOPAY_PRECONF_METHOD', '')
			OR !Configuration::updateValue('GOPAY_CUSTOMER_DATA', '0')
												
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn')
			OR !$this->registerHook('leftColumn')
			OR !$this->registerHook('home'))
						
		return false;
	
		$langQ = 'SELECT `id_lang` FROM `'._DB_PREFIX_.'lang`';
		$langResult = Db::getInstance()->ExecuteS($langQ);
			
		$orderStateId = Db::getInstance()->getValue('
		SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_state_lang` WHERE `name` = "Čeká na platbu GoPay"');
		
		if (empty($orderStateId)) {
			// vytvoreni noveho stavu awaiting GoPay
			Db::getInstance()->Execute(
	        'INSERT INTO `'._DB_PREFIX_.'order_state` (`invoice`, `send_email`, `color`, `unremovable`, `hidden`, `logable`, `delivery`)
	        VALUES ("0","1","#80b1ed","1","0","1","0");');
			
			$maxStateValue = Db::getInstance()->Insert_ID();
							
			foreach ($langResult as $r) {
				Db::getInstance()->Execute(
		        'INSERT INTO `'._DB_PREFIX_.'order_state_lang` (`id_order_state`, `id_lang`, `name`, `template`)
		        VALUES ('.$maxStateValue.','.$r['id_lang'].',"Čeká na platbu GoPay","gopay");');
			}
		}
		
		// pridani noveho cms zaznamu - informace o GoPay
		Db::getInstance()->Execute(
        'INSERT INTO `'._DB_PREFIX_.'cms` (`active`) VALUES("1");');
			
		$maxCmsValue = Db::getInstance()->Insert_ID();
				
		$logoUrl = $serverURL.__PS_BASE_URI__.'modules/gopay/images/logo_header.gif';
		
		foreach ($langResult as $r) {
			Db::getInstance()->Execute(
	        'INSERT INTO `'._DB_PREFIX_.'cms_lang` (`id_cms`, `id_lang`, `meta_title`, `meta_description`, `meta_keywords`, `content`, `link_rewrite`)
	        VALUES ('.$maxCmsValue.', '.$r['id_lang'].',"GoPay","Mikroplatební systém GoPay","gopay, payment, secure payment, ssl",
	        "<div align=\"right\"><a href=\"https://www.gopay.cz\" target=\"_blank\"><img src=\"'.$logoUrl.'\" alt=\"GoPay\" style=\"margin-right: 20px;\" /></a></div><h2>Preferujeme GoPay</h2><br />
	        <p align=\"justify\">GoPay je český, moderní, rychlý, internetový platební systém s vysokou úrovní bezpečnosti. Nabízí platby prostřednictvím elektronické peněženky a veškerých nejpopulárnějších platebních metod na českém internetu v rámci jediného uživatelského rozhraní. GoPay je partnerem vaší platby se zárukou důvěryhodného a bezpečného platebního prostředí.</p><br />
	        <h3>S GoPay peněženkou získáte výhody</h3>
	        <p align=\"justify\">GoPay peněženka nabízí elektronickou variantu běžné peněženky pro drobné každodenní platby. Už žádné vyplňování osobních údajů, žádné skryté poplatky, s GoPay peněženkou párkrát kliknete a můžete ihned nakupovat. Rychle. Snadno. Zdarma. Kdykoliv. Vyzkoušejte GoPay peněženku na <a href=\"https://www.gopay.cz\" target=\"_blank\">https://www.gopay.cz</a></p><br />
	        <h4>Hlavní výhody GoPay peněženky:</h4>
	        <ul><li>založení a užívání zdarma</li><li>registrace bez předávání citlivých údajů a čísla bankovního účtu</li><li>rychlé dobíjení a okamžité platby</li><li>platby na libovolný bankovní účet</li><li>ideální pro platby malých finančních částek</li><li>ideální jako internetová kasička pro nejmladší uživatele</li><li>splňuje všechny bezpečnostní standardy</li></ul><br />
	        <h4>Jak snadno platit s GoPay</h4>
	        <p><b>1. Vyberte si zboží/službu</b></p>
	        <p>Vyberte si na stránkách internetového obchodu zboží/službu, o kterou máte zájem.</p>
	        <p><b>2. Potvrďte objednávku</b></p>
	        <p>Na straně internetového obchodu vyplňte údaje a potvrďte objednávku vybraného zboží/služby.</p>
	        <p><b>3. Volba platební metody</b></p>
	        <p>Otevře se okno platební brány GoPay, kde zvolte záložku preferované platební metody.</p>
	        <p><b>4. Proveďte platbu</b></p>
	        <p>Na platební bráně GoPay postupujte dle instrukcí dle zvolené platební metody. Po provedení platby dojde k přesměrování zpět na stránky internetového obchodu.</p>
	        <p><b>5. Vyřízení objednávky</b></p>
	        <p>Platba je automaticky provedena a objednávka vybraného zboží/služby ze strany internetového obchodu ihned vyřízena.</p><br />
	        <a href=\"https://www.gopay.cz\" target=\"_blank\">https://www.gopay.cz</a>",
	        "gopay");');
		}
						
		return true;
	}
		
	public function uninstall()
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_state` WHERE `id_order_state` = '.$orderStateId.'');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'order_state_lang` WHERE `id_order_state` = '.$orderStateId.'');
		
		$cmsId = Db::getInstance()->getValue('
		SELECT `id_cms` FROM `'._DB_PREFIX_.'cms_lang` WHERE `link_rewrite` = "gopay"');
		
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'cms` WHERE `id_cms` = '.$cmsId.'');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'cms_lang` WHERE `id_cms` = '.$cmsId.'');
		
		$paymentMethodList = GopaySoap::paymentMethodList();
		
		for ($i = 0; $i < count($paymentMethodList); $i++) {
			Configuration::deleteByName($paymentMethodList[$i]->code);
		}
		
		if (!Configuration::deleteByName('GOID')
			OR !Configuration::deleteByName('GOPAY_SECRET')
			OR !Configuration::deleteByName('GOPAY_SUCCESS_URL')
			OR !Configuration::deleteByName('GOPAY_FAILED_URL')
			OR !Configuration::deleteByName('GOPAY_GW_URL')
			OR !Configuration::deleteByName('GOPAY_WS_URL')
			OR !Configuration::deleteByName('GOPAY_INFOPAGE_URL')
			OR !Configuration::deleteByName('GOPAY_PAY_MODE')
			OR !Configuration::deleteByName('GOPAY_PRECONF_METHOD')
			OR !Configuration::deleteByName('GOPAY_CUSTOMER_DATA')
												
			OR !parent::uninstall())
											
			return false;
		return true;
	}

	public function getContent()
	{
		if (isset($_POST['submitGopay']))
		{
			if (empty($_POST['goId']))
				$this->_postErrors[] = $this->l('EshopGoID je povinná položka.');
			elseif (empty($_POST['gopaySecret']))
				$this->_postErrors[] = $this->l('Secret je povinná položka.');
			elseif (empty($_POST['infopageUrl']))
				$this->_postErrors[] = $this->l('Info URL je povinná položka.');
			elseif (!Validate::isUrl($_POST['infopageUrl']))
				$this->_postErrors[] = $this->l('Špatný formát Info URL.');
			
				
			if (!sizeof($this->_postErrors))
			{
				Configuration::updateValue('GOID', strval($_POST['goId']));
				Configuration::updateValue('GOPAY_SECRET', strval($_POST['gopaySecret']));
				Configuration::updateValue('GOPAY_SUCCESS_URL', strval($_POST['successUrl']));
				Configuration::updateValue('GOPAY_FAILED_URL', strval($_POST['failedUrl']));
				Configuration::updateValue('GOPAY_GW_URL', strval($_POST['gwUrl']));
				Configuration::updateValue('GOPAY_WS_URL', strval($_POST['wsUrl']));
				Configuration::updateValue('GOPAY_INFOPAGE_URL', strval($_POST['infopageUrl']));
				Configuration::updateValue('GOPAY_PAY_MODE', strval($_POST['payMode']));
				Configuration::updateValue('GOPAY_PRECONF_METHOD', strval($_POST['preconfMethod']));
				Configuration::updateValue('GOPAY_CUSTOMER_DATA', strval($_POST['customerData']));
				
				$paymentMethodList = GopaySoap::paymentMethodList();
								
				for ($i = 0; $i < count($paymentMethodList); $i++) {
					Configuration::updateValue($paymentMethodList[$i]->code, $_POST[$paymentMethodList[$i]->code]);
				}

				$serverURL = 'http';
 				if (isset($_SERVER["HTTPS"])) {
 					if ($_SERVER["HTTPS"] == "on") {$serverURL .= "s";}
 				}
 				
 				$serverURL .= "://";

 				if ($_SERVER["SERVER_PORT"] != "80") {
  					$serverURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
 				}
 				else {
 					$serverURL .= $_SERVER["SERVER_NAME"];
 				}
 		
 				Configuration::updateValue('GOPAY_SUCCESS_URL', $serverURL.__PS_BASE_URI__.'modules/gopay/validation.php');
				Configuration::updateValue('GOPAY_FAILED_URL', $serverURL.__PS_BASE_URI__.'?gp_errors=canceled');
				Configuration::updateValue('GOPAY_INFOPAGE_URL', $serverURL.__PS_BASE_URI__);
											
				$this->displayConf();
			}
			else
				$this->displayErrors();
		}

		$this->displayFormSettings();
		return $this->_html;
	}

	public function displayConf()
	{
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Konfigurace byla uložena').'
		</div>';
	}
	
	public function displayErrors()
	{
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}
			
	public function displayFormSettings()
	{
		$conf = Configuration::getMultiple(array('GOID', 'GOPAY_SECRET','GOPAY_SUCCESS_URL','GOPAY_FAILED_URL','GOPAY_GW_URL','GOPAY_WS_URL','GOPAY_INFOPAGE_URL','GOPAY_PAY_MODE','GOPAY_PRECONF_METHOD','GOPAY_CUSTOMER_DATA'));
		$goId = array_key_exists('goId', $_POST) ? $_POST['goId'] : (array_key_exists('GOID', $conf) ? $conf['GOID'] : '');
		$gopaySecret = array_key_exists('gopaySecret', $_POST) ? $_POST['gopaySecret'] : (array_key_exists('GOPAY_SECRET', $conf) ? $conf['GOPAY_SECRET'] : '');
		$successUrl = array_key_exists('successUrl', $_POST) ? $_POST['successUrl'] : (array_key_exists('GOPAY_SUCCESS_URL', $conf) ? $conf['GOPAY_SUCCESS_URL'] : '');
		$failedUrl = array_key_exists('failedUrl', $_POST) ? $_POST['failedUrl'] : (array_key_exists('GOPAY_FAILED_URL', $conf) ? $conf['GOPAY_FAILED_URL'] : '');
		$gwUrl = array_key_exists('gwUrl', $_POST) ? $_POST['gwUrl'] : (array_key_exists('GOPAY_GW_URL', $conf) ? $conf['GOPAY_GW_URL'] : '');
		$infopageUrl = array_key_exists('infopageUrl', $_POST) ? $_POST['infopageUrl'] : (array_key_exists('GOPAY_INFOPAGE_URL', $conf) ? $conf['GOPAY_INFOPAGE_URL'] : '');
		$wsUrl = array_key_exists('wsUrl', $_POST) ? $_POST['wsUrl'] : (array_key_exists('GOPAY_WS_URL', $conf) ? $conf['GOPAY_WS_URL'] : '');
		$payMode = array_key_exists('payMode', $_POST) ? $_POST['payMode'] : (array_key_exists('GOPAY_PAY_MODE', $conf) ? $conf['GOPAY_PAY_MODE'] : '');
		$preconfMethod = array_key_exists('$preconfMethod', $_POST) ? $_POST['$preconfMethod'] : (array_key_exists('GOPAY_PRECONF_METHOD', $conf) ? $conf['GOPAY_PRECONF_METHOD'] : '');
		$customerData = array_key_exists('$customerData', $_POST) ? $_POST['$customerData'] : (array_key_exists('GOPAY_CUSTOMER_DATA', $conf) ? $conf['GOPAY_CUSTOMER_DATA'] : '');
		
		if ($gwUrl=="https://testgw.gopay.cz/zaplatit-plna-integrace") 
		{
			$wsUrl="https://testgw.gopay.cz/axis/EPaymentService?wsdl";
			Configuration::updateValue('GOPAY_WS_URL', strval($wsUrl));
		}	
		else {
			$wsUrl="https://gate.gopay.cz/axis/EPaymentService?wsdl";
			Configuration::updateValue('GOPAY_WS_URL', strval($wsUrl));
		}
		
		$paymentMethodList = GopaySoap::paymentMethodList();
		
		$this->_html .= '
		<legend style="background-color: white;"><a href="https://www.gopay.cz/partnerstvi" target="_blank"><img src="../modules/gopay/images/logo_header.gif" alt="Gopay" style="margin-top: 12px; margin-bottom: 12px;" /></a></legend>
		<p><b>GoPay</b> je platební systém pro online, diskrétní a bezpečné platby. GoPay zaručuje uživatelům pohodlí drobných plateb na internetu a obchodníkům jistotu okamžité platby za služby a zboží v elektronické komerci. Klíčovou výhodou je <b>univerzální platební brána</b> s nabídkou portfolia dalších platebních metod.</p>
		<p><b>Uživatel</b>, který zvolí GoPay platbu na stránkách E-shopu, je přesměrován na <b>platební bránu GoPay</b>, kde je vyzván k provedení platby. Po zaplacení je zpět přesměrován na stránky E-shopu, kam GoPay prezentuje stav platby a <b>obchodník</b> pak může expedovat zboží (službu).</p>
		<p><b>GoPay peněženka</b> je elektronická obdoba běžné peněženky pro rychlé každodenní platby, posílání nebo přijímání peněz po síti, bez poplatků, pohodlně a kdekoliv. S GoPay peněženkou může každý uživatel ihned, diskrétně a zcela zdarma platit za služby a zboží.</p>
		<p><b>Integrace</b> GoPay může být realizována instalací platebního modulu GoPay do E-shopu, který je založen na hotových open-source řešeních. Základem integrace je vyplnění formuláře partnerství na webových stránkách <a href="https://www.gopay.cz/partnerstvi" target="_blank" style="color: blue; text-decoration: underline;">https://www.gopay.cz/partnerstvi</a>, následně obchodník získá potřebné údaje a pokyny ke konfiguraci platebního modulu. V testovacím prostředí obchodník vyzkouší všechny potřebné funkcionality a v případě splnění formálních náležitostí přepne modul do provozního prostředí.</p>
		<p><b>Výhody GoPay pro E-shop:</b></p>
		<ul><li>- nejdynamičtější platební systém na českém internetu</li>
			<li>- komplexní rešení pro přijímaní plateb</li>
			<li>- 1 integrace = více platebních metod</li>
			<li>- vyšší rentabilita realizovaných obchodu</li>
			<li>- garance okamžité platby na obchodní účet</li>
			<li>- transparentní obchodní politika</li>
			<li>- žádné skryté náklady</li>
			<li>- unikátní slevový program pro zákazníky</li>
			<li>- příliv nových zákazníků</li></ul><br />
		<p>Zaujaly vás přednosti platebního systému GoPay? Máte otázky a zájem o obchodní partnerství? Více informací naleznete na <a href="https://www.gopay.cz" target="_blank" style="color: blue; text-decoration: underline;">https://www.gopay.cz</a> nebo nás prosím kontaktujte na e-mailové adrese <a href="mailto:podnikej@gopay.cz" target="_target" style="color: blue; text-decoration: underline;">podnikej@gopay.cz</a>. Máme pro vás připravený balíček obchodních výhod a silnou marketingovou podporu nejdynamičtějšího platebního systému na českém internetu.</p><br />
		<p>Vyplňte <a href="https://www.gopay.cz/partnerstvi" target="_blank" style="color: blue; text-decoration: underline;">formulář partnerství</a> na webu GoPay a získejte potřebné informace.</p>
		<br /><p><b>Kontaktní informace</b></p>
		<p>Technické informace, integrace <a href="mailto:integrace@gopay.cz" style="color: blue; text-decoration: underline;">integrace@gopay.cz</a></p>
		<p>Podpora, provozní otázky <a href="mailto:uzivej@gopay.cz" style="color: blue; text-decoration: underline;">uzivej@gopay.cz</a></p>
		<p>Smluvní podpora <a href="mailto:podnikej@gopay.cz" style="color: blue; text-decoration: underline;">podnikej@gopay.cz</a></p>
		<p><a href="https://www.gopay.cz" target="_blank" style="color: blue; text-decoration: underline;">https://www.gopay.cz</a></p>
		</fieldset>
		<br /><br />
		
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Nastavení').'</legend><br />	
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="clear: both;">	
			<br />	';
		
			for ($i = 0; $i < count($paymentMethodList); $i++) {
				
				$paymentMethod = $paymentMethodList[$i]->code;
				$conf = Configuration::get($paymentMethod);
				$offline = ($paymentMethodList[$i]->offline == 1) ? "ano" : "ne";
				
				$this->_html .= '
				<div style="border: 1px solid #dfd5c3; padding: 5px; margin-bottom: 5px;">
					<img src="'. $paymentMethodList[$i]->logo .'" alt="'.$paymentMethodList[$i]->paymentMethod.'" width="110px" height="35px">
					<label style="width: 200px; margin-right: 20px;">'.$paymentMethodList[$i]->paymentMethod.'</label>
					<div class="margin-form" style="float: right; margin-right: 20%;">
						<input type="radio" name="'. $paymentMethodList[$i]->code .'" value="1" '.($conf=='1' ? 'checked="checked"' : '').' /> '.$this->l('Aktivovat').'
						<br />
						<input type="radio" name="'. $paymentMethodList[$i]->code .'" value="0" '.($conf=='0' ? 'checked="checked"' : '').' /> '.$this->l('Deaktivovat').'
					</div>
				</div>';
			}
			
			$this->_html .= '
			
			<p>'.$this->l('<b>OBECNÁ NASTAVENÍ</b>').'</p>
			<div style="border: 1px solid #ddd; padding: 5px;">
				<p style="font-size: 12px;">'.$this->l('E-shop GoID je identifikátor e-shopu (10ti místné číslo), který získáte v okamžiku integrace GoPay (více na podnikej@gopay.cz).').'</p>
				<label>'.$this->l('E-shop GoID').'</label>
				<div class="margin-form" style="margin-bottom: 20px;"><input type="text" size="30" name="goId" value="'.htmlentities($goId, ENT_COMPAT, 'UTF-8').'" /></div>
				
				<hr />
							
				<p style="font-size: 12px;">'.$this->l('Secret je tajný kód (24 znaků) v rámci systému GoPay, který získáte v okamžiku integrace GoPay (více na podnikej@gopay.cz).').'</p>
				<label>'.$this->l('Secret').'</label>
				<div class="margin-form" style="margin-bottom: 20px;">
				<input type="text" size="30" name="gopaySecret" value="'.htmlentities($gopaySecret, ENT_COMPAT, 'UTF-8').'" /></div>
				
				<hr />
													
				<p style="font-size: 12px;">'.$this->l('Platební brána URL představuje typ komunikace s platebním systémem GoPay.').'</p>
				<label>'.$this->l('Platební brána URL').'</label>
				<div class="margin-form">
					<input type="radio" name="gwUrl" value="https://testgw.gopay.cz/zaplatit-plna-integrace" '.($gwUrl=='https://testgw.gopay.cz/zaplatit-plna-integrace' ? 'checked="checked"' : '').' /> '.$this->l('Test: https://testgw.gopay.cz').'
					<br />
					<input type="radio" name="gwUrl" value="https://gate.gopay.cz/zaplatit-plna-integrace" '.($gwUrl=='https://gate.gopay.cz/zaplatit-plna-integrace' ? 'checked="checked"' : '').' /> '.$this->l('Provoz: https://gate.gopay.cz').'
				</div>
			</div>
			
			<p style="margin-top: 50px;">'.$this->l('<b>PLATEBNÍ METODY - vyberte si ze seznamu platebních metod, které chcete přijímat na vašem E-shopu</b>').'</p>
			<div style="border: 1px solid #ddd; padding: 5px;">
			
			<p style="margin-top: 50px;">'.$this->l('<b>POTVRZENÍ PLATBY</b>').'</p>
			<div style="border: 1px solid #ddd; padding: 5px; margin-bottom: 20px;">
			
				<p style="font-size: 12px;">'.$this->l('<b>Základní varianta</b> - Typ platby je vybrán uživatelem na platební bráně GoPay. Zde provede výběr jedné z nastavených platebních metod.').'</p>	
				
				<div style="float: left;">
					<div class="margin-form">
						<input type="radio" name="payMode" value="single" '.($payMode=='single' ? 'checked="checked"' : '').' /> '.$this->l('Základní varianta').'
						<br />
						<img style="margin-top: 8px;" src="../modules/gopay/images/gopay_payment_methods.png" alt="Příklad základní varianty" title="Základní varianta" />
					</div>
				</div>
					
				<div style="float: right; margin-top: 20px; margin-right: 30px;">
				<label style="width: 200px; margin-right: 10px; margin-bottom: 20px;">'.$this->l('Preferovaná platební metoda').'</label>
					<select name="preconfMethod">
						<option>'.$this->l('').'</option>
						
						';
						
						$paymentMethodList = GopaySoap::paymentMethodList();
						for ($i = 0; $i < count($paymentMethodList); $i++) {
							
							$this->_html .= '<option value="'.$paymentMethodList[$i]->code .'"'. (Configuration::get('GOPAY_PRECONF_METHOD') == $paymentMethodList[$i]->code ? ' selected="selected">' : '">') . $paymentMethodList[$i]->paymentMethod .'</option>';
							
							$paymentMethod = $paymentMethodList[$i]->code; 
						}
						
						$this->_html .= '
					</select>
				</div>
				
				<hr class="clear"></hr>
				
				<p style="font-size: 12px;">'.$this->l('<b>Rozšířená varianta</b> - Typ platby je vybrán uživatelem v poslední fázi platby na E-shopu. Uživatel si stiskem tlačítka volí např. platbu "Platební kartou" nebo platbu "GoPay peněženkou".').'</p>
				<div class="margin-form">
					<input type="radio" name="payMode" value="multi" '.($payMode=='multi' ? 'checked="checked"' : '').' /> '.$this->l('Rozšířená varianta').'
					<br />
					<img style="margin-top: 8px;" src="../modules/gopay/images/multichoice_example.gif" alt="Příklad rozšířené varianty" title="Rozšířená varianta" />
				</div>
			
			</div>
			
			<p style="margin-top: 50px;">'.$this->l('<b>ZÁKAZNICKÉ INFORMACE</b>').'</p>
			<div style="border: 1px solid #ddd; padding: 5px; margin-bottom: 20px;">
		
				<p style="font-size: 12px;">'.$this->l('Chcete zapnout předávání zákaznických informací na platební bránu GoPay?').'</p>	
				
				<div style="float: left;">
					<div class="margin-form">
						<input type="radio" name="customerData" value="1" '.($customerData=='1' ? 'checked="checked"' : '').' /> '.$this->l('Aktivovat').'
						<br />
						<input type="radio" name="customerData" value="0" '.($customerData=='0' ? 'checked="checked"' : '').' /> '.$this->l('Deaktivovat').'
					</div>
				</div>
				
				<input type="hidden" name="successUrl" value="'.htmlentities($successUrl, ENT_COMPAT, 'UTF-8').'" />
				<input type="hidden" name="failedUrl" value="'.htmlentities($failedUrl, ENT_COMPAT, 'UTF-8').'" />
				<input type="hidden" name="wsUrl" value="'.htmlentities($wsUrl, ENT_COMPAT, 'UTF-8').'" />
				<input type="hidden" name="infopageUrl" value="'.htmlentities($infopageUrl, ENT_COMPAT, 'UTF-8').'" />
			
				<div class="clear"></div>
			</div>
					
			<center><input type="submit" name="submitGopay" value="'.$this->l('Uložit').'" class="button" /></center></div>
		
		</fieldset>
		
		
		</form> 
		<br /><br />
		
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Nahrání prezentace').'</legend>
			
				<p style="font-size: 12px;">"Soubor s grafickou prezentací GoPay" je obrázek, který je možno vygenerovat na webu <a href="https://www.gopay.cz/styl-go-pay/logo-eshopy" target="_blank">GoPay (https://www.gopay.cz/styl-go-pay/logo-eshopy)</a>. Uploadovaný obrázek je následně prezentován na domovské stránce shopu a zároveň při výběru platební metody v základní variantě potvrzení platby.</p>
				<form method="post" enctype="multipart/form-data">
				<label style="width: 300px; margin-right: 30px;">Soubor s grafickou prezentací GoPay:</label> <input class="button" type="file" name="gopay_presentation">
				
				<div class="clear"></div>
				
				<br /><br />
				
				<center><input type="submit" class="button" value="Nahrát soubor"></center>
			
			</form>
		
		</fieldset>
		</div>
		';
		
		// upload souboru s grafickou prezentaci
		if (isset($_FILES["gopay_presentation"]["name"]))
			{
				if (is_uploaded_file($_FILES["gopay_presentation"]["tmp_name"]))
				{
					$name = $_FILES["gopay_presentation"]["name"];
					if (move_uploaded_file($_FILES["gopay_presentation"]["tmp_name"], "".dirname(__FILE__)."/images/gopay_payment_methods.png")) {
				    	echo '<div class="conf confirm">';
						echo '<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />';
						echo $this->l('Soubor byl úspěšně nahrán');
						echo '</div>';
						
				   		}
				  	else {
				  		echo '<div class="alert error">';
				  		echo $this->l('Nastala chyba při nahrávání souboru, zkontrolujte práva pro přístup ke složce /modules/gopay');
				  		echo '</div>';
				  	}
				} 
			}
		}
		
	public function hookPayment($params)
	{
		if (!$this->active) {
			return ;
		}
		
		if (!$this->checkCurrency()) {
			return ;
		}
		
		if (!$this->checkModuleCurrency()) {
			return $this->display(__FILE__, 'templates/gopay_check_config.tpl');
		}
		
		global $smarty, $cookie;
		
		$param = '';
		$payUrl = 'modules/gopay/pay.php';
		$payMode = trim(Configuration::get('GOPAY_PAY_MODE'));
					
		// předvybraná platební metoda
		$preconfMethod = trim(Configuration::get('GOPAY_PRECONF_METHOD'));
		$paymentMethodList = GopaySoap::paymentMethodList();
		
		$paymentMethods = array();
		
		for ($i = 0; $i < count($paymentMethodList); $i++) {
			if ($preconfMethod == $paymentMethodList[$i]->code) {
				$param = "&paymentChannel=".$paymentMethodList[$i]->code;
			}
			if (Configuration::get($paymentMethodList[$i]->code) == "1") {
				$paymentMethods[] = array('title' => $paymentMethodList[$i]->paymentMethod, 'logo' => $paymentMethodList[$i]->logo, 'code' => $paymentMethodList[$i]->code);
			}
		}
		
		$smarty->assign(array(
		'payUrl' => $payUrl,
		'cartId' => intval($params['cart']->id),
		'param' => $param,
		'paymentMethods' => $paymentMethods,
		));
	
		// singlechoice mode
		if($payMode == 'single') {
			return $this->display(__FILE__, 'templates/gopay_single.tpl');
		}
		
		// multichoice mode
		else {
			return $this->display(__FILE__, 'templates/gopay_multi.tpl');
		}
	}
	
	function hookHome($params)
    {
    	global $smarty;
    
		/* get params from URL */
		if(isset($_GET['gp_errors']))
		{
			$gpErrors = $_GET['gp_errors'];
			
			if ($_GET['gp_errors']=='faultyPaymentIdentity') {
				$gpErrors = 'Nepodařilo se ověřit identitu platby.<br />Kontaktujte e-shop.'; 
			}
			if ($_GET['gp_errors']=='paymentCreationFailed') {
				$gpErrors = 'Nepodařilo se vytvořit platbu GoPay.<br />Zkontrolujte konfiguraci platebního modulu GoPay.'; 
			}
			if ($_GET['gp_errors']=='czk') {
				$gpErrors = 'E-shop nemá nastavenou českou měnu s platným ISO kódem CZK.'; 
			}
			if ($_GET['gp_errors']=='paymentNotVerified') {
				$gpErrors = 'Platba nebyla ověřena.'; 
			}
			if ($_GET['gp_errors']=='undefinedOrderFaultyState') {
				$gpErrors = 'Objednávka nebyla nalezena nebo je v chybném stavu.'; 
			}
			if ($_GET['gp_errors']=='alreadyClosed') {
				$gpErrors = 'Objednávka již byla uzavřena. Vyberte zboží znovu.'; 
			}
			if ($_GET['gp_errors']=='canceled') {
				$gpErrors = GopayHelper::getResultMessage("CANCELED");
			}
			if ($_GET['gp_errors']=="WAITING") {
				$gpErrors = GopayHelper::getResultMessage("WAITING"); 
			}
			if ($_GET['gp_errors']=="WAITING_OFFLINE") {
				$gpErrors = GopayHelper::getResultMessage("WAITING_OFFLINE");
			}
		
			$smarty->assign('gpErrors', $gpErrors);
		}
		
		if(isset($_GET['paymentState']))
		{
			$paymentState = $_GET['paymentState'];
			
			if ($paymentState=="done") {
				$paymentState = GopayHelper::getResultMessage("PAYMENT_DONE");
			}
			
			$smarty->assign('paymentState', $paymentState);
		}
			
		return $this->display(__FILE__, 'templates/infopage.tpl');
    }
	
	function hookLeftColumn($params)
	{
		$cmsId = Db::getInstance()->getValue('
		SELECT `id_cms` FROM `'._DB_PREFIX_.'cms_lang` WHERE `link_rewrite` = "gopay"');
		
		global $smarty;
		$smarty->assign('securepayment', $this->l('secure-payment'));
		$smarty->assign('cmsId', $cmsId);
		return $this->display(__FILE__, 'templates/blockpaymentlogogopay.tpl');
	}
	
	function hookRightColumn($params)
	{
		return $this->hookLeftColumn($params);
	}
	
	function hookFooter($params)
	{
		return $this->hookLeftColumn($params);
	}
	
	private function checkCurrency()
	{
		global $cookie;
		
		$checkCurrency = Currency::getPaymentCurrenciesSpecial($this->id);
		$czk_id = Currency::getIdByIsoCode('CZK');
		
		if (!isset($cookie->id_currency)) {
			$cookie->id_currency = $czk_id;
		}
		
		if(($checkCurrency['id_currency'] == $cookie->id_currency)) {
			return true;
		}
	}	
	
	private function checkModuleCurrency()
	{
		$paymentCurrency = Currency::getPaymentCurrenciesSpecial($this->id);
		$czk_id = Currency::getIdByIsoCode('CZK');
		
		if(($paymentCurrency['id_currency'] == $czk_id)) {
			return true;
		}
	}	
}
