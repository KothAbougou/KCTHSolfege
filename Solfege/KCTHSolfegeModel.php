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
use KCTH\Solfege\KCTHSolfegeDatabase as Database;
use KCTH\Solfege\helper\KCTHSolfegeStatisticsHelper;
use KCTH\Solfege\interface\KCTHSolfegeRepositoryInterface;
use \PDO;

/**
 * Modèle de Solfege, dépendant de la base de donnée.
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
abstract class KCTHSolfegeModel extends KCTHSolfege
{
	protected static Database $bdd;
    protected ?string $table;


	public function __construct()
	{
		global $bdd;
        
		self::$bdd = $bdd;
        $this->table = $this->ASSIGN_DB_TABLE();
	}
    
    PROTECTED FUNCTION SET_ATTR(string $set_field, $new_val, string $where_field, $where_field_val)
    {

        if($this->table !== null)
        {
            $new_val  = $this->NORMALISE_FIELD_VALUE($set_field, $new_val);

            $req = self::$bdd->prepare("UPDATE {$this->table} SET {$set_field} = ? WHERE {$where_field} = ?");

            return $req->execute(array($new_val, $where_field_val)) ?
                $new_val : null;
        }
    }

    PROTECTED FUNCTION GET_ATTR(string $field)
    {
        return $this->$field;
    }

    /**
     * Requête publiques restraintent à la table du Model
     */
    PUBLIC FUNCTION REQ(string $PDO_METHOD, $statement)
    {   
        if($this->table == null)
            throw new \Exception("Impossible d'exécuter la requête \"{$statement}\" car ". __CLASS__ . " n'est pas associée à une table sql.");

        if(!str_contains($statement, $this->table))
            throw new \Exception("Impossible d'exécuter la requête \"{$statement}\" car ". __CLASS__."::{$PDO_METHOD}() est limité à la table ". $this->table);

        return self::$bdd->{$PDO_METHOD}($statement);
    }

    PUBLIC FUNCTION DB_TABLE()
    {
        return $this->table ?? throw new \Exception(__CLASS__ . " n'est associé à aucune table sql.");
    }

    /**
     * Les statistiques liées au model
     */
    PUBLIC FUNCTION STATISTICS(string $stat_helper_name = "AppStatistics", array $vars = []): KCTHSolfegeStatisticsHelper
    {
        $stat_helper_name = "\\app\\helper\\statistics\\{$stat_helper_name}Stats";

        $vars[':model'] = $this;
        $vars = array_reverse($vars);
        
        return new $stat_helper_name($vars);
    }

    /**
     * FORMATE LES DONNEES
     */
    PROTECTED FUNCTION NORMALISE_FIELD_VALUE(string $field, string|int $value): string|int
    {
        switch($field)
        {   
            # px_fact_utilisateur
            case 'util_password':
                $HELP = SELF::SOLFEGE_HELPER('PasswordEncoderHelper');
                $formated_val = $HELP::encrypt($value, SELF::$APP_PWD_KEY);
                break;

            default: return $value;
        }

        return $formated_val;
    }

    PROTECTED FUNCTION ASSIGN_DB_TABLE()
    {
        foreach(self::$db_tables as $key => $db_table)
            if("app\\model\\". $key . "Model" == get_class($this))
                return $db_table;

        return null;
    }

    PROTECTED FUNCTION USE_REPOSITORY($repository_name): KCTHSolfegeRepositoryInterface
    {
        $repository_name = "\\app\\model\\repository\\{$repository_name}Repository";
        
        return new $repository_name();
    }



    protected function normalise(string $field, string|int $value): string|int
    {
        return $this->NORMALISE_FIELD_VALUE($field, $value);
    }

    /**
     * Retourne le résultat d'une requête en JSON
     * @author Pierre-Alexandre DAUDET <daudetpierre23@gmail.com>
     */
    PROTECTED STATIC FUNCTION FETCH_JSON($req)
    {   
        return $req->rowCount() > 0 ?
            json_encode($req->fetchAll(PDO::FETCH_OBJ)) : null;
    }


    /**
     * Retourne une liste d'objet
     */
    PROTECTED STATIC FUNCTION FETCH_MODELS(string $model, $req, int $element = -1): null|array|object
    {   
        $class = str_starts_with($model, "\\app\\model\\") || str_starts_with($model, "app\\model\\") ? 
            $model :
            "\\app\\model\\" . $model . "Model";

        switch($element)
        {
            case -1:
            return $req->rowCount() > 0 ?
                $req->fetchAll(PDO::FETCH_CLASS, $class) : null;

            default:
            return $req->rowCount() > 0 ?
                $req->fetchAll(PDO::FETCH_CLASS, $class)[$element] : null;
        }
    }

    /**
     * Retourne un unique object précis.
     */
    PROTECTED STATIC FUNCTION FETCH_MODEL(string $model, $req, int $element = 0): null|object
    {
        return SELF::FETCH_MODELS($model, $req, $element);
    }

    /**
     * Retourne une liste d'objet précis OU $default
     */
    PROTECTED STATIC FUNCTION FETCH_MODELS_OR_DEFAULT(string $model, $req, mixed $default = null, int $element = -1): mixed
    {
        return SELF::FETCH_MODELS($model, $req, $element) ?? $default;
    }

    /**
     * Retourne une liste de réflexion d'Objet
     */
    PROTECTED STATIC FUNCTION FETCH_REFLECTIONS(string $model, $req, int $element = -1): null|array|object
    {   
        $class = str_starts_with($model, "\\app\\model\\") || str_starts_with($model, "app\\model\\") ? 
            $model :
            "\\app\\model\\" . $model . "Model";

        switch($element)
        {
            case -1:
            return $req->rowCount() > 0 ?
                $req->fetchAll(PDO::FETCH_CLASS, 'ReflectionClass',[$class]) : null;

            default:
            return $req->rowCount() > 0 ?
                $req->fetchAll(PDO::FETCH_CLASS, 'ReflectionClass',[$class])[$element] : null;
        }
    }

    /**
     * Retourne un unique object précis.
     */
    PROTECTED STATIC FUNCTION FETCH_REFLECTION(string $model, $req, int $element = 0): null|object
    {
        return SELF::FETCH_REFLECTIONS($model, $req, $element);
    }

}