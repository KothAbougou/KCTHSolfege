<?php

namespace KCTH\Solfege\helper;

use KCTH\Solfege\KCTHSolfegeHelper;
use KCTH\Solfege\KCTHSolfegeModel;

abstract class KCTHSolfegeStatisticsHelper extends KCTHSolfegeHelper
{
	/**
	 * Model de dépendance.
	 */
	protected static ?KCTHSolfegeModel $model;

	/**
	 * Variables de dépendences.
	 */
	protected static array $vars;


	public function __construct(array $vars = [])
	{	
		self::$vars = $vars;

		if(isset($vars[':model'])){
			self::$model = $vars[':model'];
			unset(self::$vars[':model']);
		}
	}

	/**
	 * Ajoute des variables de dépendences.
	 */
	public static function addVariables(array $vars)
	{
		foreach($vars as $key => $var)
			if(!isset(self::$vars[$key]) || empty(self::$vars[$key]))
				self::$vars[$key] = $var;
	}


	/**
	 * Retourne une variable de dépendence
	 */
	public static function variable($key)
	{
		return self::$vars[$key] ?? null;
	}

	/**
	 * Modifie une variable avec une autre valeur de même type.
	 */
	public static function setVariable($var, $value)
	{
		self::${$var} = $value;
	}

	/**
	 * Retourne la variable lié au model
	 */
	public static function model()
	{
		return self::$model;
	}

	/**
	 * Affiche les variables de dépendences.
	 */
	public static function inspect()
	{
		self::dump(self::$vars);
	}
}

