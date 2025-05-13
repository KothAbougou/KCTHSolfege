<?php

namespace KCTH\Solfege\helper;

use KCTH\Solfege\KCTHSolfege;
use KCTH\Solfege\KCTHSolfegeHttpRequest as HttpRequest;

/**
 * Système d'authentification par tokens.
 */
class KCTHSolfegeTokenAuthHelper extends KCTHSolfege 
{
    PRIVATE CONST AUTH_TOKEN_HEADER_FIELD = 'HTTP_AUTHORIZATION_TOKEN';

    private static function tokenExist($token, bool $validTokenOnly = false): bool
    {
        $userRepository = SELF::GET_REPOSITORY("User");
        $users = $userRepository->getAllActiveUsers();
        
        foreach($users as $user) {
            if($user->util_token == $token) {
                if($validTokenOnly)
                    return $user->util_token_expiration_date > self::dtNow();
                else
                    return true;
            }
        }
        return false;
    }
    

    /**
     * Génère le token.
     */
    private static function generateToken(int $length = 16): string
    {
        $token = null;
        do {
            $token = bin2hex(random_bytes($length));
        } while(self::tokenExist($token, false));

        return $token;
    }
    

    /**
     * Initialise 
     */
    public static function initiateToken(): bool 
    {
        $token = self::generateToken();

        $userId = SELF::AUTH()->util_id;

        $userRepository = SELF::GET_REPOSITORY('User');

        return $userRepository->updateToken($userId, $token);
    } 

    /**
     * Vérifie le tocken
     */
    public static function verifyToken(HttpRequest $request): bool
    {
        $token = $request->getResponse(SELF::AUTH_TOKEN_HEADER_FIELD);
        
        return is_null($token) ?
            false :
            self::tokenExist($token, true);
    }
}