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

use KCTH\Solfege\KCTHSolfege;
use KCTH\Solfege\KCTHSolfegeConfig as Config;

/**
 * Configuration dynamique de Solfege
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
final class KCTHSolfegeConfig extends KCTHSolfege
{	
	private array $settings;

	private static ?Config $_instance = null;


	public function __construct()
	{
		$this->settings = [];
	}

	/**
	 * Singleton
	 * @return Config
	 */
	public static function single(): Config
	{
		if(self::$_instance===null){
			self::$_instance = new Config();
		}
		return self::$_instance;
	}

	/**
	 * Récupère le tableau de configation des fichiers .conf.php
	 * @param  string $file_name
	 */
	public function onFile(string $file_name)
	{
		$this->settings = require("../config/{$file_name}.conf.php");
	}

	/**
	 * Récupère une donnée du tableau de configuration.
	 * @param  string|int  $key 
	 * @return mixed      
	 */
	public function get(string|int $key): mixed
	{
		if(!isset($this->settings[$key]))
			return null;

		return $this->settings[$key];
	}

	public function getAll()
	{
		return $this->settings;
	}
}