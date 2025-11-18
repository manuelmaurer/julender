<?php

/**
 * Global configuration - DO NOT CHANGE THIS FILE
 * Use config.local.php for local configuration
 */

declare(strict_types=1);

$localConfig = is_readable(__DIR__ . '/config.local.php') ? (require __DIR__ . '/config.local.php') : [];

$configMap = [
    ['key' => 'title', 'env' => 'JUL_TITLE', 'default' => 'Julender', 'fmt' => fn ($v) => trim(strval($v))],
    ['key' => 'timezone', 'env' => 'JUL_TIMEZONE', 'default' => 'Europe/Berlin', 'fmt' => fn ($v) => new DateTimeZone($v)],
    ['key' => 'languages', 'env' => 'JUL_LANGUAGES', 'default' => 'en,de', 'fmt' => fn ($v) => explode(',', $v)],
    ['key' => 'adventMonth', 'env' => 'JUL_ADVENT_MONTH', 'default' => '12', 'fmt' => fn ($v) => intval($v)],
    ['key' => 'password', 'env' => 'JUL_PASSWORD', 'default' => 'null', 'fmt' => fn ($v) => $v == 'null' ? null : strval($v)],
    ['key' => 'debug', 'env' => 'JUL_DEBUG', 'default' => '0', 'fmt' => fn ($v) => boolval($v)],
    ['key' => 'imageCache', 'env' => 'JUL_IMAGE_CACHE', 'default' => '1', 'fmt' => fn ($v) => boolval($v)],
    ['key' => 'apiKey', 'env' => 'JUL_API_KEY', 'default' => random_bytes(32), 'fmt' => fn ($v) => strval($v)],
];

$env = getenv();

return array_reduce($configMap, function ($carry, $item) use ($env, $localConfig) {
    $value = $item['default'];
    if (array_key_exists($item['key'], $localConfig)) {
        $value = $localConfig[$item['key']];
    }
    if (array_key_exists($item['key'], $_ENV)) {
        $value = $_ENV[$item['key']];
    }
    if (array_key_exists($item['key'], $env)) {
        $value = $env[$item['key']];
    }
    $carry[$item['key']] = $item['fmt']($value);
    return $carry;
}, []);
