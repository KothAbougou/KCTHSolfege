<?php
/**
 * This file is part of the Solfege package.
 * 
 * @copyright (c) KCTH DEVELOPER <solfege@kcth.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/

namespace KCTH\Solfege;

/**
 * Autoloader de Solfege
 * @package KTCH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
final class KCTHSolfegeAutoloader
{	
	public static function register(){
		spl_autoload_register(array(__CLASS__,'autoload'));
	}

	/**
	 * 	Ne charge que les class du projet ou du dossier vendor
	 * @param  string $class [description]
	 */
	private static function autoload(string $class): void
	{
		$basespace = explode('\\', $class)[0];

		switch($basespace)
		{
			default: $dir = '../vendor/'; break;
			case 'app': 
				$class = str_replace('app\\', '', $class); 
				$dir = '../'; 
				break;

			case 'vendor': 	
				$class = str_replace('vendor\\', '', $class); 
				$dir = '../vendor/'; 
				break;

			case 'Phroute': 
				$class = str_replace('Phroute\\Phroute\\', '', $class);  
				$dir = '../vendor/phroute/phroute/src/Phroute/'; 
				break;

			
		}

		$class = str_replace('\\','/',$class);

		require($dir . $class . '.php');
	}
}