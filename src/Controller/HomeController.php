<?php

namespace App\Controller;

use App\Trait\ReleaseDateTrait;
use DateTimeImmutable;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class HomeController
{
    use ReleaseDateTrait;

    public function home(Response $response, Twig $twig, Container $container): Response
    {
        $tz = $container->get('timezone');
        $now = new DateTimeImmutable('now', $tz);
        $days = array_reduce(range(1, 24), function ($carry, $day) use ($tz, $now) {
            $ts = $this->getReleaseDate($day, $tz);
            $carry[$day] = [
                'day' => $day,
                'release' => $ts->format('Y-m-d H:i:s'),
                'isReleased' => $ts < $now,
            ];
            return $carry;
        }, []);
        return $twig->render($response, 'home.twig', ['data' => $days]);
    }

}
