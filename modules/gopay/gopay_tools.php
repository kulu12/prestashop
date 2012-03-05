<?php

/**
  * GoPay tools method class, gopay_tools.php
  
  * @category gopay
  *
  * @author Gopay <integrace@gopay.cz>
  *
  */

class GopayTools {

	public function processPayment($result, $orderId) {
		$gopay = new Gopay;
		$gpErrors = "";
		
		$history = new OrderHistory();
		$history->id_order = $orderId;
		
		if ($result["code"] == GopayHelper::WAITING) {
			$gpErrors = $result["description"];
		}
		else if ($result["code"] == GopayHelper::PAYMENT_DONE) {
			if ($history->getLastOrderState($orderId)->id == intval(_PS_OS_GOPAY_)) {
			    $history->changeIdOrderState(intval(_PS_OS_PAYMENT_), intval($orderId));
				$history->addWithemail();
			}
		}
		else if (($result["code"] == GopayHelper::CANCELED) || ($result["code"] == GopayHelper::TIMEOUTED)) {
			if ($history->getLastOrderState($orderId)->id == intval(_PS_OS_GOPAY_)) {
				$history->changeIdOrderState(intval(_PS_OS_CANCELED_), $orderId);
				$history->addWithemail();
			}
			
			$gpErrors = $result["description"];
		}
		
		return $gpErrors; 
	}
	
