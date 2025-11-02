<?php

use App\Middleware\DebugMiddleware as dbg;
use DI\Container;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\DebugExtension;

return [
    'timezone' => new DateTimeZone('Europe/Berlin'),
    SessionManagerInterface::class => function (Container $container) {
        return $container->get(SessionInterface::class);
    },
    SessionInterface::class => function (Container $container) {
        return $container->get(PhpSession::class);
    },
    PhpSession::class => function () {
        $session = new PhpSession([
            'name' => 'csrf',
            'lifetime' => 7200,
            'save_path' => null,
            'domain' => null,
            'secure' => false,
            'httponly' => true,
            'cache_limiter' => 'nocache',
        ]);
        $session->start();
        return $session;
    },
    Twig::class => function (Container $container) {
        $twig = Twig::create(__DIR__ . '/../views', [
            'debug' => dbg::isDebug(),
            'cache' => dbg::isDebug() ? false : __DIR__ . '/../tmp/twig_cache'
        ]);
        $twig->addExtension($container->get(DebugExtension::class));
        $flash = $container->get(SessionInterface::class)->getFlash();
        $twig->getEnvironment()->addGlobal('flash', $flash);
        $twig->getEnvironment()->addGlobal('isDebug', dbg::isDebug());
        return $twig;
    },
    Whoops::class => DI\autowire(Whoops::class),
    TrailingSlash::class => DI\autowire(TrailingSlash::class),
    TwigMiddleware::class => DI\autowire(TwigMiddleware::class),
    ResponseFactory::class => DI\autowire(ResponseFactory::class),
];
