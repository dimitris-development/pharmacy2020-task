<?php
declare(strict_types=1);


namespace App\Http\Middleware;

use App\Http\Controllers\TokenController;
use Closure;
use Illuminate\Http\JsonResponse;


/**
 * Class Authenticate
 * @package App\Http\Middleware
 */
class TokenValidator
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param callable $next
     * @param null $guard
     * @return JsonResponse
     * @noinspection PhpDocSignatureInspection
     */
    public function handle($request, Closure $next) : JsonResponse
    {
        $bearer_token = $request->bearerToken();
        $validator_resp = TokenController::validateToken($bearer_token);
        if (['message' => 'Token validated'] === $validator_resp){
            return $next($request);
        }
        return response()->json($validator_resp, 401);
    }

}
