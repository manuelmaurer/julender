<?php

namespace App\Middleware;

class DebugMiddleware
{
    public static function isDebug(): bool
    {
        return ($_ENV['DEBUG'] ?? false);
    }

}
