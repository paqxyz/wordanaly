<?php

use Illuminate\Routing\Router;

Admin::registerHelpersRoutes();

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
], function (Router $router) {

    $router->get('/', 'WordController@index');
    $router->resource('sites', SiteController::class);
    $router->resource('words', WordController::class);
    $router->post('/', 'WordController@index');
    $router->get('logs', 'LogController@index');

});
