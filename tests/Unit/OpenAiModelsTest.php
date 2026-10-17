<?php

namespace Tests\Unit;

use App\Services\ChatGPTFeedback;
use App\Services\SpeechToTextService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * gpt-4o and whisper-1 are switched off by OpenAI (January and February 2027).
 * The GPT-6 models reject max_tokens and any temperature but 1, and
 * gpt-transcribe takes "languages[]" instead of "language".
 */
class OpenAiModelsTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        app()->setLocale('ru');
    }

    public function test_speech_is_transcribed_by_gpt_transcribe_and_analysed_by_the_meal_model(): void
    {
        $service = $this->speechService([
            new Response(200, [], json_encode(['text' => 'Я съел 200 грамм творога'])),
            new Response(200, [], json_encode(['choices' => [['message' => ['content' => 'Творог - 200 граммов;']]]])),
        ]);

        $result = $service->convertSpeechToText(base_path('tests/Unit/ExampleTest.php'));

        $this->assertSame('Творог - 200 граммов;', $result);

        $transcription = (string) $this->sent[0]['request']->getBody();
        $this->assertMatchesRegularExpression('/name="model"\r\n(?:[^\r\n]+\r\n)*\r\ngpt-transcribe\r\n/', $transcription);
        $this->assertMatchesRegularExpression('/name="languages\[\]"\r\n(?:[^\r\n]+\r\n)*\r\nru\r\n/', $transcription);
        $this->assertStringNotContainsString('name="language"', $transcription);

        $analysis = json_decode((string) $this->sent[1]['request']->getBody(), true);
        $this->assertSame('gpt-6.1-sol', $analysis['model']);
        $this->assertSame('low', $analysis['reasoning_effort']);
        $this->assertArrayNotHasKey('temperature', $analysis);
        $this->assertArrayNotHasKey('max_tokens', $analysis);
    }

    public function test_nutrition_facts_of_a_product_use_the_efficient_model(): void
    {
        $service = $this->speechService([
            new Response(200, [], json_encode(['choices' => [['message' => ['content' => 'Калории - 120;']]]])),
        ]);

        $service->generateNewProductData('Творог');

        $this->assertSame('gpt-6-luna', json_decode((string) $this->sent[0]['request']->getBody(), true)['model']);
    }

    public function test_the_key_follows_the_locale(): void
    {
        config()->set('services.openai.keys', ['ua' => 'key-ua', 'ru' => 'key-ru', 'en' => 'key-en']);
        app()->setLocale('ua');

        $service = $this->speechService([
            new Response(200, [], json_encode(['choices' => [['message' => ['content' => 'ok']]]])),
        ]);
        $service->generateNewProductData('Сир');

        $this->assertSame('Bearer key-ua', $this->sent[0]['request']->getHeaderLine('Authorization'));
    }

    public function test_diet_feedback_uses_the_feedback_model_without_rejected_parameters(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Add protein.']]]])]);

        $this->assertSame('Add protein.', app(ChatGPTFeedback::class)->generateNewProductData('prompt'));

        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-6.1-sol'
            && $request['reasoning_effort'] === 'low'
            && ! isset($request['temperature'])
            && ! isset($request['max_tokens']));
    }

    /**
     * @param  array<int, Response>  $responses
     */
    private function speechService(array $responses): SpeechToTextService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));

        return new SpeechToTextService(new Client(['handler' => $stack]));
    }
}
