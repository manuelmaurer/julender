<?php

use App\Controller\HomeController;
use App\Controller\ImageController;
use Slim\App;

return function (App $app) {
    $app->get('/', [HomeController::class, 'home'])->setName('get.root');
    $app->get('/image/{day}', [ImageController::class, 'get'])->setName('get.image');
};
