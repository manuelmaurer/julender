<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ReleaseDate;
use DI\DependencyException;
use DI\NotFoundException;
use Imagick;
use ImagickPixel;
use Odan\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * This Controller handles the image endpoint
 */
readonly class ImageController
{
    private string $mediaPath;
    private string $cachePath;
    private bool $useCache;

    /**
     * @param ContainerInterface $container
     * @param string $mediaPath
     * @param string $cachePath
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, string $mediaPath = __DIR__ . "/../../media", string $cachePath = __DIR__ . "/../../tmp/image_cache")
    {
        $this->mediaPath = rtrim($mediaPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->useCache = $container->get('imageCache');
    }

    /**
     * @param int $day
     * @return string
     */
    private function getImagePath(int $day): string
    {
        return sprintf("%s/%02d", $this->mediaPath, $day);
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
     * @throws \DateMalformedStringException|\ImagickException
     */
    public function getImage(Request $request, Response $response, ReleaseDate $releaseDate, SessionInterface $session, string $day): Response
    {
        $dayNumber = intval($day);
        if ($dayNumber < ReleaseDate::RELEASE_DAY_START || $dayNumber > ReleaseDate::RELEASE_DAY_END) {
            throw new HttpNotFoundException($request, 'Invalid day');
        }
        if (!$releaseDate->isReleased($dayNumber)) {
            throw new HttpUnauthorizedException($request, 'Not yet released');
        }
        $imagePath = $this->getImagePath($dayNumber);
        if (!is_readable($imagePath) || filesize($imagePath) === 0) {
            throw new HttpNotFoundException($request, 'File not found');
        }

        $opened = $session->get('images', []);
        $opened[$dayNumber] = true;
        $session->set('images', $opened);

        $size = $request->getQueryParams()['size'] ?? '';
        $action = $request->getQueryParams()['action'] ?? '';

        return $this->deliverImage($response, $dayNumber, $size, $action);
    }

    /**
     * Load the specified image in the given size into the response body
     * @param Response $response
     * @param int $day
     * @param string $targetSize
     * @param string $action
     * @return Response
     * @throws \ImagickException
     */
    private function deliverImage(Response $response, int $day, string $targetSize, string $action): Response
    {
        if (!is_dir($this->cachePath) || !is_writable($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
        $loadPath = match ($targetSize) {
            'preview' => $this->resizeImage($day, 250, 188),
            'full' => $this->resizeImage($day, 1000, 800),
            default => $this->getImagePath($day),
        };
        if ($action === 'generate') {
            return $response->withStatus(204);
        }
        $mime = mime_content_type($loadPath) ?: 'application/octet-stream';
        if ($action === 'download') {
            $fileExt = $mime == 'application/octet-stream' ? 'bin' : (explode('/', $mime)[1] ?? 'bin');
            $fileName = sprintf("day_%02d.%s", $day, $fileExt);
            $response = $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "attachment; filename=\"$fileName\"");
        }
        $response->getBody()->write(file_get_contents($loadPath) ?: 'Could not read file');
        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) filesize($loadPath));
    }

    /**
     * Resize an image to a given width
     * @param int $day
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string
     * @throws \ImagickException
     */
    private function resizeImage(int $day, int $maxWidth, int $maxHeight): string
    {
        // check cache
        $cacheFile = sprintf("%s/%02d_t_%dx%d.jpg", $this->cachePath, $day, $maxWidth, $maxHeight);
        if (is_readable($cacheFile) && $this->useCache) {
            return $cacheFile;
        }
        $imagePath = $this->getImagePath($day);

        $thumb = new Imagick($imagePath);
        // rotate image first if necessary
        $orientation = $thumb->getImageProperties('exif:Orientation')['exif:Orientation'] ?? '1';
        $rotation = match ($orientation) {
            '3' => 180,
            '6' => 90,
            '8' => 270,
            default => 0
        };
        $thumb->rotateImage(new ImagickPixel('none'), $rotation);
        // remove exif data
        $thumb->stripImage();
        // resize to width first
        $thumb->resizeImage($maxWidth, 0, imagick::FILTER_CATROM, 0.5);
        if ($thumb->getImageHeight() > $maxHeight) {
            $thumb->resizeImage(0, $maxHeight, imagick::FILTER_CATROM, 0.5);
        }
        file_put_contents($cacheFile, $thumb->getImageBlob());
        return $cacheFile;
    }
}
