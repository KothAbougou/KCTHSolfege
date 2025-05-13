<?php

namespace KCTH\Solfege\helper;

use KCTH\Solfege\KCTHSolfegeHelper;


final class KCTHSolfegeUploaderHelper extends KCTHSolfegeHelper
{
	public const DEFAULT_FILE_SIZE_LIMIT = 20971520; // 20 Mo = 20 * (1024^2) Bytes

	public const DEFAULT_VIDEO_EXT_ALLOWED = ["mp4", "avi"];
	public const DEFAULT_PHOTO_EXT_ALLOWED = ["png", "jpg", "jpeg"];

	private const DEFAULT_EXT_ALLOWED = [
		SELF::DEFAULT_VIDEO_EXT_ALLOWED,
		SELF::DEFAULT_PHOTO_EXT_ALLOWED								
	];

	/**
	 * Système de Téléchargement de fichiers externes dans l'application.
	 * @param  array  $files     tous les $_FILES['input_file_name']
	 * @param  string $directory dossier de télégargement
	 * @param  array  $options   les options de téléchargements.
	 * @return [type]            [description]
	 */
	public static function uploadFiles(array $files, string $directory, array $options = [])
	{
		$file_limit_size 		 = $options['SIZE_LIMIT'] ?? SELF::DEFAULT_FILE_SIZE_LIMIT;
		$file_prefix 			 = $options['FILE_PREFIX'] ?? null;
		$file_suffix 			 = $options['FILE_SUFFIX'] ?? null;
		$file_extensions_allowed = $options['EXT_ALLOWED'] ?? array_merge(SELF::DEFAULT_EXT_ALLOWED); 
		$file_to_unlink			 = $options['UNLINK_FILE'] ?? false;



		foreach($files as $key => $file)
		{
			if($file['size'] > 0 && $file['size'] < $file_limit_size)
			{
				//dossier
				$directory = SITE_ROOT . $directory;

				//nom du fichier
				$filename = $file_prefix. basename($file["name"]) . $file_suffix;
				$filename = $directory . $filename;


				// vérification de l'extension
				$file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				if(self::file_extension_allow($file_ext, $file_extensions_allowed))

				// suppression de l'actuel
				if($file_to_unlink != false)
					if(file_exists($file_to_unlink))
						if(!unlink($file_to_unlink))
							return false;

				// si le nouveau fichier n'existe pas déjà dans le $directory
				if(!file_exists($filename))
				{	
					// ajout du nouveau sinon erreur
					if(!move_uploaded_file($file["tmp_name"], $filename))
						return false;
					return true;

				}else return true;
			}
		}
	}

	/**
	 * Vérifie si l'extension est autorisée.
	 * @param  string $file_ext           [description]
	 * @param  array  $extensions_allowed [description]
	 * @return [type]                     [description]
	 */
	private static function file_extension_allow(string $file_ext, array $extensions_allowed = []): bool
	{
		if($extensions_allowed == [])
			foreach(SELF::DEFAULT_EXT_ALLOWED as $allowed)
				if($file_ext == $allowed)
					return true;
		else
			foreach($extensions_allowed as $allowed)
				if($file_ext == $allowed)
					return true;

		return false;
	}
}