<?php

namespace Tests\Feature;

use Tests\TestCase;

class DevOpcacheTest extends TestCase
{
    public function test_endpoint_rejects_requests_without_the_right_token(): void
    {
        config(['app.dev_opcache_token' => 'secret-token']);

        $this->get('/__dev/opcache')->assertForbidden();
        $this->get('/__dev/opcache?token=salah')->assertForbidden();
    }

    public function test_endpoint_is_not_available_when_no_token_is_configured(): void
    {
        config(['app.dev_opcache_token' => null]);

        $this->get('/__dev/opcache?token=apa-saja')->assertNotFound();
    }

    public function test_endpoint_invalidates_the_requested_php_files(): void
    {
        config(['app.dev_opcache_token' => 'secret-token']);

        $this->get('/__dev/opcache?token=secret-token&path%5B%5D=app/Models/User.php')
            ->assertOk()
            ->assertJsonPath('mode', 'invalidate')
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.path', 'app/Models/User.php');
    }

    public function test_endpoint_ignores_paths_outside_the_project_or_not_php(): void
    {
        config(['app.dev_opcache_token' => 'secret-token']);

        $this->get('/__dev/opcache?token=secret-token&path%5B%5D=../../etc/passwd&path%5B%5D=README.md')
            ->assertOk()
            ->assertJsonPath('mode', 'invalidate')
            ->assertJsonCount(0, 'files');
    }

    public function test_endpoint_can_reset_the_whole_cache(): void
    {
        config(['app.dev_opcache_token' => 'secret-token']);

        $this->get('/__dev/opcache?token=secret-token')
            ->assertOk()
            ->assertJsonPath('mode', 'reset')
            ->assertJsonStructure(['mode', 'ok']);
    }
}
