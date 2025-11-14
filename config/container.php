<?php

declare(strict_types=1);

use App\Extensions\TranslationExtension;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\LanguageMiddleware;
use DI\Container;
use Middlewares\TrailingSlash;
use Middlewares\Whoops;
use Odan\Session\Middleware\SessionStartMiddleware;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;

return [
    // Odan Session configuration
    SessionManagerInterface::class => function (Container $container) {
        return $container->get(SessionInterface::class);
    },
    SessionInterface::class => function (Container $container) {
        return $container->get(PhpSession::class);
    },
    PhpSession::class => function () {
        $session = new PhpSession([
            'name' => 'advent',
            'lifetime' => 2 * 30 * 24 * 60 * 60, // 2 months
            'save_path' => null,
            'domain' => null,
            'secure' => false,
            'httponly' => true,
            'cache_limiter' => 'nocache',
        ]);
        return $session;
    },
    // Twig configuration
    Twig::class => function (Container $container) {
        $debug = $container->get('debug');
        $twig = Twig::create(__DIR__ . '/../views', [
            'debug' => $debug,
            'cache' => $debug ? false : __DIR__ . '/../tmp/twig_cache'
        ]);
        if ($debug) {
            $twig->addExtension($container->get(DebugExtension::class));
        }
        $session = $container->get(SessionInterface::class);
        $twigEnv = $twig->getEnvironment();
        $translator = new TwigFunction('__t', $container->get(TranslationExtension::class));
        $twigEnv->addFunction($translator);
        $twigEnv->addGlobal('session', $session);
        $twigEnv->addGlobal('isDebug', $debug);
        $twigEnv->addGlobal('languages', $container->get('languages'));
        $twigEnv->addGlobal('title', $container->get('title'));
        return $twig;
    },
    // Autowired middlewares
    Whoops::class => DI\autowire(Whoops::class),
    TrailingSlash::class => DI\autowire(TrailingSlash::class),
    TwigMiddleware::class => DI\autowire(TwigMiddleware::class),
    ResponseFactory::class => DI\autowire(ResponseFactory::class),
    AuthenticationMiddleware::class => DI\autowire(AuthenticationMiddleware::class),
    TranslationExtension::class => DI\autowire(TranslationExtension::class),
    LanguageMiddleware::class => DI\autowire(LanguageMiddleware::class),
    SessionStartMiddleware::class => DI\autowire(SessionStartMiddleware::class),
];
