<?php

namespace Tests\Feature;

use App\Jobs\ProcessVoiceRecordJob;
use App\Services\AudioConversionService;
use App\Services\SpeechToTextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessVoiceRecordJobTest extends TestCase
{
    private const RID = 'rid-1';

    private const SOURCE = 'voice_records/rid-1.webm';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put(self::SOURCE, 'audio');
    }

    public function test_speech_that_was_not_recognised_fails_the_record_without_a_retry(): void
    {
        $this->runJob(fn ($stt) => $stt->shouldReceive('convertSpeechToText')->andReturn(false));

        $this->assertSame('failed', Cache::get('voice:job:'.self::RID)['status']);
        Storage::disk('public')->assertMissing(self::SOURCE);
    }

    public function test_a_transient_error_keeps_the_record_for_the_retry(): void
    {
        try {
            $this->runJob(fn ($stt) => $stt->shouldReceive('convertSpeechToText')->andThrow(new \RuntimeException('ffmpeg crashed')));
            $this->fail('The job must throw so the queue retries it.');
        } catch (\RuntimeException) {
        }

        Storage::disk('public')->assertExists(self::SOURCE);
        $this->assertNotSame('failed', Cache::get('voice:job:'.self::RID)['status'] ?? null);
    }

    public function test_the_last_failed_attempt_fails_the_record_and_removes_the_file(): void
    {
        $this->makeJob()->failed(new \RuntimeException('ffmpeg crashed'));

        $this->assertSame('failed', Cache::get('voice:job:'.self::RID)['status']);
        Storage::disk('public')->assertMissing(self::SOURCE);
    }

    public function test_an_analysis_error_fails_the_record_and_removes_the_file(): void
    {
        $this->runJob(fn ($stt) => $stt->shouldReceive('convertSpeechToText')->andReturn(['error' => 'OpenAI is down']));

        $this->assertSame('failed', Cache::get('voice:job:'.self::RID)['status']);
        Storage::disk('public')->assertMissing(self::SOURCE);
    }

    private function makeJob(): ProcessVoiceRecordJob
    {
        return new ProcessVoiceRecordJob(userId: 1, rid: self::RID, localPath: self::SOURCE, locale: 'ru');
    }

    private function runJob(callable $speechToText): void
    {
        $audio = Mockery::mock(AudioConversionService::class);
        $audio->shouldReceive('convertToMp3')->andReturn([null, null]);

        $stt = Mockery::mock(SpeechToTextService::class);
        $speechToText($stt);

        $this->makeJob()->handle($audio, $stt);
    }
}
