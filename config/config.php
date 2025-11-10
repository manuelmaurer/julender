<?php

/**
 * Global configuration - DO NOT CHANGE THIS FILE
 * Use config.local.php for local configuration
 */

declare(strict_types=1);

// Get environment variable with default value
$getEnv = function (string $key, mixed $default) {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'title' => $getEnv('JUL_TITLE', 'Julender'),
    'timezone' => new DateTimeZone($getEnv('JUL_TIMEZONE', 'Europe/Berlin')),
    'languages' => explode(',', $getEnv('JUL_LANGUAGES', 'en,de')),
    'adventMonth' => $getEnv('JUL_ADVENT_MONTH', 12),
    'password' => $getEnv('JUL_PASSWORD', null),
    'debug' => $getEnv('JUL_DEBUG', false),
    'dateFormat' => 'Y-m-d'
];
