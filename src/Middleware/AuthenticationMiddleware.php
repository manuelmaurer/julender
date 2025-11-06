<?php

namespace App\Middleware;

use App\Trait\RedirectTrait;
use DI\Container;
use Odan\Session\PhpSession;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * This Middleware checks if the user is logged in
 */
class AuthenticationMiddleware
{
    use RedirectTrait;

    private Container $container;
    private PhpSession $session;

    /**
     * @param Container $container Required to get the password
     * @param PhpSession $session Required to check if the user is logged in
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
        // If no password is configured, allow access
        if ($this->container->get('password') === null) {
            return $handler->handle($request);
        }
        // Redirect to login if not logged in
        if (!$this->session->has('loggedIn')) {
            return $this->redirectTo(new \Slim\Psr7\Response(), '/login');
        }
        // Allow access if logged in
        return $handler->handle($request);
    }
}
