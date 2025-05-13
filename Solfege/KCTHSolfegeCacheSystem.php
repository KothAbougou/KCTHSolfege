<?php

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfege;

/**
 * Système de mise en cache de l'application
 */

final class KCTHSolfegeCacheSystem extends KCTHSolfege
{
	private static ?string $buffer;

	/**
	 * Mémoriser un contenu dans un fichier temporaire
	 * Attention, l'ancien fichier du même nom est écrasé et remplacé.
	 */
	public static function memorize(string $cachename, string $content, bool $condition = true, ?callable $callback = null): bool
	{
	    if (self::$CACHE_SYSTEM && $condition) {
	        $file = self::filename($cachename);

	        $directory = dirname($file);
	        if (!is_dir($directory)) {
	            mkdir($directory, 0755, true);
	        }

	        if(!is_null($callback)){
	        	$callback();
	        }

	        return file_put_contents($file, $content) !== false;
	    }

	    return false;
	}

	/**
	 * Lecture d'un fichier temporaire
	 */
	public static function read(string $cachename, int|false $duration = false): string|false
	{
		$file = self::filename($cachename);

		if(!file_exists($file))
			return false;

		if(is_int($duration))
		{
			$lifetime = (time() - filemtime($file)) / 60;
			if($lifetime > $duration)
				return false;
		}
		
		return file_get_contents($file);
	}

	/**
	 * Suppression d'un fichier temporaire
	 */
	public static function delete(string $cachename): bool
	{
		$file = self::filename($cachename);

		if(file_exists($file))
			return unlink($file);

		return false;
	}

	/**
	 * Vide tout le cache
	 */
	public static function clear()
	{
		foreach(glob(self::filename('*')) as $file)
			unlink($file);
	}


	public static function start(string $cachename, int|false $duration = false, bool $condition = true): bool
	{
		$content = self::read($cachename, $duration);

		if($content !== false && $condition && SELF::$CACHE_SYSTEM){
			self::clearBuffer();
			echo $content;
			return false;
		}

		self::_startBuffer($cachename);
		return true;
	}

	public static function end(bool $condition = true, ?callable $callback = null): bool
	{
		if(empty(self::$buffer)) return false;

		self::_endBuffer($condition, $callback); 
		return true;
	}

	/**
	 * Vider le buffer
	 */
	public static function clearBuffer(): void
	{
		self::$buffer = null;
	}

	/**
	 * Début d'enregistrement du buffer
	 */
	private static function _startBuffer($cachename): void
	{
		ob_start();
		self::$buffer = $cachename;
	}

	private static function _endBuffer(bool $condition = true, ?callable $callback = null): void
	{
		self::memorize(self::$buffer, ob_get_clean(), $condition, $callback);
		echo self::read(self::$buffer);
		self::clearBuffer();
	}


	private static function filename(string $cachename): string
	{
		return self::$cacheDirectory . $cachename;
	}

	/**
	 * Raffraichis le cache à chaque changement d'état d'un fragment
	 */
	public static function onFragmentMutation(string $fragment, array $conditions, callable $newFragmentHandler): string
	{
		$cache = true; 

		foreach($conditions as $condition){
			if(!$condition){
				$cache = false;
				break;
			}
		}

		$cachedFragment = self::read($fragment);

		if($cache && $cachedFragment && SELF::$CACHE_SYSTEM)
			return $cachedFragment;

		return $newFragmentHandler();
	}

}

