<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Some environments inject values into $_SERVER (e.g. APP_ENV=local,
        // DB_CONNECTION=mysql). Laravel's env repository reads $_SERVER before
        // $_ENV, so mirror PHPUnit's forced values here before the app boots.
        foreach ([
            'APP_ENV',
            'APP_URL',
            'APP_MAINTENANCE_DRIVER',
            'BCRYPT_ROUNDS',
            'BROADCAST_CONNECTION',
            'CACHE_STORE',
            'DB_CONNECTION',
            'DB_DATABASE',
            'DB_URL',
            'MAIL_MAILER',
            'QUEUE_CONNECTION',
            'SESSION_DRIVER',
            'PULSE_ENABLED',
            'TELESCOPE_ENABLED',
            'NIGHTWATCH_ENABLED',
        ] as $key) {
            $value = $_ENV[$key] ?? getenv($key);

            if ($value !== false && $value !== null) {
                $_SERVER[$key] = $value;
            }
        }

        parent::setUp();
    }
}
