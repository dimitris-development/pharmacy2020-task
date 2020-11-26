<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\Token;


use Illuminate\Http\Request;

class TokenController extends Controller {

    public static function createToken($user_id) {
        
        if (Token::whereUserIdAndIsExpired($user_id, 0)->exists()){
            $token = Token::whereUserId($user_id)->first();
            $token->is_expired = 1;
            $token->save();
        }

        do {
            $access_token = bin2hex(openssl_random_pseudo_bytes(64));
            $access_tokenAlreadyExists = Token::whereAccessToken($access_token)->exists();
        } while ($access_tokenAlreadyExists);
        $refresh_token = bin2hex(openssl_random_pseudo_bytes(128));

        $token = new Token;
        $token->access_token = $access_token;
        $token->refresh_token = $refresh_token;
        $token->user_id = $user_id;
        $token->save();

        return [
            'token_type' => 'Bearer',
            'access_token' => $access_token,
            'expires_in' => Token::ACCESS_TOKEN_LIFESPAN,
            'refresh_token' => $refresh_token,
        ];
    }

    public static function validateToken(Request $request){
        $bearerToken = $request->bearerToken();
        if (Token::where("access_token", $bearerToken)->exists()) {
            $token = Token::whereAccessToken($bearerToken)->first();
            $tokenCreationDate = $token->access_refresh_pair_creation_date;
            $now = date("U");
            if ($now - strtotime($tokenCreationDate) >= Token::REFRESH_TOKEN_LIFESPAN){
                $token->is_expired = 1;
                $token->save();
                return response()->json([
                    "error" => "invalid_token",
                    "error_description" => "The refresh token is expired"
                ], 401);
            } else if ($now - strtotime($tokenCreationDate) >= Token::ACCESS_TOKEN_LIFESPAN) {
                return response()->json([
                    "error" => "invalid_token",
                    "error_description"=> "The access token is expired"
                ], 401)
                    ->header('WWW-Authenticate','Bearer error="invalid_token" error_description="The access token expired" ');
            } else {
                return [
                    "message" => "Token validated"
                ];
            }
        } else {
            return response()->json([
                "error" => "invalid_grant"
            ], 400);
        }
    }

    public static function revokeToken(Request $request){
        $bearerToken = $request->bearerToken();
        $responseFromTokenValidator = TokenController::validateToken($request);
        if ($responseFromTokenValidator === ["message" => "Token validated"]){
            $token = Token::whereAccessToken($bearerToken)->first();
            $token->is_expired = 1;
            $token->save();
            return [
                "message" => "Token revoked"
            ];
        } 
        return $responseFromTokenValidator;
    }

    public function refreshToken(Request $request) {
        $refresh_token = $request->input('refresh_token');
        if (Token::whereRefreshToken($refresh_token)->exists()){
            $token = Token::whereRefreshToken($refresh_token)->first();
            $tokenCreationDate = $token->access_refresh_pair_creation_date;
            $now = date("U");
            if ($now - strtotime($tokenCreationDate) >= Token::REFRESH_TOKEN_LIFESPAN){
                return response()->json([
                    "error" => "invalid_token",
                    "error_description" => "The refresh token is expired"
                ], 401);
            }
            $user_id = $token->user_id;
            $token->delete();
            return $this->createToken($user_id);
        } else {
            return response()->json([
                "error" => "invalid_grant"
            ], 400);
        }
    }
}