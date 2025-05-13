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
use KCTH\Solfege\KCTHSolfegeRepository;

/**
 * Le Helper de Solfege
 * @package KCTH\Solfege
 * @author Koth R. Abougou <richard.abougou@gmail.com>
 */
abstract class KCTHSolfegeHelper extends KCTHSolfege
{	
    PROTECTED CONST ERR_ALL_FIELDS_REQUIRED      = "Tous les champs sont obligatoire! ";
    PROTECTED CONST ERR_SOME_FIELDS_REQUIRED     = "Certains champs sont obligatoire! ";
    PROTECTED CONST ERR_ATLEAST_REQUIRED_FIELDS  = "Les champs obligatoires doivent être renseignés. ";
    PROTECTED CONST ERR_INVALID_EMAIL_ADRESS     = "L'adresse email est invalide. ";
    PROTECTED CONST ERR_ACTION_NOT_POSSIBLE      = "Action Impossible. ";
    PROTECTED CONST ERR_DELETE_NOT_POSSIBLE      = "Suppression Impossible. ";
    PROTECTED CONST ERR_SET_NOT_POSSIBLE         = "Edition Impossible. ";
    PROTECTED CONST ERR_ADD_NOT_POSSIBLE         = "Ajout Impossible. ";
    PROTECTED CONST ERR_NO_CHANGE_DETECTED       = "Aucun changement détecté. ";
    PROTECTED CONST ERR_INPUTS_MISSING           = "Inputs Manquants. ";
    PROTECTED CONST ERR_INPUTS_OR_FIELDS_MISSING = "Inputs ou Champs Manquants. ";
    PROTECTED CONST ERR_SQL_REQUEST              = "Erreur SQL. ";

    PROTECTED CONST ABSTRACTED = true;
    
    /**
     * Permet au Helper de travailler avec un autre Helper
     * @return KCTHSolfegeHelper Co-Helper
     */
    PROTECTED STATIC FUNCTION WORK_WITH(string $helper_name): KCTHSolfegeHelper
    {
        $helper_name = "\\app\\helper\\{$helper_name}";
        
        return new $helper_name();
    }

    /**
     * Permer au Helper d'utiliser un Repository.
     */
    PROTECTED STATIC FUNCTION GET_REPOSITORY($repository_name, bool $abstracted = false): KCTHSolfegeRepository|string
    {
        $repository_name = "\\app\\model\\repository\\{$repository_name}Repository";
        
        return !$abstracted ? new $repository_name() : $repository_name;
    }

    /**
     * js:location.href = window.www + params['SUBLINK'] + $to;
     */
    PROTECTED STATIC FUNCTION LINK(string $to): string
    {
        return "LINK|{$to}";
    }

    /**
     * js:window.open(window.www + params['SUBLINK'] + $to;
     */
    PROTECTED STATIC FUNCTION _BLANK(string $to): string
    {
        return "_BLANK|{$to}";
    }

    /**
     * js:alert($message); : Message d'erreur.
     */
    PROTECTED STATIC FUNCTION ERROR(string|int $message = ""): string
    {
        return "ERROR|{$message}";
    }

    PROTECTED STATIC FUNCTION ERROR_SQL(string|int $message = "Contactez le développeur.")
    {
        return SELF::ERROR(SELF::ERR_SQL_REQUEST. $message);
    }

    /**
     * ja:alert($message); : Message de succès.
     */
    PROTECTED STATIC FUNCTION SUCCESS(string|int $message = ""): string
    {
        return "SUCCESS|{$message}";
    }

    /**
     * js:alert($message); : Message d'erreur.
     */
    PROTECTED STATIC FUNCTION TEST(string|int $message = "That's work!"): string
    {
        return SELF::ERROR($message);
    }

    /**
     * js:confirm($message); : Message de confirmation
     */
    PROTECTED STATIC FUNCTION CONFIRM(string|int $message = ""): string
    {
        return "CONFIRM|{$message}";
    }

    /**
     * js:console.log($message) : Message en console.
     */
    PROTECTED STATIC FUNCTION LOG(string|int $message = ""): string
    {
        return "LOG|{$message}";
    }

    /**
     * js: return true; Aucun affichage (Utile pour le back PHP);
     */
    PROTECTED STATIC FUNCTION SILENT(string|int $message = ""): string
    {
        return "SILENT|{$message}";
    }

}