<?php
/**
 * This file is part of the Solfege package.
 * 
 * @copyright (c) KCTH Developpers <solfege@kcth.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/
namespace KCTH\Solfege\exception;

use \Exception;


/**
 * Les Exceptions de Solfege.
 * @package KCTH\Solfege\exception
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
class KCTHSolfegeException extends Exception
{
	public static function view(string $page, Template $template, $params=[])
	{
		global $solfege;

		$solfege::getView()::render($page, $template, $params);
	}
}