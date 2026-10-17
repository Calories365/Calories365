<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WayForPayCallbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->user = User::factory()->create();
        Payment::create([
            'user_id' => $this->user->id, 'order_reference' => 'DH1', 'status' => 'Pending', 'signature' => str_repeat('a', 32),
        ]);
    }

    public function test_a_suspended_subscription_is_stored(): void
    {
        Payment::processCallback($this->wayForPayCallback('Suspended'));

        $this->assertDatabaseHas('payments', ['order_reference' => 'DH1', 'status' => 'Suspended']);
    }

    public function test_an_approved_payment_gives_a_month_of_premium(): void
    {
        Payment::processCallback($this->wayForPayCallback('Approved'));

        $this->assertDatabaseHas('payments', ['order_reference' => 'DH1', 'status' => 'Approved']);
        $this->assertTrue($this->user->fresh()->isPremium());
    }

    /**
     * @return array<string, string>
     */
    private function wayForPayCallback(string $status): array
    {
        return [
            'orderReference' => 'DH1',
            'transactionStatus' => $status,
            'merchantSignature' => str_repeat('b', 32),
            'email' => $this->user->email,
        ];
    }
}
