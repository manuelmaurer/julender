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
    private const array THUMB_SIZES = [
        'preview' => ['w' => 250, 'h' => 188],
        'full' => ['w' => 1000, 'h' => 800],
    ];
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
        if (!is_dir($this->cachePath) || !is_writable($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
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
     * @param int $day
     * @param string $size
     * @return string
     */
    private function getThumbnailPath(int $day, string $size): string
    {
        return sprintf("%s/%02d_t_%dx%d.jpg", $this->cachePath, $day, self::THUMB_SIZES[$size]['w'], self::THUMB_SIZES[$size]['h']);
    }

    /**
     * Resize an image to a given width
     * @param int $day
     * @param string $size
     * @return string
     * @throws \ImagickException
     */
    private function resizeImage(int $day, string $size): string
    {
        // check cache
        $cacheFile = $this->getThumbnailPath($day, $size);
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
        $thumb->resizeImage(self::THUMB_SIZES[$size]['w'], 0, imagick::FILTER_CATROM, 0.5);
        if ($thumb->getImageHeight() > self::THUMB_SIZES[$size]['h']) {
            $thumb->resizeImage(0, self::THUMB_SIZES[$size]['h'], imagick::FILTER_CATROM, 0.5);
        }
        file_put_contents($cacheFile, $thumb->getImageBlob());
        return $cacheFile;
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
        $mime = mime_content_type($imagePath) ?: 'application/octet-stream';

        if ($size === 'download') {
            $fileExt = $mime == 'application/octet-stream' ? 'bin' : (explode('/', $mime)[1] ?? 'bin');
            $fileName = sprintf("day_%02d.%s", $day, $fileExt);
            $response = $response
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Transfer-Encoding', 'Binary')
                ->withHeader('Content-Disposition', "attachment; filename=\"$fileName\"");
        }

        if (array_key_exists($size, self::THUMB_SIZES)) {
            $imagePath = $this->resizeImage($dayNumber, $size);
        }

        $response->getBody()->write(file_get_contents($imagePath) ?: 'Could not read file');
        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) filesize($imagePath));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $day
     * @param string $size
     * @return Response
     * @throws \ImagickException
     */
    public function createThumbnail(Request $request, Response $response, string $day, string $size): Response
    {
        $dayNumber = intval($day);
        $imagePath = $this->getImagePath($dayNumber);
        if (!is_readable($imagePath) || filesize($imagePath) === 0) {
            throw new HttpNotFoundException($request, "File not found");
        }
        $this->resizeImage($dayNumber, $size);
        return $response->withStatus(200);
    }

    /**
     * @param Response $response
     * @param string $day
     * @param string $size
     * @return Response
     */
    public function deleteThumbnail(Response $response, string $day, string $size): Response
    {
        $dayNumber = intval($day);
        $thumbPath = $this->getThumbnailPath($dayNumber, $size);
        if (!is_file($thumbPath)) {
            return $response->withStatus(204);
        }
        unlink($thumbPath);
        if (!is_file($thumbPath)) {
            return $response->withStatus(200);
        }
        return $response->withStatus(500);
    }
}
