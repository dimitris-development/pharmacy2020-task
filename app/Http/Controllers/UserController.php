<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\Token;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller {

    /**
     * Handles authentication and if granted responds with a bearer token
     *
     * @param  Request  $request
     * @return Response
     */
    public function authenticate(Request $request) {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::whereEmail($email)->first();
        if($user && Hash::check($password, $user->password)) {
            return TokenController::createToken($user->id);
        }

        return response()->json([
            'message' => 'Unauthorized',
            'reason' => 'Incorrect username or password.'
        ], 401);

    }

    public function getUserInfo(Request $request){
        $bearerToken = $request->bearerToken();
        $responseFromTokenValidator = TokenController::validateToken($request);
        if ($responseFromTokenValidator === ["message" => "Token validated"]) {

            $token = Token::whereAccessToken($bearerToken)->first();
            $user = User::whereId($token->user_id)->first();
            return [
                "first_name" => $user->first_name,
                "last_name" => $user->last_name
            ];
        }
        return $responseFromTokenValidator;
    }

    /**
     * Logout a user into the platform
     *
     * @param  Request  $request
     * @return Response
     */
    public function logout(Request $request) {
        return TokenController::revokeToken($request);
    }
}
