<?php

namespace KCTH\Solfege\helper;

use \KCTH\Solfege\KCTHSolfegeHelper;

class KCTHSolfegeRibHelper extends KCTHSolfegeHelper
{
	PUBLIC STATIC FUNCTION SWIFT_VALIDATE($swift)
	{
		return !preg_match("/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/i", $swift) ? false : true;
	}

	PUBLIC STATIC FUNCTION IS_VALID_IBAN($iban)
	{
		// Régles de validation par pays  
		static $rules = array(
			'AL'=>'[0-9]{8}[0-9A-Z]{16}',
			'AD'=>'[0-9]{8}[0-9A-Z]{12}',
			'AT'=>'[0-9]{16}',
			'BE'=>'[0-9]{12}',
			'BA'=>'[0-9]{16}',
			'BG'=>'[A-Z]{4}[0-9]{6}[0-9A-Z]{8}',
			'HR'=>'[0-9]{17}',
			'CY'=>'[0-9]{8}[0-9A-Z]{16}',
			'CZ'=>'[0-9]{20}',
			'DK'=>'[0-9]{14}',
			'EE'=>'[0-9]{16}',
			'FO'=>'[0-9]{14}',
			'FI'=>'[0-9]{14}',
			'FR'=>'[0-9]{10}[0-9A-Z]{11}[0-9]{2}',
			'GE'=>'[0-9A-Z]{2}[0-9]{16}',
			'DE'=>'[0-9]{18}',
			'GI'=>'[A-Z]{4}[0-9A-Z]{15}',
			'GR'=>'[0-9]{7}[0-9A-Z]{16}',
			'GL'=>'[0-9]{14}',
			'HU'=>'[0-9]{24}',
			'IS'=>'[0-9]{22}',
			'IE'=>'[0-9A-Z]{4}[0-9]{14}',
			'IL'=>'[0-9]{19}',
			'IT'=>'[A-Z][0-9]{10}[0-9A-Z]{12}',
			'KZ'=>'[0-9]{3}[0-9A-Z]{3}[0-9]{10}',
			'KW'=>'[A-Z]{4}[0-9]{22}',
			'LV'=>'[A-Z]{4}[0-9A-Z]{13}',
			'LB'=>'[0-9]{4}[0-9A-Z]{20}',
			'LI'=>'[0-9]{5}[0-9A-Z]{12}',
			'LT'=>'[0-9]{16}',
			'LU'=>'[0-9]{3}[0-9A-Z]{13}',
			'MK'=>'[0-9]{3}[0-9A-Z]{10}[0-9]{2}',
			'MT'=>'[A-Z]{4}[0-9]{5}[0-9A-Z]{18}',
			'MR'=>'[0-9]{23}',
			'MU'=>'[A-Z]{4}[0-9]{19}[A-Z]{3}',
			'MC'=>'[0-9]{10}[0-9A-Z]{11}[0-9]{2}',
			'ME'=>'[0-9]{18}',
			'NL'=>'[A-Z]{4}[0-9]{10}',
			'NO'=>'[0-9]{11}',
			'PL'=>'[0-9]{24}',
			'PT'=>'[0-9]{21}',
			'RO'=>'[A-Z]{4}[0-9A-Z]{16}',
			'SM'=>'[A-Z][0-9]{10}[0-9A-Z]{12}',
			'SA'=>'[0-9]{2}[0-9A-Z]{18}',
			'RS'=>'[0-9]{18}',
			'SK'=>'[0-9]{20}',
			'SI'=>'[0-9]{15}',
			'ES'=>'[0-9]{20}',
			'SE'=>'[0-9]{20}',
			'CH'=>'[0-9]{5}[0-9A-Z]{12}',
			'TN'=>'[0-9]{20}',
			'TR'=>'[0-9]{5}[0-9A-Z]{17}',
			'AE'=>'[0-9]{19}',
			'GB'=>'[A-Z]{4}[0-9]{14}'
		);

		// Vérification la longueur minimale
		if(mb_strlen($iban) < 18) return false;

		// Récupération du code ISO du pays
		$ctr = substr($iban,0,2);
		if(isset($rules[$ctr]) === false) return false;
		
		// Récupération de la règle de validation en fonction du pays
		$check = substr($iban,4);
		# Si la règle n'est pas bonne l'IBAN n'est pas valide
		if(preg_match('~'.$rules[$ctr].'~',$check) !== 1) return false;

		// Récupération de la chaine qui permettant de calculer la validation
		$check .= substr($iban,0,4);

		// On remplace les caractères alpha par leurs valeurs décimales*/
		
		$alphaChars = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$decimalChars = array('10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35');
		$check = str_replace($alphaChars, $decimalChars, $check);

		/*On effectue la vérification finale*/
		return bcmod($check,97) === '1';
	}

	/**
	 * Permet de vérifier la validité du RIB par rapport à la clé
	 */
	PUBLIC STATIC FUNCTION CHECK_RIB($cbanque, $cguichet, $nocompte, $clerib)
	{
		$tabcompte = "";
		$len = strlen($nocompte);

		if($len != 11) return false;

		for($i=0; $i < $len; $i++)
		{
			$car = substr($nocompte, $i, 1);

			if(!is_numeric($car))
			{
				$c = ord($car) - (ord('A') - 1);
				$b = ($c + pow(2, ($c - 10)/9)) % 10;
				$tabcompte .= $b;
			}else{
				$tabcompte .= $car;
			}
		}

		$int = $cbanque . $cguichet . $tabcompte . $clerib;

		return strlen($int) >= 21 && bcmod($int, 97) == 0;
	}


	PUBLIC STATIC FUNCTION IS_VALID_RIB($rib)
	{
		if(mb_strlen($rib) !== 23) return false;

		$key = substr($rib,-2);
		$bank = substr($rib,0,5);
		$bank = substr($rib,0,5);
		$branch = substr($rib,5,5);
		$account = substr($rib,10,11);
		$account = strtr($account,
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'12345678912345678923456789');

		return 97 - bcmod(89*$bank + 15 * $branch + 3 * $account,97) === (int)$key;
	}


	PUBLIC STATIC FUNCTION GET_IBAN_INFO(string $iban): array
	{
		return array(
			'IBAN' 	  => $iban,
			'BANQUE'  => substr($iban , 4, 5),
            'GUICHET' => substr($iban , 9, 5),
            'COMPTE'  => substr($iban , 14, 11),
            'CLEF' 	  => substr($iban , 25, 2)
		);
	}
}
