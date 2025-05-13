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
use KCTH\Solfege\KCTHSolfegeModel as Model;
use KCTH\Solfege\KCTHSolfegeTemplate as Template;

/**
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
abstract class KCTHSolfegeController extends KCTHSolfege
{
	protected static function getTemplate(): Template
	{
		$template_name = "\\app\\template\\" . self::$templateClass;

		return new $template_name();
	}

	PROTECTED STATIC FUNCTION LOAD_MODEL($model_name): Model
	{
		$model_name = "\\app\\model\\{$model_name}Model";
		
		return new $model_name();
	}

	PROTECTED STATIC FUNCTION RENDER_VIEW(string $view, callable $template, $params=[])
	{
		global $solfege;

		$solfege::getView()::render($view, $template(), $params);
	}

	PROTECTED STATIC FUNCTION RENDER_PARTIALS(string $partial_file, array $params = [])
	{
		global $solfege;

		$solfege::getView()::loadPartials($partial_file, $params); 
	}

	PROTECTED STATIC FUNCTION MIGRATE(string $migration_class_name, array $migration_methods = [])
	{
		$migration_class_name = "\\app\\migration\\{$migration_class_name}";

		$migration_class = new $migration_class_name();

		if(empty($migration_methods))
			$migration_methods[] = "migrate";

		foreach($migration_methods as $method)
			$migration_class->{$method}();


	}
}