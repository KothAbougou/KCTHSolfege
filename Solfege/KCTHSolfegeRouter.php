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

/**
 * (c) Phroute
 * @link https://github.com/mrjgreen/phroute Doc Phroute
 */
use \Phroute\Phroute\RouteCollector;

/**
 * Router de Solfege.
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
final class KCTHSolfegeRouter extends RouteCollector
{
    /**
     * Permet de préfixer toutes les routes du projet.
     * 
     *   * L'attribut private \Phroute\Phroute\RouteCollector::$globalRoutePrefix
     *     est devenu protected pour notre projet.
     *     
     * @param string $globalRoutePrefix Préfix des routes (Ex: $prefix . '/' . $route);
     */
    public function setGlobalRoutePrefix(string $globalRoutePrefix): void
    {
        $globalRoutePrefix .= !str_ends_with('/', $globalRoutePrefix) ? '/' : null;
        $globalRoutePrefix = '/' . explode('/', $globalRoutePrefix, 4)[3];
        $globalRoutePrefix = preg_replace('/(^\/)|(\/$)/', '', $globalRoutePrefix);

        $this->globalRoutePrefix = $globalRoutePrefix;
    }

    public function generateRoutes(array $routes)
    {
        $sorted_routes = [];
        $rest_routes = [];

        foreach($routes as $route) $route['method'] == "delete" ?
            $sorted_routes[] = $route : $rest_routes[] = $route;
        

        foreach(array_merge($sorted_routes, $rest_routes) as $route)
            $this->{$route['method']}($route['route'], $route['handler'], $route['middleware'] ?? []);

        
    }

    public function response()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Ajouter les en-têtes CORS nécessaires
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
            // Terminer la réponse avec un code de statut 200 (OK)
            http_response_code(200);
            exit();
        }


        $dispatcher = new \Phroute\Phroute\Dispatcher($this->getData());

        $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        // Print out the value returned from the dispatched function
        echo $response;
    }
}