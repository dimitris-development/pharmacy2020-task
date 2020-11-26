<?php

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

$router->get('/', function () {
    return view('home');
});

$router->get('/login', function () {
    return view('login');
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('login', 'UserController@authenticate');
    $router->post('logout', 'UserController@logout');
    $router->get('get_user_info', 'UserController@getUserInfo');
    $router->post('refresh_token', 'TokenController@refreshToken');
    $router->post('is_token_valid', 'TokenController@validateToken');
});