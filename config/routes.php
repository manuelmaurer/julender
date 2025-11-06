<?php

use App\Controller\AuthenticationController;
use App\Controller\HomeController;
use App\Controller\ImageController;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\LanguageMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Endpoints with language detection
    $app->group('/', function (RouteCollectorProxy $group) {
        $group->get('', [HomeController::class, 'home'])
            ->add(AuthenticationMiddleware::class)
            ->setName('get.home');
        $group->get('login', [AuthenticationController::class, 'getLogin'])->setName('get.login');
        $group->post('login', [AuthenticationController::class, 'postLogin'])->setName('post.login');
    })->add(LanguageMiddleware::class);

    // Language switcher
    $app->get('/language/{language}', [HomeController::class, 'language'])
        ->setName('get.language');

    // Images
    $app->get('/image/{day}', [ImageController::class, 'get'])->setName('get.image');
};
