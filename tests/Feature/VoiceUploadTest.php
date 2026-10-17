<?php

namespace Tests\Feature;

use App\Jobs\ProcessVoiceRecordJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceUploadTest extends TestCase
{
    use RefreshDatabase;

    private const PANEL = 'http://bot-panel.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Queue::fake();
        Storage::fake('public');
        config()->set('services.bot_panel', ['url' => self::PANEL, 'key' => 'testing-bot-key', 'host' => 'bot-panel.test']);

        $this->user = User::factory()->create();
    }

    public function test_a_user_within_the_limit_gets_the_record_accepted(): void
    {
        Http::fake([self::PANEL.'/api/subscription-check/*' => Http::response(['canTranscribeAudio' => true])]);

        $this->upload()
            ->assertStatus(202)
            ->assertJsonPath('message', 'accepted');

        Queue::assertPushed(ProcessVoiceRecordJob::class);
        Http::assertSent(fn ($request) => $request->url() === self::PANEL.'/api/subscription-check/'.$this->user->id
            && $request->hasHeader('X-Api-Key', 'testing-bot-key'));
    }

    public function test_a_user_over_the_limit_is_asked_to_buy_premium(): void
    {
        Http::fake([self::PANEL.'/api/subscription-check/*' => Http::response(['canTranscribeAudio' => false])]);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('message', 'please_buy_premium');

        Queue::assertNothingPushed();
    }

    public function test_an_unreachable_panel_is_reported_as_unavailable_not_as_a_spent_limit(): void
    {
        Http::fake([self::PANEL.'/*' => Http::response('Bad gateway', 502)]);

        $this->upload()
            ->assertStatus(503)
            ->assertJsonPath('message', 'voice_service_unavailable');

        Queue::assertNothingPushed();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_an_unconfigured_panel_is_reported_as_unavailable(): void
    {
        config()->set('services.bot_panel.url', null);

        $this->upload()
            ->assertStatus(503)
            ->assertJsonPath('message', 'voice_service_unavailable');
    }

    private function upload()
    {
        return $this->actingAs($this->user)->post('/api/voice/upload', [
            'audio' => UploadedFile::fake()->create('record.webm', 10, 'audio/webm'),
        ], ['Accept' => 'application/json']);
    }
}
