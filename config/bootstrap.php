<?php

declare(strict_types=1);

use App\Middleware\LanguageMiddleware;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;
use Odan\Session\Middleware\SessionStartMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$builder = new ContainerBuilder();
$defs = [
    'container.php', // container definitions
    'config.php', // default configuration
    'config.local.php' // custom configuration
];
// Iterate all files and add them to the container
foreach ($defs as $def) {
    if (!is_file(__DIR__ . '/' . $def)) {
        continue;
    }
    $containerDef = require __DIR__ . '/' . $def;
    $builder->addDefinitions($containerDef);
}
// We can't use debug from config for this, because the container is not yet built
if (getenv('CONTAINER_CACHE') == '1') {
    $builder->enableCompilation(__DIR__ . '/../tmp');
}
$container = $builder->build();

// Initialize slim application
$app = Bridge::create($container);

$app->add($container->get(SessionStartMiddleware::class));
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add($container->get(TrailingSlash::class));
$app->add($container->get(LanguageMiddleware::class));

// Register routes
(require __DIR__ . '/routes.php')($app);

// Error handling
if ($container->get('debug')) {
    $app->add($container->get(Whoops::class));
} else {
    $app->addErrorMiddleware(false, true, false);
}

return $app;
