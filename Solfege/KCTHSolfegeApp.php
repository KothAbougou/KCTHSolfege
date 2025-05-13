<?php
/**
 * This file is part of the Solfege package.
 * 
 * @copyright (c) KCTH Developpers <solfege@kcth.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/

namespace KCTH\Solfege;

use KCTH\Solfege\KCTHSolfege;
use KCTH\Solfege\KCTHSolfegeApp as App;
use KCTH\Solfege\KCTHSolfegeView as View;
use KCTH\Solfege\KCTHSolfegeRouter as Router;
use KCTH\Solfege\KCTHSolfegeConfig as Config;
use KCTH\Solfege\KCTHSolfegeDatabase as Database;
use KCTH\Solfege\KCTHSolfegeHttpRequest as HttpRequest;
use KCTH\Solfege\event\KCTHSolfegeEventEmitter as EventEmitter;
use KCTH\Solfege\KCTHSolfegeCacheSystem as Cache;
use \Closure;



/**
 * L'application
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
 class KCTHSolfegeApp extends KCTHSolfege
{	

	private static string $name; 		# nom de l'app.
	private static string $lang;		# langue de l'application.
	private static string $favicon;		# favicon de l'app.
	private static string $logo;		# logo de l'app.
	public static string $rapaceNextBaseUrl;

	private static ?App 	 	 $_instance = null; 		# instance de l'app.
	private static ?View 	 	 $view_instance = null; 	# instance de la vue.
	private static ?Router 	 	 $router_instance = null;	# instance du router.
	private static ?Database 	 $db_instance = null; 		# instance de la bdd.
	private static ?EventEmitter $emitter_instance = null; 	# instance du controller l'événements

	private static ?Cache 		 $cache_instance = null; 	# instance du système de cache.
	
	private static bool $unitTester;


	public function __construct()
	{
		parent::__construct();

		$config = Config::single();
		$config->onFile("env");
		self::$rapaceNextBaseUrl = $config->get('RAPACE_NEXT_BASE_URL');

		self::$unitTester = false;
		self::$routes = [];
	}

	/**
	 * Instanciation de l'application
	 */
	public static function open(?string $appName = 'My App'): App
	{
		self::$name = $appName;

		if(self::$_instance === null)
			self::$_instance = new App();

		return self::$_instance;
	}

	/**
	 * Instanciation de la base de donnée.
	 * @return Database
	 */
	public static function getDatabase(): Database
	{
		$config = Config::single();
		$config->onFile('db');

		if(self::$db_instance === null)
			self::$db_instance = new Database(
				$config->get('db_name'),
				$config->get('db_user'),
				$config->get('db_pass'), 
				$config->get('db_host'));

		return self::$db_instance;
	}

	/**
	 * Instanciation d'une autre base de donées
	 * @return Database autre base de donnée
	 */
	public static function getOuterDatabase($db_name, $db_user, $db_pass, $db_host): Database
	{
		return new Database($db_name, $db_user, $db_pass, $db_host);
	}

	/**
	 * Instanciation de la vue.
	 * @return View Vue de l'application.
	 */
	public static function getView(): View
	{
		if(self::$view_instance === null){
			self::$view_instance = new View();
		}

		return self::$view_instance;
	}

	/**
	 * Instanciation du routeur.
	 * @return Router Router de l'application.
	 */
	private static function getRouter(): Router
	{
		if(self::$router_instance === null)
			self::$router_instance = new Router();

		return self::$router_instance;
	}

	/**
	 * Instanciation des requêtes
	 * @return HttpRequest
	 */
	public static function Request(): HttpRequest
	{
		return new HttpRequest();
	}

	public static function getEventEmitter(): EventEmitter
	{
		if(self::$emitter_instance === null)
			self::$emitter_instance = new EventEmitter();

		return self::$emitter_instance;
	}

	/**
	 * Instance du système de cache
	 * @return Cache
	 */
	public static function getCacheSystem(): Cache
	{
		if(self::$cache_instance === null)
			self::$cache_instance = new Cache();

		return self::$cache_instance;
	}

	/**
	 * Définie une route.
	 */
	public static function route($args)
	{
		$args = func_get_args();
		$pattern = $args[0]; $patterns = explode('/', $pattern, 2);
		$handler = $args[1];	
		$name 	 = $args[2] ?? null;
		$middleware = $args[3] ?? [];

		$method = strtolower($patterns[0]);
		$route = '/' . $patterns[1];

		if($name != null)
			foreach(self::$routes as $_route)
				if($name == $_route['name'])
					throw new \Exception("La route \"{$name}\" est déjà attribuée.");

		if($method == "get" && $route == '/')
			$name = self::$homePageRouteName;
		elseif($name == self::$homePageRouteName)
			throw new \Exception("La route \"{$name}\" est réservée à la page d'accueil.");

		self::$routes[] = [
			'method' => $method,
			'route' => $route,
			'handler' => $handler,
			'name' => $name,
			'middleware' => $middleware
		];
	}

	/**
	 * Groupe de routes
	 */
	public static function group(array $filters, Closure $callback)
	{
		$router = self::getRouter();

		$router->group($filters, $callback);
	}

	/**
	 * Middle ware des routes
	 * @author Pierre-Alexandre DAUDET <daudetpierre23@gmail.com>
	 */
	public static function middleware(string $name, callable $handler) 
	{
		$router = self::getRouter();
		$router->filter($name, $handler);
	}

	public static function homePageRouteName(string $route_name)
	{
		self::$homePageRouteName = $route_name;
	}

	/**
	 * Activation des tests unitaires
	 */
	public static function testMode()
	{
		self::$unitTester = true;
	}

	/**
	 * Redirige vers / si le mode test est désactivé.
	 */
	public static function checkTestMode()
	{
		if(self::$unitTester==false)
			header(self::urlFor(self::$homePageRouteName));
	}

	/**
	 * Lance l'application
	 */
	public static function run()
	{
		$router = self::getRouter();

		$router->setGlobalRoutePrefix(self::$www);

		$router->generateRoutes(self::$routes);

		$router->response();
	}


	// GETTERS & SETTERS

	public static function name(string $appName)
	{
		self::$name = $appName;
	}

	public static function appName(): string
	{
		return self::$name;
	}

	public static function lang(string $lang)
	{
		self::$lang = $lang;
	}
	
	public static function appLang()
	{
		return self::$lang;
	}

	public static function www(string $appDirectory)
	{
		self::$www = $appDirectory;
	}

	public static function favicon(string $favicon)
	{
		self::$favicon = $favicon;
	}

	public static function appFavicon(): string
	{
		return self::$favicon;
	}

	public static function logo(string $logo)
	{
		self::$logo = $logo;
	}

	public static function appLogo(): string
	{
		return self::$logo;
	}
}