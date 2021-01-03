<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'api', 'middleware' => 'cors'], function () use ($router) {
    $router->post('/login', 'UserController@authenticate');
    $router->get('/refresh_token', 'TokenController@refreshToken');
    $router->group(['middleware' => 'token_validator'], function () use ($router) {
        $router->post('/logout', 'UserController@logout');
        $router->get('/get_user_info', 'UserController@getUserInfo');
    });
});