	public function concatProductsNames($order) {
		$products = $order->getProductsDetail();
		$productNameConcat = "";
		foreach ($products as $key => $product) {
			$product['product_name'] = trim($product['product_name']);
			$products[$key]['product_name'] = htmlentities(utf8_decode($product['product_name']));
			$productNameConcat .= $product['product_name'] . ', ';
		}
		
		// oriznuti velkeho retezce 
		if (strlen($productNameConcat) > 127) {
			$productNameConcat = substr($productNameConcat, 0, 127);

		} else {
			$productNameConcat = substr($productNameConcat, 0, strlen($productNameConcat) - 2);
		}
		
		return $productNameConcat;
	}
	
public function getConvertedCountryCode($iso_code)
	{
		$countryCodeTable = array(

			'AF' => CountryCode::AFG, 
			'AE' => CountryCode::ARE, 
			'AG' => CountryCode::ATG, 
			'AI' => CountryCode::AIA, 
			'AL' => CountryCode::ALB, 
			'AM' => CountryCode::ARM, 
			'AO' => CountryCode::AGO, 
			'AQ' => CountryCode::ATA, 
			'AR' => CountryCode::ARG,
			'AS' => CountryCode::ASM, 
			'AT' => CountryCode::AUT, 
			'AU' => CountryCode::AUS, 
			'AW' => CountryCode::ABW, 
			'AX' => CountryCode::ALA,
			'AZ' => CountryCode::AZE, 
			'BA' => CountryCode::BIH, 
			'BB' => CountryCode::BRB, 
			'BD' => CountryCode::BGD, 
			'BE' => CountryCode::BEL,
			'BF' => CountryCode::BFA, 
			'BG' => CountryCode::BGR, 
			'BH' => CountryCode::BHR, 
			'BI' => CountryCode::BDI, 
			'BJ' => CountryCode::BEN, 
			'BM' => CountryCode::BMU, 
			'BN' => CountryCode::BRN,
			'BO' => CountryCode::BOL, 
			'BR' => CountryCode::BRA, 
			'BS' => CountryCode::BHS, 
			'BT' => CountryCode::BTN, 
			'BV' => CountryCode::BVT, 
			'BW' => CountryCode::BWA, 
			'BY' => CountryCode::BLR, 
			'BZ' => CountryCode::BLZ, 
			'CA' => CountryCode::CAN, 
			'CC' => CountryCode::CCK, 
			'CF' => CountryCode::CAF, 
			'CG' => CountryCode::COG, 
			'CH' => CountryCode::CHE,
			'CI' => CountryCode::CIV, 
			'CK' => CountryCode::COK, 
			'CL' => CountryCode::CHL,
			'CM' => CountryCode::CMR,
			'CN' => CountryCode::CHN, 
			'CO' => CountryCode::COL, 
			'CR' => CountryCode::CRI, 
			'CU' => CountryCode::CUB,
			'CV' => CountryCode::CPV, 
			'CX' => CountryCode::CXR, 
			'CY' => CountryCode::CYP, 
			'CZ' => CountryCode::CZE, 
			'DE' => CountryCode::DEU, 
			'DJ' => CountryCode::DJI, 
			'DK' => CountryCode::DNK, 
			'DM' => CountryCode::DMA, 
			'DO' => CountryCode::DOM, 
			'DZ' => CountryCode::DZA, 
			'EC' => CountryCode::ECU, 
			'EE' => CountryCode::EST, 
			'EG' => CountryCode::EGY, 
			'EH' => CountryCode::ESH, 
			'ER' => CountryCode::ERI, 
			'ES' => CountryCode::ESP, 
			'ET' => CountryCode::ETH, 
			'FI' => CountryCode::FIN, 
			'FJ' => CountryCode::FJI, 
			'FK' => CountryCode::FLK, 
			'FM' => CountryCode::FSM, 
			'FO' => CountryCode::FRO, 
			'FR' => CountryCode::FRA, 
			'GA' => CountryCode::GAB, 
			'GB' => CountryCode::GBR, 
			'GD' => CountryCode::GRD, 
			'GE' => CountryCode::GEO, 
			'GF' => CountryCode::GUF, 
			'GG' => CountryCode::GGY,
			'GH' => CountryCode::GHA, 
			'GI' => CountryCode::GIB, 
			'GL' => CountryCode::GRL, 
			'GM' => CountryCode::GMB, 
			'GN' => CountryCode::GIN,
			'GP' => CountryCode::GLP,
			'GQ' => CountryCode::GNQ,
			'GR' => CountryCode::GRC,
			'GS' => CountryCode::SGS, 
			'GT' => CountryCode::GTM, 
			'GU' => CountryCode::GUM, 
			'GW' => CountryCode::GNB, 
			'GY' => CountryCode::GUY, 
			'HK' => CountryCode::HKG, 
			'HM' => CountryCode::HMD, 
			'HN' => CountryCode::HND, 
			'HR' => CountryCode::HRV, 
			'HT' => CountryCode::HTI, 
			'HU' => CountryCode::HUN, 
			'CH' => CountryCode::CHE, 
			'ID' => CountryCode::IDN, 
			'IE' => CountryCode::IRL, 
			'IL' => CountryCode::ISR, 
			'IN' => CountryCode::IND, 
			'IO' => CountryCode::IOT, 
			'IQ' => CountryCode::IRQ, 
			'IR' => CountryCode::IRN, 
			'IS' => CountryCode::ISL,
			'IT' => CountryCode::ITA, 
			'JE' => CountryCode::JEY,
			'JM' => CountryCode::JAM, 
			'JO' => CountryCode::JOR, 
			'JP' => CountryCode::JPN, 
			'KE' => CountryCode::KEN, 
			'KG' => CountryCode::KGZ, 
			'KH' => CountryCode::KHM, 
			'KI' => CountryCode::KIR, 
			'KM' => CountryCode::COM, 
			'KN' => CountryCode::KNA, 
			'KP' => CountryCode::PRK,
			'KR' => CountryCode::KOR, 
			'KW' => CountryCode::KWT, 
			'KY' => CountryCode::CYM, 
			'KZ' => CountryCode::KAZ,
			'LA' => CountryCode::LAO, 
			'LB' => CountryCode::LBN, 
			'LC' => CountryCode::LCA, 
			'LI' => CountryCode::LIE, 
			'LK' => CountryCode::LKA, 
			'LR' => CountryCode::LBR,
			'LS' => CountryCode::LSO, 
			'LT' => CountryCode::LTU, 
			'LU' => CountryCode::LUX, 
			'LV' => CountryCode::LVA, 
			'LY' => CountryCode::LBY, 
			'MA' => CountryCode::MAR, 
			'MC' => CountryCode::MCO, 
			'MD' => CountryCode::MDA, 
			'ME' => CountryCode::MNE,
			'MG' => CountryCode::MDG, 
			'MH' => CountryCode::MHL, 
			'MK' => CountryCode::MKD, 
			'ML' => CountryCode::MLI,
			'MM' => CountryCode::MMR, 
			'MN' => CountryCode::MNG,
			'MO' => CountryCode::MAC, 
			'MP' => CountryCode::MNP, 
			'MQ' => CountryCode::MTQ, 
			'MR' => CountryCode::MRT, 
			'MS' => CountryCode::MSR, 
			'MT' => CountryCode::MLT, 
			'MU' => CountryCode::MUS, 
			'MV' => CountryCode::MDV, 
			'MW' => CountryCode::MWI, 
			'MX' => CountryCode::MEX, 
			'MY' => CountryCode::MYS, 
			'MZ' => CountryCode::MOZ, 
			'NA' => CountryCode::NAM, 
			'NC' => CountryCode::NCL, 
			'NE' => CountryCode::NER, 
			'NF' => CountryCode::NFK, 
			'NG' => CountryCode::NGA, 
			'NI' => CountryCode::NIC,
			'NL' => CountryCode::NLD, 
			'NO' => CountryCode::NOR, 
			'NP' => CountryCode::NPL, 
			'NR' => CountryCode::NRU, 
			'NU' => CountryCode::NIU, 
			'NZ' => CountryCode::NZL,
			'OM' => CountryCode::OMN, 
			'PA' => CountryCode::PAN,
			'PE' => CountryCode::PER, 
			'PF' => CountryCode::PYF,
			'PG' => CountryCode::PNG, 
			'PH' => CountryCode::PHL, 
			'PK' => CountryCode::PAK, 
			'PL' => CountryCode::POL, 
			'PM' => CountryCode::SPM, 
			'PN' => CountryCode::PCN, 
			'PR' => CountryCode::PRI, 
			'PT' => CountryCode::PRT, 
			'PW' => CountryCode::PLW, 
			'PY' => CountryCode::PRY, 
			'QA' => CountryCode::QAT, 
			'RE' => CountryCode::REU, 
			'RO' => CountryCode::ROU,
			'RS' => CountryCode::SRB,
			'RU' => CountryCode::RUS,
			'RW' => CountryCode::RWA, 
			'SA' => CountryCode::SAU, 
			'SB' => CountryCode::SLB, 
			'SC' => CountryCode::SYC,
			'SD' => CountryCode::SDN, 
			'SE' => CountryCode::SWE,
			'SG' => CountryCode::SGP,
			'SH' => CountryCode::SHN,
			'SI' => CountryCode::SVN, 
			'SJ' => CountryCode::SJM, 
			'SK' => CountryCode::SVK,
			'SL' => CountryCode::SLE, 
			'SM' => CountryCode::SMR, 
			'SN' => CountryCode::SEN, 
			'SO' => CountryCode::SOM,
			'SR' => CountryCode::SUR, 
			'ST' => CountryCode::STP, 
			'SV' => CountryCode::SLV, 
			'SY' => CountryCode::SYR, 
			'SZ' => CountryCode::SWZ, 
			'TC' => CountryCode::TCA, 
			'TD' => CountryCode::TCD, 
			'TF' => CountryCode::ATF, 
			'TG' => CountryCode::TGO, 
			'TH' => CountryCode::THA, 
			'TJ' => CountryCode::TJK,
			'TK' => CountryCode::TKL, 
			'TM' => CountryCode::TKM, 
			'TN' => CountryCode::TUN, 
			'TO' => CountryCode::TON, 
			'TR' => CountryCode::TUR, 
			'TT' => CountryCode::TTO, 
			'TV' => CountryCode::TUV, 
			'TW' => CountryCode::TWN, 
			'TZ' => CountryCode::TZA,
			'UA' => CountryCode::UKR, 
			'UG' => CountryCode::UGA,
			'UM' => CountryCode::UMI, 
			'US' => CountryCode::USA, 
			'UY' => CountryCode::URY, 
			'UZ' => CountryCode::UZB, 
			'VA' => CountryCode::VAT,
			'VC' => CountryCode::VCT, 
			'VE' => CountryCode::VEN, 
			'VG' => CountryCode::VGB,
			'VI' => CountryCode::VIR, 
			'VN' => CountryCode::VNM,
			'VU' => CountryCode::VUT, 
			'WF' => CountryCode::WLF, 
			'WS' => CountryCode::WSM, 
			'YE' => CountryCode::YEM, 
			'YT' => CountryCode::MYT, 
			'YU' => CountryCode::BIH,
			'ZA' => CountryCode::ZAF,
			'ZM' => CountryCode::ZMB, 
			'ZW' => CountryCode::ZWE
		);
		
		return $countryCodeTable[$iso_code];
	}
	
}
