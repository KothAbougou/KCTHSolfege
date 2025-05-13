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

/**
 * Sessions du système d'authentification
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
final class KCTHSolfegeAuth extends KCTHSolfege
{	
	/**
	 * Clé de la session des Auths
	 * @var string [default: 'Auth']
	 */
	public static $auth_KEY;

	/**
	 * Sélectionne une session Auth
	 * @param  string $auth_KEY
	 */
	public static function select(string $auth_KEY): void
	{
		$auth_KEY = strtoupper($auth_KEY);

		self::$auth_KEY = $auth_KEY == 'AUTH' ? 
			'Auth' : 'Auth_' . $auth_KEY;
	}

	/**
	 * Change la session Auth
	 * @param  string|int $auth_VALUE 
	 */
	public static function sessionStart(string|int $auth_VALUE)
	{
		if(!empty(self::$auth_KEY))
			self::session(self::$auth_KEY, $auth_VALUE);
		else throw new \Exception("Impossible de créer une " . __CLASS__ . "::session() sans avoir sélectionner " . __CLASS__ ."::select(\$auth_KEY) avant.");
		
	}

	/**
	 * Retourne la valeur de la session Auth
	 * @return string|int|array|object $auth_VALUE
	 */
	public static function get(): string|int
	{
		if(self::isAuth())
			return self::session(self::$auth_KEY);
		else throw new \Exception("La session " . self::$auth_KEY . "n'existe pas.");
	}

	public static function getModel()
	{
		global $bdd;

		if(self::isAuth())
		{
			$auth_VALUE = self::get();

			switch(self::$auth_KEY)
			{
				case 'Auth':
					$req = $bdd->query("SELECT * FROM px_fact_utilisateur WHERE util_id = {$auth_VALUE}");
					return $req->fetchAll(\PDO::FETCH_CLASS, 'app\model\user\UserModel')[0];
			}

		}else return null;
	}

	/**
	 * Détruit la session de l'auth courant
	 */
	public static function unset():void
	{
		if(self::isAuth())
			self::session()::unset(self::$auth_KEY);
		else throw new \Exception("La session " . self::$auth_KEY . "n'existe pas.");
	}

	/**
	 * Vérifie l'existance de la session auth
	 * @return boolean [description]
	 */
	public static function isAuth(): bool
	{
		$auth_session = self::session(self::$auth_KEY);
		return isset($auth_session) && !empty($auth_session);
	}

	/**
	 * Redirection en cas d'authentification invalide.
	 */
	PUBLIC STATIC FUNCTION REQUIRED(?string $location = '/connexion'): void
	{
		$location = is_null($location) ? self::$www : $location;

		!self::isAuth() ? SELF::REDIRECT_TO($location) : false;

	}

	/**
	 * Redirection en cas d'authentification valide.
	 */
	PUBLIC STATIC FUNCTION NOT_REQUIRED(?string $location = null): void
	{
		$location = is_null($location) ? self::$www : $location;

		self::isAuth() ? SELF::REDIRECT_TO($location) : false;
	}

	/**
	 * Redirection si l'auth n'est pas développer
	 */
	PUBLIC STATIC FUNCTION DEV_REQUIRED(?string $location = '/connexion'): void
	{
		$location = is_null($location) ? self::$www : $location;

		SELF::AUTH()->Role->sigle() !== SELF::ROLE_DEV ?
			SELF::REDIRECT_TO($location) : false;
	}

	// private static function renameKey($key): string
}