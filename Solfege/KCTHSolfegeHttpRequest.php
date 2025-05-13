<?php
namespace KCTH\Solfege;

use \KCTH\Solfege\KCTHSolfege;


class KCTHSolfegeHttpRequest extends KCTHSolfege
{
	private array $params;
	private array $cookies;
	

	public function __construct()
	{
		// $_REQUEST = $_GET + $_POST + $_COOKIE
		$this->params = array_merge($_GET, $_POST, $_FILES);
		$this->cookies = $_COOKIE;
	}

	/**
	 * Récupère un paramètre de la requête HTTP
	 * @param  string $key 
	 */
	public function param(string $key)
	{
		return $this->params[$key] ?? null;
	}

	/**
	 * Récupère tous les paramètres d'une requête HTTP
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * Ajouter un paramètre
	 */
	public function addParam(string $key, $value)
	{
		$this->params[$key] = $value;
	}

	/**
	 * Retourne la réponse SERVER
	 */
	public function getResponse(?string $key = null): null|string|array
	{
		return is_null($key) ? $_SERVER : ($_SERVER[$key] ?? null);
	}

	/**
	 * Récupère tous les cookies d'une requête HTTP
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}

	/**
	 * Récupère la valeur du cookie {name} d'une requête HTTP
	 * @param name cookie's name
	 */
	public function getCookie(string $name): string|null
	{
		return array_key_exists($name, $this->cookies) ? $this->cookies[$name] : null;
	}
}