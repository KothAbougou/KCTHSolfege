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

use KCTH\KCTH\KCTH;
use KCTH\Solfege\KCTHSolfegeAuth as Auth;
use KCTH\Solfege\KCTHSolfegeConfig as Config;
use KCTH\Solfege\KCTHSolfegeHelper as Helper;
use KCTH\Solfege\KCTHSolfegeSession as Session;
use KCTH\Solfege\tools\KCTHSolfegePeriodTool as PeriodTool;

use \DateTime;
use \DateTimeImmutable;
use \NumberFormatter;

/**
 *    +-------+     Solfege by (c) KCTH DEVELOPER.
 *    KCTH    |     Your self Framework!
 *    DEVELOPER     — Framework PHP.
 *  ============= 
 *                  
 *  
 * @package KCTH\Solfege
 * @version beta
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
abstract class KCTHSolfege implements KCTH
{
    # env.conf.php
    protected static string     $ENVMODE;
    protected static string     $APP_PWD_KEY;
    protected static bool       $CACHE_SYSTEM;

    # view.conf.php
    protected static string     $viewPath;
    protected static string     $viewExtension;
    protected static string     $viewPartialsPath;
    protected static string     $templateTagsPath;
    protected static string     $templateClass;
    protected static string     $layoutPath;
    protected static string     $layoutName;
    protected static string     $layoutElementaryName;
    protected static string     $cacheDirectory;


    # db_tables.conf.php
    protected static array      $db_tables;

    #
    protected static string     $www;
    protected static array      $routes;
    protected static string     $homePageRouteName;

    # les rôles
    protected static array      $ALL_ROLES;
    protected const ROLE_FOUND    = 'FOND';
    protected const ROLE_MANAGER  = 'MANAGER';
    protected const ROLE_COM      = 'COM';
    protected const ROLE_TECH     = 'TECH';
    protected const ROLE_DEV      = 'DEV';
    protected const ROLE_NONE     = 'NONE';

    protected static array  $TVA = [];       # Taxes sur valeur joutée de l'app.
    protected static array $SYNC = [];

    # Les accès
    protected const ERR_ACCESS_DENIED = "ERROR| Accès Ou Fonctionnalité Non Autorisée.";

    # Encrypteur
    private static Encoder $ENCODER;

    private static ?Session $session_instance = null; # instance des sessions.


    public function __construct()
    {
        $config = Config::single();

        $config->onFile('env');
        self::$ENVMODE = $config->get('ENV_MODE');
        self::$APP_PWD_KEY = $config->get('APP_PWD_KEY');
        self::$CACHE_SYSTEM = $config->get('CACHE');

        $config->onFile('view');
        self::$viewPath             = $config->get('viewPath');
        self::$viewExtension        = $config->get('viewExtension');
        self::$viewPartialsPath     = $config->get('viewPartialsPath');
        self::$templateTagsPath     = $config->get('templateTagsPath');
        self::$templateClass        = $config->get('templateClass');

        self::$layoutPath           = $config->get('layoutPath');
        self::$layoutName           = $config->get('layoutName');
        self::$layoutElementaryName = $config->get('layoutElementaryName');
        self::$cacheDirectory       = $config->get('cacheDirectory');


        $config->onFile('db_tables');
        self::$db_tables = $config->getAll();

        self::$homePageRouteName = 'home_page';

        self::$ALL_ROLES = [        // Cet ordre est important
            SELF::ROLE_FOUND,
            SELF::ROLE_MANAGER,
            SELF::ROLE_COM,
            SELF::ROLE_TECH,
            SELF::ROLE_DEV,
            SELF::ROLE_NONE,
        ];
    }

    /**
     * Change le layout de l'application.
     */
    public static function setLayout($layoutName): void
    {
        self::$layoutName = $layoutName;
    }

    protected const URL_VOID = "javascript:void(0)";

    /**
     * L'URL d'une roure
     * @param  string $route_name 
     * @param  array  $params Les valeurs paramétriques de l'URL
     * @return string|null
     */
    protected static function urlFor(string $route_name, $params = []): ?string
    {
        $tag = $route_name;
        $route_name = str_replace('./', '', $route_name);


        foreach(self::$routes as $route)
            if($route_name == $route['name'])
            {
                $path = explode('/', $route['route'], 2)[1];
                $url = '/' . $path;

                $pattern = "/{.*?}/";
                preg_match_all($pattern, $url, $matches);

                if(count($params) > 0 && count($params) == count($matches[0]))
                    for ($i=0; $i < count($params); $i++)
                        $url = preg_replace($pattern, $params[$i], $url, 1);

                $link = str_starts_with($tag, './') ? $url : self::$www . $url;
                

                return str_ends_with($link, '?') ? str_replace('?', '', $link) : $link;
            }

        return null;
    }

    /**
     * L'URL d'une asset
     * @param  string $source
     */
    protected static function urlAssets(string $source): string
    {
        $folderFile = self::folderFile($source);
        $folder = $folderFile['folder'];
        $file = $folderFile['file'];

        $link = self::$www . "/public/assets/{$folder}/{$file}";

        return $link;
    }

    /**
     * Protection élémementaire des données saisies
     */
    PROTECTED STATIC FUNCTION SANITIZE(&...$inputs)
    {
        foreach($inputs as &$input)
            if(is_array($input))
                SELF::SANITIZE(...$input);
            else $input = htmlspecialchars(trim(addslashes($input)));
    }

    protected static function divideStr($chaine, $delimiteur, $n) {
        $morceaux = explode($delimiteur, $chaine);
        $longueur = count($morceaux);
        $taillePartie = ceil($longueur / $n);
        $resultat = array();

        for ($i = 0; $i < $n; $i++) {
            $partie = array_slice($morceaux, $i * $taillePartie, $taillePartie);
            $resultat[] = implode($delimiteur, $partie);
        }

        return $resultat;
    }

    /**
     * NOMMENCLATURE
     *
     *  . @: folder@file.ext => .../folder/.../file.ext
     */
    protected static function folderFile(string $source, string $separator = '@')
    {
        $folderFile = explode($separator, $source);

        switch($separator)
        {
            case '@': $folderFile[1] = preg_replace('/\|.*/', '', $folderFile[1]);
        }

        return
        array(
            'folder' => $folderFile[0],
            'file' => $folderFile[1]
        );
    }

    PROTECTED STATIC FUNCTION REDIRECT_TO(string $location)
    {
        if(str_starts_with($location, "https://") || str_starts_with($location, "http://"))
            header('Location:' . $location);
        else
            header('Location:' . self::$www . $location);
    }


    PROTECTED STATIC FUNCTION CREATE_AUTH(string $auth_KEY, string|int $auth_VALUE)
    {
        Auth::select($auth_KEY);
        Auth::sessionStart($auth_VALUE);
    }

    PROTECTED STATIC FUNCTION GET_AUTH(string $auth_KEY)
    {
        Auth::select($auth_KEY);
        return Auth::get();
    }

    PROTECTED STATIC FUNCTION AUTH(string $auth_KEY = 'AUTH')
    {
        Auth::select($auth_KEY);
        return Auth::getModel();
    }

    PROTECTED STATIC FUNCTION UNSET_AUTH(string $auth_KEY)
    {
        Auth::select($auth_KEY);
        Auth::unset();
    }

    PROTECTED STATIC FUNCTION AUTH_KEY_SECURITY($auth_KEY):bool
    {
        return !str_starts_with($auth_KEY, "Auth");
    }

    PROTECTED STATIC FUNCTION DESTROY_ALL_SESSIONS()
    {
        Session::destroy();
    }

    protected static function sessions()
    {
        return self::session()::getAll();
    }

    PROTECTED STATIC FUNCTION session(string|int|null $session_key = null, $session_value = null)
    {
        // instanciation
        if($session_key == null && $session_value == null)
        {    
            if(self::$session_instance === null)
                self::$session_instance = new Session();

            return self::$session_instance;

        // creation
        }elseif($session_key != null && $session_value != null){
            self::session()::create($session_key, $session_value);
            return true;

        // getter
        }elseif($session_key != null)
        {
            return self::session()::get($session_key);
        }
    }

    /**
     * FRAMEWORK HELPERS
     */
    PROTECTED STATIC FUNCTION SOLFEGE_HELPER(string $helper): Helper
    {
        $helper_name = "\\KCTH\\Solfege\\helper\\KCTHSolfege{$helper}";
        
        return new $helper_name();
    }

    ////////////////
    // LES ACCESS //
    ////////////////

    PROTECTED STATIC FUNCTION DEV_ACCESS_BYPASSABLE(): bool
    {
        return SELF::$ENVMODE == 'dev' && SELF::AUTH()->Role->is(SELF::ROLE_DEV);
    }

    /**
     * MIDDLE WARE: Autorisation d'accès à des utilisateurs ou roles précis.
     * @param $allowed id ou nom de role
     *
     * $ENVMODE 'dev' => Accès forcément autorisé au développeur.
     */
    PROTECTED STATIC FUNCTION ALLOW_ACCESS_TO(?array $allowed = [], bool $EXIT = true )
    {
        if(SELF::DEV_ACCESS_BYPASSABLE())
            return true;

        if(!empty($allowed))
            foreach($allowed as $allow)
                if (SELF::AUTH()->util_id == $allow || SELF::AUTH()->Role->sigle() == $allow)
                    return true;

        return $EXIT ?
        exit(SELF::ERR_ACCESS_DENIED) : false;
    }

    /**
     * MIDDLE WARE: Interdiction d'accès à des utilisateurs ou roles précis.
     * @param $denied id ou nom de role
     *
     * $ENVMODE 'dev' => Accès non-interdit au développeur.
     */
    PROTECTED STATIC FUNCTION DENY_ACCESS_TO(?array $denied = [], bool $EXIT = true)
    {
        if(SELF::DEV_ACCESS_BYPASSABLE())
            return false;

        if(!empty($denied))
            foreach($denied as $deny)
                if (SELF::AUTH()->util_id == $deny || SELF::AUTH()->Role->sigle() == $deny)
                    return $EXIT ?
                    exit(SELF::ERR_ACCESS_DENIED) : true;

        return false;
    }


    /**
     * MIDDLE WARE: Autorisation d'accès à des utilisateurs ou roles précis. Du Fondateur au $until_role. (ordre: TOP to BOTTOM)
     */
    PROTECTED STATIC FUNCTION ALLOW_ACCESS_UNTIL(?string $until_role, ?array $id_allowed_too = [], bool $EXIT = true)
    {
        if(SELF::ALLOW_ACCESS_TO($id_allowed_too, false)) return true;

        $ALLOWED_ROLES = [];

        if(!empty($until_role))
            foreach(SELF::$ALL_ROLES as $ROLE)
            {
                $ALLOWED_ROLES[] = $ROLE;
                if($ROLE == $until_role) break;
            }

        return SELF::ALLOW_ACCESS_TO($ALLOWED_ROLES, $EXIT);

    }

    /**
     * MIDDLE WARE: Autorisation d'accès à des utilisateurs ou roles précis. Du Fondateur au $until_role. (ordre: BOTTOM to TOP)
     */
    PROTECTED STATIC FUNCTION DENY_ACCESS_UNTIL(?string $until_role, ?array $id_denied_too, bool $EXIT = true)
    {
        if(SELF::DENY_ACCESS_TO($id_denied_too, false)) return true;

        $DENIED_ROLES = [];

        if(!empty($until_role))
            foreach(array_reverse(SELF::$ALL_ROLES) as $ROLE)
            {
                $DENIED_ROLES[] = $ROLE;
                if($ROLE == $until_role) break;
            }

        return SELF::DENY_ACCESS_TO($DENIED_ROLES, $EXIT);
    }

    //////////
    // ROLE //
    //////////

    // PROTECTED STATIC FUNCTION ROLE_IS(string $role, ?User $u = null) :bool
    // {
    //     if(is_null($u))
    //     {
    //         $User = SELF::AUTH();

    //         if(SELF::$ENVMODE == "dev" && $User->Role->sigle() == SELF::ROLE_DEV)
    //             return true;

    //     }else $User = $u;

    //     return $User->Role->sigle() == $role;
    // }

    // PROTECTED STATIC FUNCTION ROLE_FOUND(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_FOUND, $User);
    // }
    // PROTECTED STATIC FUNCTION ROLE_MANAGER(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_MANAGER, $User);
    // }
    // PROTECTED STATIC FUNCTION ROLE_COM(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_COM, $User);
    // }
    // PROTECTED STATIC FUNCTION ROLE_TECH(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_TECH, $User);
    // }
    // PROTECTED STATIC FUNCTION ROLE_DEV(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_DEV, $User);
    // }
    // PROTECTED STATIC FUNCTION ROLE_NONE(?User $User = null)
    // {
    //     return SELF::ROLE_IS(SELF::ROLE_NONE, $User);
    // }

    /**
     * "access" => ["ACCESS_TYPE" => [array(roles_id), until_role]]
     */
    PROTECTED STATIC FUNCTION ACCESS(string $ACCESS_TYPE, ?array $roles_id, ?string $until_role = null): bool
    {

        if($ACCESS_TYPE == "ALLOW") $ACCESS_FUNC = "ALLOW_ACCESS_UNTIL";
        if($ACCESS_TYPE == "DENY") $ACCESS_FUNC = "DENY_ACCESS_UNTIL";

        $accessible = self::{$ACCESS_FUNC}($until_role, $roles_id, false);

        switch($ACCESS_FUNC)
        {
            case 'ALLOW_ACCESS_UNTIL': return $accessible; break;
            case 'DENY_ACCESS_UNTIL': return !$accessible; break;
        }
    }

    // LES CHIFFRES
    protected static function zerofill($value, int $size): string
    {
       return str_pad($value, $size, '0', STR_PAD_LEFT);
    }


    ///////////////
    // LES DATES //
    ///////////////
    
    protected const dUndefined = "0000-00-00";
    protected const tUndefined = "00:00:00";
    protected const dtUndefined = self::dUndefined ." ". self::tUndefined;

    protected static function dtEmpty($date)
    {
        return is_null($date) || $date == "" || $date == self::dtUndefined || $date == self::dtFormat(self::dtUndefined, "Y-m-d");
    }

    protected static function dtString($date, $format = "%d %B %Y"): string
    {
        return strftime($format, strtotime($date));
    }

    protected static function dtFormat(?string $date, string $pattern = "d F Y", ?string $setter = null): ?string
    {
        if(is_null($date) || self::dtEmpty($date))
            return null;

        $dt = new DateTime($date);
        if(!empty($setter)) $dt->modify($setter);
        return $dt->format($pattern);
    }

    /**
     * Retourne une DateTime()
     */
    protected static function dt(?string $date): ?DateTime
    {
        return !is_null($date) ? new DateTime($date) : null;
    }

    /**
     * Retourne la date du dernier jour du mois d'une date courrante
     */
    protected static function dtEndMonth(string $date): string
    {
        return date('Y-m-t', strtotime($date));
    }

    /**
     * Renvoi les dates au bornes annuelles $borne = "Y" ou border
     */
    protected static function dtBorned(string $date, ?string $borne = null): ?PeriodTool
    {
        switch($borne)
        {
            case "Y": $period = new PeriodTool(self::dtFormat($date, "Y-01-01"), self::dtFormat($date, "Y-12-31")); break;
            case "m": $period = new PeriodTool(self::dtFormat($date, "Y-m-01"), self::dtEndMonth(self::dtFormat($date, "Y-m-01"))); break;
            default: $period = null;
        }

        return $period;
    }

    protected static function dtDiff(string $date1, string $date2, string $pattern = '%R%a jours'): string
    {
        $origin = new DateTimeImmutable($date1);
        $target = new DateTimeImmutable($date2);
        $interval = $origin->diff($target);

        return $interval->format($pattern);
    }

    /**
     * Date courante.
     *
     * $pattern "d/m/Y"         : 05/05/2002
     * $pattern "h:i:s"         : 06:50:00 (h: format 12h / H: format 24h)
     * $pattern "l d m Y h:i:s" : Dimanche 05 05 2002
     * $pattern "l d F Y h:i:s" : Dimanche 05 Mai 2002
     */
    protected static function dtNow(string $pattern = "Y-m-d H:i:s", ?string $setter = null): string   
    {
        $dt = new DateTime();
        if(!empty($setter)) $dt->modify($setter);
        return $dt->format($pattern);
    }

    protected static function dtAgo($time, string $pattern = 'Il y a :x'): string
    {
        $time_difference = time() - strtotime($time);

        if( $time_difference < 1 ) { return 'Il y a 1 seconde'; }
        $condition = array( 12 * 30 * 24 * 60 * 60 =>  'an',
                    30 * 24 * 60 * 60       =>  'mois',
                    24 * 60 * 60            =>  'jour',
                    60 * 60                 =>  'heure',
                    60                      =>  'minute',
                    1                       =>  'seconde'
        );

        foreach( $condition as $secs => $str )
        {
            $d = $time_difference / $secs;

            if( $d >= 1 )
            {
                $t = round( $d );
                $replace = $t . ' ' . $str . ( $t > 1 ? 's' : '' );
                $replace = str_replace('ss', 's', $replace);
                return str_replace(":x", $replace, $pattern);
            }
        }
    }

    protected static function dtRemain($time, string $pattern = "Dans :x"): ?string
    {
        $time_difference = strtotime($time) - time();

        if ($time_difference < 0) return self::dtAgo($time);

        $units = [
            365 * 24 * 60 * 60  => ['an', 'ans'],
            30 * 24 * 60 * 60   => ['mois', 'mois'],
            7 * 24 * 60 * 60    => ['semaine', 'semaines'],
            24 * 60 * 60        => ['jour', 'jours'],
            60 * 60             => ['heure', 'heures'],
            60                  => ['minute', 'minutes']
        ];

        foreach($units as $secs => $labels){
            if($time_difference >= $secs){
                $quantity = floor($time_difference / $secs);
                $label = ($quantity > 1) ? $labels[1] : $labels[0];

                if ($secs == 365 * 24 * 60 * 60) { // special year
                    $remainingMonths = floor(($time_difference - ($quantity * $secs)) / (30 * 24 * 60 * 60));
                    return str_replace(":x", "$quantity $label et $remainingMonths mois", $pattern);
                }

                return str_replace(":x", "$quantity $label", $pattern);
            }
        }

        // Default case for seconds
        $seconds = ($time_difference < 60) ? $time_difference : $time_difference % 60;
        return str_replace(":x", "un instant", $pattern);
    }

    protected static function dtMoment(?string $moment = null, ?string $date = null): ?string
    {
        $hour = null;

        switch($moment)
        {
            case 'midi':    $hour = "12:00:00"; break;
            case 'minuit':  $hour = self::tUndefined; break;
            case 'open':    $hour = "09:00:00"; break;
            case 'close':   $hour = "18:00:00"; break;
            default:        $hour = self::tUndefined; break;
        }

        if(is_null($date))
            return $hour;
        else{
            return self::dtFormat($date, "Y-m-d ") . $hour;
        }
    }

    protected static function dtPeriode(string $dtFrom, ?string $dtTo = null): PeriodTool
    {
        return new PeriodTool($dtFrom, $dtTo);
    }

    protected static function dCheck(?string $date): bool
    {
        return !empty($date) 
            && preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) 
            && $date != self::dUndefined;
    }

    protected static function tCheck(?string $time): bool
    {
        return !empty($time) 
            && preg_match("/^\d{2}:\d{2}:\d{2}$/", $time) 
            && $time != self::tUndefined;
    }


    protected static function dtCheck(?string $datetime): bool
    {
        return !empty($datetime) 
            && preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $datetime) 
            && $datetime != self::dtUndefined;
    }

    ///////////
    // DEBUG //
    ///////////

    protected static function dump($var, ?string $message = null, bool $exit = true)
    {
        if($message)
            echo <<< HTML
                <div><span style="background-color: yellow; color: black;"><b><i>var_dump() :</i></b><br>{$message}</span></div>
            HTML;

        echo "<pre>";
        var_dump($var);
        echo "</pre>";
        $exit ? exit() : null;
    }

    protected static function print($var, ?string $message = null, bool $exit = true)
    {
        if($message)
            echo <<< HTML
                <div><span style="background-color: yellow; color: black;"><b><i>print_r() :</i></b><br>{$message}</span></div>
            HTML;
        echo "<pre>";
        print_r($var);
        echo "</pre>";
        $exit ? exit() : null;
    }

    //////////////
    // CURRENCY //
    //////////////

    /**
     * Ajoute le symbole de la devise monétaire
     */
    protected static function currency(null|float|int $value = 0, $symbol = "EUR"): string
    {
        $fmt = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);

        return $fmt->formatCurrency($value, $symbol);
    }

    protected static function wrapNumber(float $number)
    {
        if ($number < 1000000) {
            return number_format($number, 2, ',', ' ');
        } elseif ($number < 1000000000) {
            $millions = $number / 1000000;
            return number_format($millions, 2, ',', ' ') . ' M';
        } else {
            $milliards = $number / 1000000000;
            return number_format($milliards, 2, ',', ' ') . ' Md';
        }
    }

    /**
     * Marge commerciale
     */
    protected static function margeCommerciale(?float $cout = 0, ?float $gain = 0, string $mode = "EUR", $precision = 1): float
    {
        switch($mode)
        {
            case 'EUR': return $gain - $cout;
            case '%': return round((($gain - $cout) * 100) / self::notZero($gain), $precision);
        }
    }

    protected static function wrapCurrency(float $number)
    {
        return self::wrapNumber($number) . " €";
    }

    /**
     * Retourne des TVA
     */
    protected static function tva($index): float
    {
        return self::$TVA[$index];
    }

    /**
     * Retourne des TVA
     */
    public static function setTVA($TVA)
    {
        self::$TVA = $TVA;
    }

    /**
     * Ajoute ou retranche une taxe
     */
    protected static function addTaxe(?float $value, float $taxe): float
    {
        return $value + self::portionOf($value, $taxe);
    }

    /**
     * Retourne une portion d'une valeur
     */
    protected static function portionOf(?float $value, float $percent): float
    {
        return $percent * $value / 100;
    }

    /**
     * Produit en croit
     * $value_ref = $proportion_ref => $value_needed = ?
     */
    protected static function proportionOf(float $value_needed, float $proportion_ref, float $value_ref): float
    {
        return $value_needed * $proportion_ref / self::notZero($value_ref);
    }

    /**
     * Retourne le pourcentage d'une portion
     */
    protected static function percentOf(?float $value, float $portion, float $limit = 100): float
    {
        $percent = $value != 0 ? 
            $portion * 100 / $value : 0;

        return $percent <= $limit ? $percent : $limit;
    }

    /**
     * Affiche le symbole `%` après une valeur
     */
    protected static function percent(?float $value): string
    {
        return $value . " %";
    }

    /**
     * Synchronisation d'applications C&D
     */
    public static function SYNC(string $where, bool $mode = false)
    {
        if($mode)
            SELF::$SYNC[$where] = $mode;
        else
            return SELF::$SYNC[$where] ?? false;
    }

    /**
     * Correction d'accents
     */
    public static function correctAccent($message)
    {
        return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }

    /**
     * implode avec encapsulation
     * @param string[2] $with
     */
    protected static function implode(string $glue, array $pieces, string $with): string
    {
        return $with[0] . implode($glue, $pieces) . $with[1];
    }

    /**
     * explode avec encapsulation
     * @param string[2] $with
     */
    protected static function explode(string $separator, string $implode, ?string $with = null): array
    {
        if(!is_null($with))
            $implode = str_replace([$with[0], $with[1]], "", $implode);

        return explode($separator, $implode);
    }

    protected static function removeAccents(string $text)
    {
        return strtr(utf8_decode($text), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ«»'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY  ');
    }

    protected static function json_decode(string $json, ?bool $associative = null, int $depth = 512, int $flags = 0): mixed
    {
        return json_decode($json, $associative, $depth, $flags);
    }

    /**
     * Vérifie si une chaine contient du HTML
     */
    protected static function isHtml(string $chaine): bool
    {
        return $chaine !== strip_tags($chaine);
    }

    /**
     * Retoune $number ou 1 si $number = 0;
     */
    protected static function notZero(?float $number = 0): float
    {
        return $number == 0 ? 1 : $number;
    }
}