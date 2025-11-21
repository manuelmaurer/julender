<?php

use Symfony\Component\Console\Application;

(require __DIR__ . '/../config/bootstrap.php')
    ->get(Application::class)
    ->run();
