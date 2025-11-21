<?php

namespace App\Command;

use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Headers;
use Slim\Psr7\NonBufferedBody;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Teapot\StatusCode;

abstract class ImageCacheCommand extends Command
{
    protected readonly Headers $headers;
    /** @var array<string, Uri> */
    protected readonly array $uris;

    /**
     * @param string|null $name
     * @param string $apiKey
     */
    public function __construct(?string $name = null, private readonly string $apiKey = '')
    {
        parent::__construct($name);
        $this->headers = new Headers([
            'X-API-KEY' => $this->apiKey,
        ]);
        $this->uris = array_reduce(range(1, 24), function ($carry, $day) {
            $carry[sprintf("Day %02d PREV", $day)] = new Uri('http', 'localhost', 8000, "/v1/images/$day/preview");
            $carry[sprintf("Day %02d FULL", $day)] = new Uri('http', 'localhost', 8000, "/v1/images/$day/full");
            return $carry;
        }, []);
    }
}
