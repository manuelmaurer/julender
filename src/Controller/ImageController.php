<?php

namespace App\Controller;

use App\Trait\ReleaseDateTrait;
use DateTimeImmutable;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ImageController
{
    use ReleaseDateTrait;

    /**
     * @param Request $request
     * @param Response $response
     * @param string $day
     * @return Response
     * @throws \DateMalformedStringException
     */
    public function get(Request $request, Response $response, Container $container, string $day): Response
    {
        $dayNumber = intval($day);
        if ($dayNumber < 1 || $dayNumber > 24) {
            throw new HttpNotFoundException($request, 'Invalid day');
        }
        $tz = $container->get('timezone');
        $releaseDate = $this->getReleaseDate($dayNumber, $tz);
        $now = new DateTimeImmutable('now', $tz);
        if ($releaseDate > $now) {
            throw new HttpUnauthorizedException($request, 'Not yet released');
        }
        $imagePath = sprintf("%s/../../media/day%02d.png", __DIR__, $dayNumber);
        if (!file_exists($imagePath)) {
            throw new HttpNotFoundException($request, 'File not found');
        }
        $response = $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', filesize($imagePath));
        $response->getBody()->write(file_get_contents($imagePath));
        return $response;
    }

}
