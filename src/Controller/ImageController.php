<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ReleaseDate;
use DI\DependencyException;
use DI\NotFoundException;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * This Controller handles the image endpoint
 */
readonly class ImageController
{
    /** @var array<string, array{'in': callable, 'out': callable}> */
    private array $mimeMap;
    private string $mediaPath;

    public function __construct(string $mediaPath = __DIR__ . "/../../media")
    {
        $this->mediaPath = rtrim($mediaPath, '/');
        $this->mimeMap = [
            'image/png' => ['in' => 'imagecreatefrompng', 'out' => 'imagepng'],
            'image/jpeg' => ['in' => 'imagecreatefromjpeg', 'out' => 'imagejpeg'],
            'image/gif' => ['in' => 'imagecreatefromgif', 'out' => 'imagegif'],
        ];
    }

    /**
     * Get image for given day if it exists and is released
     * @param Request $request
     * @param Response $response
     * @param ReleaseDate $releaseDate
     * @param SessionInterface $session
     * @param string $day
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \DateMalformedStringException
     */
    public function get(Request $request, Response $response, ReleaseDate $releaseDate, SessionInterface $session, string $day): Response
    {
        $dayNumber = intval($day);
        if ($dayNumber < ReleaseDate::RELEASE_DAY_START || $dayNumber > ReleaseDate::RELEASE_DAY_END) {
            throw new HttpNotFoundException($request, 'Invalid day');
        }
        if (!$releaseDate->isReleased($dayNumber)) {
            throw new HttpUnauthorizedException($request, 'Not yet released');
        }
        $imagePath = sprintf("%s/day%02d", $this->mediaPath, $dayNumber);
        if (!is_readable($imagePath) || filesize($imagePath) === 0) {
            throw new HttpNotFoundException($request, 'File not found');
        }
        $mimeType = @mime_content_type($imagePath);
        if ($mimeType === false) {
            $mimeType = 'application/octet-stream';
        }
        $opened = $session->get('images', []);
        $opened[$dayNumber] = true;
        $session->set('images', $opened);

        $size = $request->getQueryParams()['size'] ?? '';
        $action = $request->getQueryParams()['action'] ?? '';

        $response = $response->withHeader('Content-Type', $mimeType);
        return $this->deliveryImage($response, $imagePath, $size, $mimeType, $action);
    }

    /**
     * Load the specified image in the given size into the response body
     * @param Response $response
     * @param string $imagePath
     * @param string $targetSize
     * @param string $mime
     * @param string $action
     * @return Response
     */
    private function deliveryImage(Response $response, string $imagePath, string $targetSize, string $mime, string $action): Response
    {
        if (array_key_exists($mime, $this->mimeMap)) {
            $loadPath = match ($targetSize) {
                'preview' => $this->resizeImage($imagePath, 250, $this->mimeMap[$mime]['in'], $this->mimeMap[$mime]['out']),
                'full' => $this->resizeImage($imagePath, 1250, $this->mimeMap[$mime]['in'], $this->mimeMap[$mime]['out']),
                default => $imagePath,
            };
        } else {
            $loadPath = $imagePath;
        }
        if ($action === 'download') {
            $fileExt = explode('/', $mime)[1] ?? 'bin';
            $fileName = basename($imagePath) . '.' . $fileExt;
            $response = $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "attachment; filename=\"$fileName\"");
        }
        $response->getBody()->write(file_get_contents($loadPath) ?: 'Could not read file');
        return $response->withHeader('Content-Length', (string) filesize($loadPath));
    }

    /**
     * Resize an image to a given width
     * @param string $imagePath
     * @param int $newWidth
     * @param callable $imageLoader
     * @param callable $imageSaver
     * @return string
     */
    private function resizeImage(string $imagePath, int $newWidth, callable $imageLoader, callable $imageSaver): string
    {
        // check cache
        $targetName = $imagePath . '_t_' . $newWidth;
        if (file_exists($targetName)) {
            return $targetName;
        }

        // Calculate new sizes
        $originalSize = getimagesize($imagePath);
        if ($originalSize === false) {
            return $imagePath;
        }
        list($width, $height) = $originalSize;
        if ($width <= $newWidth) {
            return $imagePath;
        }
        $factor = $width / $height;
        // Make sure we have positive values
        $newHeight = max(1, intval($newWidth / $factor));
        $newWidth = max(1, $newWidth);

        // Create image
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        $source = $imageLoader($imagePath);
        imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $imageSaver($thumb, $targetName);
        return $targetName;
    }
}
