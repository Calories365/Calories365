<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Signing in from the Mini App: the panel checks the initData, and a refusal
 * says why, so the screen of the failed sign-in shows where it stopped.
 */
class TelegramWebAppAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.bot_panel.url' => 'http://panel.test', 'services.bot_panel.key' => 'key']);
    }

    public function test_a_refused_signature_gives_the_panel_answer(): void
    {
        Http::fake(['panel.test/*' => Http::response(['valid' => false], 401)]);

        $this->postJson('/api/telegram/web-app-auth', ['init_data' => 'auth_date=1&hash=x'])
            ->assertUnauthorized()
            ->assertJson(['reason' => 'panel_401']);
    }

    public function test_a_wrong_api_key_gives_the_panel_answer(): void
    {
        Http::fake(['panel.test/*' => Http::response(['error' => 'Invalid API key'], 403)]);

        $this->postJson('/api/telegram/web-app-auth', ['init_data' => 'auth_date=1&hash=x'])
            ->assertUnauthorized()
            ->assertJson(['reason' => 'panel_403']);
    }

    public function test_an_unreachable_panel_is_told_apart(): void
    {
        Http::fake(['panel.test/*' => Http::failedConnection()]);

        $this->postJson('/api/telegram/web-app-auth', ['init_data' => 'auth_date=1&hash=x'])
            ->assertUnauthorized()
            ->assertJson(['reason' => 'unreachable']);
    }

    public function test_a_panel_that_is_not_configured_is_told_apart(): void
    {
        config(['services.bot_panel.url' => null]);

        $this->postJson('/api/telegram/web-app-auth', ['init_data' => 'auth_date=1&hash=x'])
            ->assertUnauthorized()
            ->assertJson(['reason' => 'not_configured']);
    }
}
