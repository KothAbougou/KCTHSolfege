<?php

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfege;

/**
 * Système de mise en cache de l'application
 */
class KCTHSolfegeCache extends KCTHSolfege
{
	public ?string $dirname; // dossier du cache
	public int $duration; // durée de vie du cache en minute

	public function __construct(string $dirname, int $duration)
	{
		$this->dirname = $dirname;
		$this->duration = $duration;
	}

	public function write(string $f, $content)
	{
		return file_put_contents($this->filename($f), $content);
	}

	public function inc(string $file)
	{
		$filename = basename($file);
		ob_start();
		require $file;
		$content = ob_get_clean();
		$this->write($filename, $content);
		echo $content;

	}

	public function read(string $f): string|false
	{
		$file = $this->filename($f);

		if(!file_exists($file)) 
			return false;

		$lifetime = time() - filemtime($file) / 60;

		if($lifetime > $this->duration)
			return false;

		return file_get_contents($file);
	}

	public function delete(string $f)
	{
		$file = $this->filename($f);

		if(file_exists($file))
			unlink($file);
	}

	public function clear()
	{
		$files = glob($this->dirname . "/*");

		foreach($files as $file)
			unlink($file);
	}

	private function filename(string $filename): string
	{
		return $this->dirname . "/" . $filename;
	}
}
