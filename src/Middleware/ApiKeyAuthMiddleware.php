<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

/**
 * This Middleware checks if the X-API-KEY header is valid
 */
class ApiKeyAuthMiddleware
{
    private string $apiKey;

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // If no api key is configured, fail
        if (empty($this->apiKey)) {
            throw new HttpUnauthorizedException($request, 'Unauthorized');
        }
        $requestKey = $request->getHeaderLine('X-API-KEY');
        if ($requestKey !== $this->apiKey) {
            throw new HttpUnauthorizedException($request, 'Unauthorized');
        }
        return $handler->handle($request);
    }
}
