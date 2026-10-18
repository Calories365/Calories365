<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The current user as the SPA sees it: the cabinet shows the email only when
 * the person gave one, accounts opened from the Mini App carry a made-up address.
 */
class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_account_opened_from_the_mini_app_has_a_synthetic_email(): void
    {
        config(['services.telegram_mini_app.email_domain' => 'telegram.calories365.local']);
        $user = User::factory()->create(['email' => 'tg42@telegram.calories365.local', 'telegram_id' => 42]);

        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJson(['synthetic_email' => true, 'telegram_auth' => true]);
    }

    public function test_an_email_the_person_gave_is_not_synthetic(): void
    {
        config(['services.telegram_mini_app.email_domain' => 'telegram.calories365.local']);
        $user = User::factory()->create(['email' => 'person@example.com']);

        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJson(['synthetic_email' => false, 'telegram_auth' => false]);
    }
}
