<?php

namespace Tests\Feature;

use App\Jobs\ProcessVoiceRecordJob;
use App\Models\Product;
use App\Services\AudioConversionService;
use App\Services\ProductCatalog;
use App\Services\SpeechToTextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessVoiceRecordJobTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_the_items_are_found_together_and_the_one_still_missing_is_generated(): void
    {
        $baked = Product::create(['calories' => 191, 'proteins' => 23, 'carbohydrates' => 0, 'fats' => 11, 'fibers' => 0]);

        $catalog = Mockery::mock(ProductCatalog::class);
        $catalog->shouldReceive('find')->once()
            ->with(['Нога запечённая куриная', 'Гречка варёная'], 1, 'ru')
            ->andReturn([['id' => 12, 'product_id' => $baked->id, 'locale' => 'ru', 'name' => 'куриная ножка в духовке'], null]);

        $this->runJob(function ($stt) {
            $stt->shouldReceive('convertSpeechToText')->andReturn("Нога запечённая куриная - 200 граммов;\nГречка варёная - 150 граммов;");
            $stt->shouldReceive('generateNewProductData')->once()->with('Гречка варёная')
                ->andReturn('Калории - 105; Белки - 4; Жиры - 1; Углеводы - 21;');
        }, $catalog);

        $record = Cache::get('voice:job:'.self::RID);
        $this->assertSame('ready', $record['status']);
        $this->assertSame('куриная ножка в духовке', $record['products'][0]['product_translation']['name']);
        $this->assertSame($baked->id, $record['products'][0]['product']['id']);
        $this->assertSame(200.0, $record['products'][0]['quantity']);
        $this->assertTrue($record['products'][1]['is_generated']);
        $this->assertSame(105.0, $record['products'][1]['product']['calories']);
    }

    private function makeJob(): ProcessVoiceRecordJob
    {
        return new ProcessVoiceRecordJob(userId: 1, rid: self::RID, localPath: self::SOURCE, locale: 'ru');
    }

    private function runJob(callable $speechToText, ?ProductCatalog $catalog = null): void
    {
        $audio = Mockery::mock(AudioConversionService::class);
        $audio->shouldReceive('convertToMp3')->andReturn([null, null]);

        $stt = Mockery::mock(SpeechToTextService::class);
        $speechToText($stt);

        $this->makeJob()->handle($audio, $stt, $catalog ?? Mockery::mock(ProductCatalog::class));
    }
}
