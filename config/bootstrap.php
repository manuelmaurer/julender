<?php

use App\Middleware\DebugMiddleware as dbg;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;
use Odan\Session\Middleware\SessionStartMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$containerDefs = require __DIR__ . '/container.php';
$builder = new ContainerBuilder();
if (dbg::isDebug()) {
$builder->enableCompilation(__DIR__ . '/../tmp');
}
$builder->addDefinitions($containerDefs);
$container = $builder->build();

$app = Bridge::create($container);

$app->add($container->get(SessionStartMiddleware::class));
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add($container->get(TrailingSlash::class));

(require __DIR__ . '/../config/routes.php')($app);

if (dbg::isDebug()) {
$app->add($container->get(Whoops::class));
} else {
$logErrors = boolval($_ENV['LOG_ERRORS'] ?? false);
$app->addErrorMiddleware(false, true, $logErrors);
}

return $app;
