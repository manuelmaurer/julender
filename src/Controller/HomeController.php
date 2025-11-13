<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ReleaseDate;
use App\Trait\RedirectTrait;
use DI\DependencyException;
use DI\NotFoundException;
use Odan\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * This Controller handles the home page and language switcher
 */
readonly class HomeController
{
    use RedirectTrait;

    /**
     * Calculate release dates for all 24 days and render the home page
     * @param Response $response
     * @param Twig $twig
     * @param ReleaseDate $releaseDate
     * @param SessionInterface $session
     * @return Response
     * @throws DependencyException
     * @throws LoaderError
     * @throws NotFoundException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \DateMalformedStringException
     */
    public function home(Response $response, Twig $twig, ReleaseDate $releaseDate, SessionInterface $session): Response
    {
        $payload = [
            'data' => $releaseDate->getAllReleaseDates(),
            'opened' => $session->get('images', []),
        ];
        return $twig->render($response, 'home.twig', $payload);
    }

    /**
     * Switch language and redirect to the home page
     * @param Request $request
     * @param Response $response
     * @param ContainerInterface $container
     * @param SessionInterface $session
     * @param string $language
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function language(Request $request, Response $response, ContainerInterface $container, SessionInterface $session, string $language): Response
    {
        if (!$container->has('languages') || empty($languages = $container->get('languages')) || !is_array($languages)) {
            throw new HttpInternalServerErrorException($request, 'Invalid configuration');
        }
        if (!in_array($language, $languages)) {
            throw new HttpNotFoundException($request, 'Invalid language');
        }
        $session->set('language', $language);
        return $this->redirectTo($response, '/');
    }
}
