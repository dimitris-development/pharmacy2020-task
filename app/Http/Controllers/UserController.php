<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;

use Illuminate\Http\JsonResponse;
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
     * @return JsonResponse
     */
    public function authenticate(Request $request) : JsonResponse {
        $email = $request->input('email');
        $password = $request->input('password');

        $auth_user = User::whereEmail($email)->first();
        if($auth_user && Hash::check($password, $auth_user->password)) {
            return TokenController::createToken($auth_user->id);
        }

        return response()->json([
            'message' => 'Unauthorized',
            'reason' => 'Incorrect username or password.'
        ], 401);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserInfo(Request $request) : JsonResponse {
        $bearer_token = $request->bearerToken();
        $validator_resp = TokenController::validateToken($request);
        if ($validator_resp->content() === '{"message":"Token validated"}') {
            $token = Token::whereAccessToken($bearer_token)->first();
            $valid_user = User::whereId($token->user_id)->first();
            return response()->json([
                'first_name' => $valid_user->first_name,
                'last_name' => $valid_user->last_name
            ]);
        }
        return $validator_resp;
    }

    /**
     * Logout a user
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request) : JsonResponse {
        return TokenController::revokeToken($request);
    }
}
