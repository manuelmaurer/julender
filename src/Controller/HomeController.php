<?php

namespace App\Controller;

use App\Trait\ReleaseDateTrait;
use DI\Container;
use Odan\Session\PhpSession;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * This Controller handles the home page and language switcher
 */
class HomeController
{
    use ReleaseDateTrait;

    /**
     * Calculate release dates for all 24 days and render home page
     * @param Response $response
     * @param Container $container
     * @param Twig $twig
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function home(Response $response, Container $container, Twig $twig): Response
    {
        $data = array_reduce(range(1, 24), function ($carry, $item) use ($container) {
            $carry[$item] = [
                'ts' => $this->getReleaseDate($container, $item),
                'isReleased' => $this->isReleased($container, $item),
            ];
            return $carry;
        }, []);
        return $twig->render($response, 'home.twig', ['data' => $data]);
    }

    /**
     * Switch language and redirect to home page
     * @param Response $response
     * @param Container $container
     * @param PhpSession $session
     * @param string $language
     * @return Response
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function language(Response $response, Container $container, PhpSession $session, string $language): Response
    {
        $languages = $container->get('languages');
        if (empty($languages)) {
            throw new \Exception('Invalid configuration');
        }
        if (!in_array($language, $languages)) {
            throw new \Exception('Invalid language');
        }
        $session->set('language', $language);
        return $response->withStatus(302)->withHeader('Location', '/');
    }
}
