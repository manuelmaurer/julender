<?php

namespace App\Command;

use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\NonBufferedBody;
use Slim\Psr7\Request;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Teapot\StatusCode;

#[AsCommand(
    name: 'image:cache:build',
    description: 'Build image cache',
    help: 'Generate cached thumbnails for all images in all preview sizes'
)]
class ImageCacheBuildCommand extends ImageCacheCommand
{
    /**
     * @param RequestHandlerInterface $slimApp
     * @param string|null $name
     * @param string $apiKey
     */
    public function __construct(private readonly RequestHandlerInterface $slimApp, ?string $name = null, string $apiKey = '')
    {
        parent::__construct($name, $apiKey);
    }

    /**
     * @param OutputInterface $output
     * @return int
     */
    public function __invoke(OutputInterface $output): int
    {
        $output->writeln([
            'Image cache builder',
            '===================',
            '',
        ]);
        $errors = [];
        foreach ($this->uris as $id => $uri) {
            $output->write("  $id\t");
            $request = new Request('PUT', $uri, $this->headers, [], [], new NonBufferedBody());
            try {
                $response = $this->slimApp->handle($request);
            } catch (HttpNotFoundException) {
                $output->writeln('<comment>UNAV</comment>');
                continue;
            } catch (\Throwable $e) {
                $errors[] = "$id: Exception Code {$e->getCode()}";
                $output->writeln('<error>FAILED</error>');
                continue;
            }
            $scode = $response->getStatusCode();
            if ($scode === StatusCode::NO_CONTENT || $scode === StatusCode::OK) {
                $output->writeln('<info>OK</info>');
                continue;
            }
            $errors[] = "$id: Response Code $scode";
            $output->writeln("<error>FAILED</error>");
        }
        if (empty($errors)) {
            return Command::SUCCESS;
        }
        $output->writeln($errors);
        return Command::FAILURE;
    }
}
