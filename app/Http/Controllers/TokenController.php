<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Token;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class TokenController
 * @package App\Http\Controllers
 */
class TokenController extends Controller {


    private const ACCESS_TOKEN_LIFESPAN = Token::ACCESS_TOKEN_LIFESPAN__TESTING;
    private const REFRESH_TOKEN_LIFESPAN = Token::REFRESH_TOKEN_LIFESPAN__TESTING;

    /**
     * @param $user_id
     * @return JsonResponse
     */
    public static function createToken(int $user_id) : JsonResponse
    {

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
            'expires_in' => self::ACCESS_TOKEN_LIFESPAN,
            'refresh_token' => $refresh_token,
        ]);
    }

    /**
     * @param $bearer_token
     * @return array
     */
    #[ArrayShape([
        'error_id' => integer,
        'error' => 'string',
        'error_description' => 'string',
        'message' => 'string'
    ])] public static function validateToken ($bearer_token) : array
    {
        if (Token::whereAccessToken($bearer_token)->exists()) {
            $token = Token::whereAccessToken($bearer_token)->first();
            $token_creat_date = $token->access_refresh_pair_creation_date;
            $date_now = Carbon::now();
            if ($token_creat_date->diffInSeconds($date_now) >= self::REFRESH_TOKEN_LIFESPAN) {
                $token->is_expired = 1;
                $token->save();
                $response = [
                    'error_id' => 2,
                    'error' => 'invalid_token',
                    'error_description' => 'The refresh token is expired'
                ];
            }
            elseif ($token_creat_date->diffInSeconds($date_now) >= self::ACCESS_TOKEN_LIFESPAN) {
                $response = [
                    'error_id' => 1,
                    'error' => 'invalid_token',
                    'error_description' => 'The access token is expired'
                ];
            } else {
                $response = [
                    'message' => 'Token validated'
                ];
            }
        } else {
            $response = [
                'error_id' => 3,
                'error' => 'invalid_grant',
                'error_description' => 'This access token does not exist'
            ];
        }
        return $response;
    }

    public static function revokeToken (Request $request) : JsonResponse {
        $bearer_token = $request->bearerToken();
        $token = Token::whereAccessToken($bearer_token)->first();
        $token->is_expired = 1;
        $token->save();
        return response()->json([
            'message' => 'Token revoked'
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken (Request $request) : JsonResponse {
        $refresh_token = $request->input('refresh_token');
        if (Token::whereRefreshToken($refresh_token)->exists()){
            $token = Token::whereRefreshToken($refresh_token)->first();
            $token_creat_date = $token->access_refresh_pair_creation_date;
            $date_now = Carbon::now();
            if ($token_creat_date->diffInSeconds($date_now) >= self::REFRESH_TOKEN_LIFESPAN){
                $response = response()->json([
                    'error_id' => 2,
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
                'error_id' => 3,
                'error' => 'invalid_grant',
                'error_description' => 'This access token does not exist'
            ], 400);
        }
        return $response;
    }
}
