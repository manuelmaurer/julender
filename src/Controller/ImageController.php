<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ReleaseDate;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * This Controller handles the image endpoint
 */
class ImageController
{
    private readonly string $mediaPath;

    public function __construct(string $mediaPath = __DIR__ . "/../../media")
    {
        $this->mediaPath = rtrim($mediaPath, '/');
    }

    /**
     * Get image for given day if it exists and is released
     * @param Request $request
     * @param Response $response
     * @param ReleaseDate $releaseDate
     * @param string $day
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    public function get(Request $request, Response $response, ReleaseDate $releaseDate, string $day): Response
    {
        $dayNumber = intval($day);
        if ($dayNumber < 1 || $dayNumber > 24) {
            throw new HttpNotFoundException($request, 'Invalid day');
        }
        if (!$releaseDate->isReleased($dayNumber)) {
            throw new HttpUnauthorizedException($request, 'Not yet released');
        }
        $imagePath = sprintf("%s/day%02d", $this->mediaPath, $dayNumber);
        $fileSize = @filesize($imagePath);
        if (!@file_exists($imagePath) || $fileSize === false || $fileSize === 0) {
            throw new HttpNotFoundException($request, 'File not found');
        }
        $mimeType = @mime_content_type($imagePath);
        if ($mimeType === false) {
            $mimeType = 'application/octet-stream';
        }
        $response = $response
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', strval($fileSize));
        $response->getBody()->write(file_get_contents($imagePath) ?: 'Could not read file');
        return $response;
    }
}
