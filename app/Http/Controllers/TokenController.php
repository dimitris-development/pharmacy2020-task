<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Token;
use Illuminate\Http\Request;

/**
 * Class TokenController
 * @package App\Http\Controllers
 */
class TokenController extends Controller {

    /**
     * @param $user_id
     * @return Response
     */
    public static function createToken(int $user_id) : Response {

        if (Token::whereUserIdAndIsExpired($user_id, 0)->exists()){
            $token = Token::whereUserId($user_id)->first();
            $token->is_expired = 1;
            $token->save();
        }

        do {
            $access_token = bin2hex(random_bytes(64));
            $acc_token_exists = Token::whereAccessToken($access_token)->exists();
        } while ($acc_token_exists);
        $refresh_token = bin2hex(random_bytes(128));

        $token = new Token();
        $token->access_token = $access_token;
        $token->refresh_token = $refresh_token;
        $token->user_id = $user_id;
        $token->save();

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $access_token,
            'expires_in' => Token::ACCESS_TOKEN_LIFESPAN,
            'refresh_token' => $refresh_token,
        ], 200);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public static function validateToken (Request $request) : Response {
        $bearer_token = $request->bearerToken();
        if (Token::whereAccessToken($bearer_token)->exists()) {
            $token = Token::whereAccessToken($bearer_token)->first();
            $token_creat_date = $token->access_refresh_pair_creation_date;
            $date_now = date('U');

            if ($date_now - strtotime($token_creat_date) >= Token::REFRESH_TOKEN_LIFESPAN) {
                $token->is_expired = 1;
                $token->save();
                $response = response()->json([
                    'error' => 'invalid_token',
                    'error_description' => 'The refresh token is expired'
                ], 401);
            }
            elseif ($date_now - strtotime($token_creat_date) >= Token::ACCESS_TOKEN_LIFESPAN) {
                $response = response()->json([
                    'error' => 'invalid_token',
                    'error_description'=> 'The access token is expired'
                ], 401)
                    ->header('WWW-Authenticate',
                        'Bearer error=\'invalid_token\' error_description=\'The access token expired\'');
            } else {
                $response = response()->json([
                    'message' => 'Token validated'
                ], 200);
            }
        } else {
            $response = response()->json([
                'error' => 'invalid_grant'
            ], 400);
        }
        return $response;
    }

    public static function revokeToken (Request $request) : Response {
        $bearer_token = $request->bearerToken();
        $validator_resp = self::validateToken($request);
        if ($validator_resp === ['message' => 'Token validated']){
            $token = Token::whereAccessToken($bearer_token)->first();
            $token->is_expired = 1;
            $token->save();
            $response = response()->json([
                'message' => 'Token revoked'
            ]);
        } else {
            $response = $validator_resp;
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function refreshToken (Request $request) : Response {
        $refresh_token = $request->input('refresh_token');
        if (Token::whereRefreshToken($refresh_token)->exists()){
            $token = Token::whereRefreshToken($refresh_token)->first();
            $token_creat_date = $token->access_refresh_pair_creation_date;
            $date_now = date('U');
            if ($date_now - strtotime($token_creat_date) >= Token::REFRESH_TOKEN_LIFESPAN){
                $response = response()->json([
                    'error' => 'invalid_token',
                    'error_description' => 'The refresh token is expired'
                ], 401);
            } else {
                $user_id = $token->user_id;
                $token->delete();
                $response = self::createToken($user_id);
            }

        } else {
            $response = response()->json([
                'error' => 'invalid_grant'
            ], 400);
        }
        return $response;
    }
}
