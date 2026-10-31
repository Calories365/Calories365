<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The site is a SPA on the session: after login every API request carries the
 * session cookie through Sanctum.
 */
class SiteSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_answers_after_login(): void
    {
        config()->set('sanctum.stateful', ['localhost']);
        $user = User::factory()->create(['password' => 'secret-password']);
        $headers = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

        $this->withHeaders($headers)
            ->postJson('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertOk();

        // The next request comes with the session only, as from the browser.
        $this->app['auth']->forgetGuards();

        $this->withHeaders($headers)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }
}
