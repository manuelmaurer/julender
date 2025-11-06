<?php

namespace App\Controller;

use App\Trait\ReleaseDateTrait;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * This Controller handles the image endpoint
 */
class ImageController
{
    use ReleaseDateTrait;

    /**
     * Get image for given day if it exists and is released
     * @param Request $request
     * @param Response $response
     * @param Container $container
     * @param string $day
     * @return Response
     */
    public function get(Request $request, Response $response, Container $container, string $day): Response
    {
        $dayNumber = intval($day);
        if ($dayNumber < 1 || $dayNumber > 24) {
            throw new HttpNotFoundException($request, 'Invalid day');
        }
        if (!$this->isReleased($container, $dayNumber)) {
            throw new HttpUnauthorizedException($request, 'Not yet released');
        }
        $imagePath = sprintf("%s/../../media/day%02d.png", __DIR__, $dayNumber);
        $fileSize = filesize($imagePath);
        if (!file_exists($imagePath) || $fileSize === false || $fileSize === 0) {
            throw new HttpNotFoundException($request, 'File not found');
        }
        $response = $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', strval($fileSize));
        $response->getBody()->write(file_get_contents($imagePath) ?: 'Could not read file');
        return $response;
    }
}
