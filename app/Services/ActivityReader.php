<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads what a user said or typed about how they moved and slept into
 * activities of the catalogue (ActivityCatalog), in one request to the cheap
 * text model: "час гулял", "в 7 пробежал 5 км", "спал с часу до семи". Food in
 * the same words is not read here; the meal draft reads it.
 */
class ActivityReader
{
    private const MEALS = ['morning' => 'breakfast', 'dinner' => 'lunch', 'supper' => 'dinner'];

    /**
     * @param  array<string, string>  $meals  when the meals of today were eaten: part of the day => "HH:MM"
     * @return array<int, array{type: string|null, title: string, minutes: int, met: float, time: string|null}>
     *
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \RuntimeException
     */
    public function read(string $text, string $locale, array $meals = []): array
    {
        $language = match ($locale) {
            'ua' => 'Ukrainian',
            'en' => 'English',
            default => 'Russian',
        };

        $eaten = [];
        foreach ($meals as $part => $time) {
            $eaten[] = (self::MEALS[$part] ?? $part).' at '.$time;
        }

        $prompt = "The user of a calorie diary tells how they moved or slept. They speak {$language}. "
            .'Now it is '.now()->format('H:i').' of '.now()->format('l').'.'
            .($eaten === [] ? '' : ' Today they had '.implode(', ', $eaten).'. Movement told as after a meal ("после завтрака", "после обеда", "after dinner") began about 20 minutes after the time of that meal.')
            ."\n\n"
            ."The user said:\n".trim($text)."\n\n"
            .'Return '.ActivityCatalog::instructions($language)."\n\n"
            .'"Час назад", "полчаса назад" count back from now. Food and drinks are not activities. When the words tell '
            .'no activity, return none.';

        $keys = config('services.openai.keys');

        $result = Http::timeout(60)
            ->withToken((string) ($keys[$locale] ?? $keys['ru'] ?? ''))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.models.meal_text'),
                'reasoning_effort' => config('services.openai.reasoning_effort'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'activities',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['activities'],
                            'properties' => ['activities' => ActivityCatalog::schema()],
                        ],
                    ],
                ],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])
            ->throw()
            ->json();

        $decoded = json_decode((string) ($result['choices'][0]['message']['content'] ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('The activity model gave no JSON');
        }

        return ActivityCatalog::valid($decoded['activities'] ?? []);
    }
}
