<?php

namespace Tests\Feature;

use App\Support\EnvironmentGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Garde-fou : les commandes destructives sont refusées en production sans autorisation explicite. */
class EnvironmentGuardTest extends TestCase
{
    use RefreshDatabase;

    private function asEnvironment(string $env): void
    {
        $this->app['env'] = $env;
    }

    public function test_destructive_commands_allowed_outside_production(): void
    {
        $this->asEnvironment('testing');
        $this->assertTrue(EnvironmentGuard::allowsDestructive());
    }

    public function test_destructive_commands_refused_in_production_by_default(): void
    {
        $this->asEnvironment('production');
        config(['app.env' => 'production']);
        putenv('ALLOW_DESTRUCTIVE_COMMANDS=false');

        $this->assertFalse(EnvironmentGuard::allowsDestructive());
    }

    public function test_purge_personal_data_refuses_in_production(): void
    {
        $this->asEnvironment('production');

        $this->artisan('consulat:purge-personal-data')
            ->expectsOutputToContain('Refusé')
            ->assertExitCode(1);
    }

    public function test_purge_demo_refuses_in_production(): void
    {
        $this->asEnvironment('production');

        $this->artisan('consulat:purge-demo', ['--force' => true])
            ->expectsOutputToContain('Refusé')
            ->assertExitCode(1);
    }

    public function test_production_allows_destructive_commands_only_with_explicit_flag(): void
    {
        $this->asEnvironment('production');
        putenv('ALLOW_DESTRUCTIVE_COMMANDS=true');

        try {
            $this->assertTrue(EnvironmentGuard::allowsDestructive());
        } finally {
            putenv('ALLOW_DESTRUCTIVE_COMMANDS=false');
        }
    }
}
