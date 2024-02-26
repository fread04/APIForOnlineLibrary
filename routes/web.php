<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});



$router->group([], function ($app) {
    $app->get('books', 'BookController@showAll');
    $app->get('books/{id:[0-9]+}', 'BookController@showById');
    $app->post('signup/', 'UserController@signup');
    $app->get('login/', 'UserController@login');
});

$router->group(['middleware' => \App\Http\Middleware\Authenticate::class], function ($app) {
    $app->post('books/fav/{id:[0-9]+}', 'UserController@addFavorite');
    $app->delete('books/fav/{id:[0-9]+}', 'UserController@removeFavorite');
    $app->get('books/fav', 'UserController@getFavorite');
});

$router->group(['middleware' => [\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\AdminOnly::class]], function ($app) {
    $app->post('books/add', 'BookController@add');
    $app->delete('books/remove/{id:[0-9]+}', 'BookController@remove');
    $app->get('books/csv', 'BookController@csvExport');
});

