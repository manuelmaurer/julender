<?php

declare(strict_types=1);

namespace App\Middleware;

use DI\Container;
use Odan\Session\PhpSession;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * This Middleware sets the language for the user
 */
class LanguageMiddleware
{
    private Container $container;
    private PhpSession $session;

    /**
     * @param Container $container Required to get the active languages
     * @param PhpSession $session Required to get and set the selected language in session
     */
    public function __construct(Container $container, PhpSession $session)
    {
        $this->container = $container;
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $languages = $this->container->get('languages');
        if (empty($languages)) {
            // Configuration error
            throw new \Exception('Invalid configuration');
        }
        // Prefer the saved language in session
        $sessionLanguage = $this->session->get('language', null);
        if (!empty($sessionLanguage) && in_array($sessionLanguage, $languages)) {
            return $handler->handle($request);
        }
        // Try to get the browser language
        $browserLanguage = explode('-', $request->getHeaderLine('Accept-Language'))[0];
        if (in_array($browserLanguage, $languages)) {
            $this->session->set('language', $browserLanguage);
            return $handler->handle($request);
        }
        // Use the first configured language as fallback
        $this->session->set('language', $languages[0]);
        return $handler->handle($request);
    }
}
