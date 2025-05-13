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
use \Exception;

/**
 * Les sessions de l'application
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
class KCTHSolfegeSession extends KCTHSolfege
{
	PUBLIC CONST ERR_SESSION_ALREADY_CREATED = "La session :x a déjà été créée. Utilisez la méthode self::session()::change(\$session_key, \$session_value) pour la modifier";

	PUBLIC CONST ERR_SESSION_NOT_EXISTING = "Le session :x n'est pas créée. Utilisez la méthode self::session(\$session_key, \$session_value)";


	public static function get(string|int $session_key): mixed
	{
		return $_SESSION[$session_key] ?? null;
	}

	public static function getAll(): array
	{
		return $_SESSION;
	}

	public static function create(string|int $session_key, mixed $session_value)
	{
		if(!isset($_SESSION[$session_key]))
			$_SESSION[$session_key] = $session_value;

		else throw new Exception(str_replace(":x", $session_key, SELF::ERR_SESSION_ALREADY_CREATED));
	}

	public static function change(string|int $session_key, mixed $session_value)
	{
		if(isset($_SESSION[$session_key]))
			$_SESSION[$session_key] = $session_value;

		else throw new Exception(str_replace(":x", $session_key, SELF::ERR_SESSION_NOT_EXISTING));
	}

	public static function isset(string|int $session_key): bool
	{
		return isset($_SESSION[$session_key]);
	}

	public static function unset(string|int $session_key): void
	{
		unset($_SESSION[$session_key]);
	}

	public static function destroy(): void
	{
		session_unset();
        session_destroy();
	}

}