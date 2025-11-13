<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Trait\RedirectTrait;
use Odan\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * This Middleware checks if the user is logged in
 */
class AuthenticationMiddleware
{
    use RedirectTrait;

    private ContainerInterface $container;
    private SessionInterface $session;

    /**
     * @param ContainerInterface $container Required to get the password
     * @param SessionInterface $session Required to check if the user is logged in
     */
    public function __construct(ContainerInterface $container, SessionInterface $session)
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
        if (!$this->container->has('password') || empty($password = $this->container->get('password'))) {
            return $handler->handle($request);
        }
        // Redirect to login if not logged in
        if (!$this->session->get('loggedIn', false) || !($sessionHash = $this->session->get('passwordHash', ''))) {
            return $this->redirectTo(new \Slim\Psr7\Response(), '/login');
        }
        // Check if password changed
        if (!password_verify($password, $sessionHash)) {
            return $this->redirectTo(new \Slim\Psr7\Response(), '/login');
        }
        // Allow access if logged in
        return $handler->handle($request);
    }
}
