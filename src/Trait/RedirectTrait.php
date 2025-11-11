<?php

declare(strict_types=1);

namespace App\Trait;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Trait to redirect to a location
 */
trait RedirectTrait
{
    /**
     * Redirect to a location
     * @param Response $response
     * @param string $location
     * @return Response
     */
    protected function redirectTo(Response $response, string $location): Response
    {
        return $response
            ->withHeader('Location', $location)
            ->withStatus(302);
    }
}
