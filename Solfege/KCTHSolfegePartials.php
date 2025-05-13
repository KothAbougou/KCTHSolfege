<?php

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfegeView;
use KCTH\Solfege\KCTHSolfegeCacheSystem;

class KCTHSolfegePartials extends KCTHSolfegeView
{

	/**
	 * Variables de dépendences.
	 */
	protected static array $dependencies;
	protected const ITERATIVE_DEPENDENCY = null;

	/**
	 * itération 
	 */
	public static int $i = 0; 

	/**
	 * Cache system
	 */
	protected static KCTHSolfegeCacheSystem $cacheSystem;

	/**
	 * Fichier courant
	 */
	public static string $__PARTIAL_FILE__;


	public function __construct(array $dependencies = [])
	{	
		global $cacheSystem;

		self::$dependencies = $dependencies;
		self::$cacheSystem = $cacheSystem;
	}


	/**
	 * Affiche les variables de dépendences.
	 */
	public static function show_dependencies()
	{
		self::dump(self::$dependencies);
	}

	/**
	 * Retourne une variable de dépendence
	 */
	public static function dependency($key)
	{
		return self::$dependencies[$key] ?? null;
	}

	/**
	 * Modifie un variable de dépendance itérative.
	 */
	PUBLIC STATIC FUNCTION SET_ITERATIVE_DEPENDENCY_VAR($key, $value)
	{
		self::${$key} = $value;
	}

	/**
	 * Nomme un fragment de la vue
	 */
	protected static function fragment(string $fragment): string
	{
		return SELF::$__PARTIAL_FILE__ . "__" . $fragment . self::$viewExtension;
	}

	protected static function fragment_tmp(string $fragment): string
	{
		return "tmp/" . self::fragment($fragment);
	}
}