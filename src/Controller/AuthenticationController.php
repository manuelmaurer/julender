<?php

declare(strict_types=1);

namespace App\Controller;

use App\Trait\RedirectTrait;
use DI\Container;
use Odan\Session\PhpSession;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * This Controller handles the login functionality
 */
class AuthenticationController
{
    use RedirectTrait;

    /**
     * Render login page
     * @param Response $response
     * @param Twig $twig
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getLogin(Response $response, Twig $twig): Response
    {
        return $twig->render($response, 'login.twig');
    }

    /**
     * Validate login request
     * - Redirect to start page on successful login
     * - Save error message in flash and redirect to login page on error
     * @param Request $request
     * @param Response $response
     * @param PhpSession $session
     * @param Container $container
     * @return Response
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function postLogin(Request $request, Response $response, PhpSession $session, Container $container): Response
    {
        $flash = $session->getFlash();
        $targetPassword = $container->get('password');
        if (empty($targetPassword)) {
            $flash->add('login-error', 'Server error');
            return $this->redirectTo($response, '/login');
        }
        $body = $request->getParsedBody();
        if (empty($body)) {
            $password = '';
        } elseif (is_array($body)) {
            $password = $body['password'] ?? '';
        } else {
            $password = $body->{'password'} ?? '';
        }
        if ($password !== $targetPassword) {
            $flash->add('login-error', 'Invalid password');
            return $this->redirectTo($response, '/login');
        }
        $session->set('loggedIn', true);
        return $this->redirectTo($response, '/');
    }
}
