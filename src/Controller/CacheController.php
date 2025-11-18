<?php

declare(strict_types=1);

namespace App\Controller;


use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * This Controller handles caches
 */
readonly class CacheController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param string $cache
     * @return Response
     */
    public function clearCache(Request $request, Response $response, string $cache = ''): Response
    {
        $caches = match ($cache) {
            'image' => ['image' => __DIR__ . '/../../tmp/image_cache'],
            'frontend' => ['frontend' => __DIR__ . '/../../tmp/twig_cache'],
            'container' => ['container' => __DIR__ . '/../../tmp/container_cache'],
            'all' => [
                'image' => __DIR__ . '/../../tmp/image_cache',
                'frontend' => __DIR__ . '/../../tmp/twig_cache',
                'container' => __DIR__ . '/../../tmp/container_cache',
            ],
            default => [],
        };
        if (empty($caches)) {
            throw new HttpBadRequestException($request, 'Invalid cache');
        }
        $result = [];
        foreach ($caches as $key => $path) {
            exec("rm -rf $path");
            $result[$key] = is_dir($path) ? 'error' : 'success';
        }
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
