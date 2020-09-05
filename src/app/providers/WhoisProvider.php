<?php
namespace providers;

use models\Whois;
use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;

class WhoisProvider{
	
	public static function retrieve($domain){
		
		$existingWhois = DAO::getOne(Whois::class, 'domain LIKE ?', false, [$domain]);
		if(empty($existingWhois)) {
			
			$ch = curl_init("https://www.whoisxmlapi.com/whoisserver/WhoisService?apiKey=" . Startup::$config['providers']['whois']['apiKey'] . "&domainName=" . $domain . "&outputFormat=JSON");
			
			$curlOptions[CURLOPT_RETURNTRANSFER] = 1;
			$curlOptions[CURLOPT_FOLLOWLOCATION] = 1;
			
			curl_setopt_array($ch, $curlOptions);
			$data = curl_exec($ch);
			
			if(!empty($data)) {
				$parsedData = json_decode($data, true);
				if (!empty($parsedData) && isset($parsedData['WhoisRecord'])) {
					
					$whois = new Whois();
					
					$whois->setDomain($domain);
					$whois->setData($data);
					
					$whois->setExists((!isset($parsedData['WhoisRecord']['dataError']) || $parsedData['WhoisRecord']['dataError'] !== 'MISSING_WHOIS_DATA'));
					if (DAO::save($whois)) {
						return $whois;
					}
				}
			}
			return false;
			
		}else {
			return $existingWhois;
		}
		
	}
	
}
