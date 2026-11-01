<?php

namespace App\Services;

/**
 * The kinds of movement a user picks with one tap or names by voice, with
 * their MET from the 2024 Adult Compendium of Physical Activities
 * (https://pacompendium.com, code in the comment), and a night's sleep. The
 * site, the Mini App and the bot show the same list (presets()); the models
 * reading what the user said choose from it too (instructions(), schema()).
 */
class ActivityCatalog
{
    public const SLEEP = 'sleep';

    public const OTHER = 'other';

    /**
     * type => [MET, minutes offered first, emoji], the most common first.
     */
    public const TYPES = [
        'walk' => [3.0, 30, '🚶'],          // 17170 walking, 2.5 mph
        'errands' => [3.3, 30, '🛒'],       // 05060 food shopping, standing or walking
        'walk_brisk' => [4.8, 30, '👟'],    // 17200 walking, 3.5–3.9 mph, brisk
        'run' => [8.0, 30, '🏃'],           // 12150 running
        'gym' => [3.5, 60, '🏋️'],           // 02054 resistance training, multiple exercises
        'bike' => [6.8, 30, '🚴'],          // 01011 bicycling, to and from work
        'housework' => [3.3, 30, '🧹'],     // 05030 cleaning, house, moderate effort
        self::SLEEP => [1.0, 450, '😴'],    // 07030 sleeping
        'workout' => [3.8, 20, '🤸'],       // 02022 calisthenics, moderate effort
        'hiit' => [7.0, 20, '⚡'],          // 02210 high intensity interval exercise, moderate
        'stairs' => [6.8, 10, '🪜'],        // 17131 stair climbing, general
        'swim' => [5.8, 45, '🏊'],          // 18240 swimming laps, freestyle, slow
        'yoga' => [2.3, 30, '🧘'],          // 02150 yoga, hatha
        'dance' => [5.0, 45, '💃'],         // 03010 ballet, modern or jazz, class
        'ball' => [7.0, 60, '⚽'],          // 15610 soccer, casual
        'cooking' => [2.0, 30, '🍳'],       // 05050 cooking or food preparation
        'kids' => [3.5, 30, '🧸'],          // 05175 walking or running, playing with children
        'driving' => [2.0, 30, '🚗'],       // 16010 automobile driving
        'transit' => [1.3, 30, '🚌'],       // 16016 riding in a bus or train
        'sitting' => [1.5, 90, '🪑'],       // 11580 sitting tasks, light effort
    ];

    /**
     * How many of the kinds, the most common, are shown before "more".
     */
    public const SHOWN_FIRST = 8;

    /**
     * MET of the intensities of the form typed on the page before the kinds:
     * sitting at a course and driving, a walk or a shop, a brisk walk or easy
     * cycling, a run or a workout.
     */
    public const INTENSITIES = ['sitting' => 1.5, 'light' => 2.5, 'moderate' => 4.0, 'hard' => 7.0];

    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function met(string $type): float
    {
        return self::TYPES[$type][0];
    }

    public static function title(string $type, ?string $locale = null): string
    {
        return __('activities.'.$type, [], $locale);
    }

    /**
     * The kinds in the user's language, in the order they are shown.
     *
     * @return array<int, array{type: string, title: string, met: float, minutes: int, emoji: string}>
     */
    public static function presets(?string $locale = null): array
    {
        $presets = [];

        foreach (self::TYPES as $type => [$met, $minutes, $emoji]) {
            $presets[] = ['type' => $type, 'title' => self::title($type, $locale), 'met' => $met, 'minutes' => $minutes, 'emoji' => $emoji];
        }

        return $presets;
    }

    /**
     * What a model reading the user's words is told about activities.
     */
    public static function instructions(string $language): string
    {
        $types = [];
        foreach (self::TYPES as $type => [$met]) {
            $types[] = "{$type} (MET {$met})";
        }

        return 'activities: each physical activity, each stretch of everyday movement and each sleep the user tells '
            .'they did today ("пробежал 5 км за 30 минут", "час в зале", "10 тысяч шагов", "час гулял", "сходил в '
            .'магазин", "съездил на машине на курсы, там два часа, ходил мало", "спал 6 часов"). type: the closest of '
            .implode(', ', $types).'; "other" when none fits. '
            ."title in {$language}, short, as said (\"Бег 5 км\"); minutes, estimated from the distance or the steps when "
            .'not said ("10 тысяч шагов" is about 100 minutes of walking), a usual time when nothing tells it; met: of the '
            .'type, or for "other" from the Compendium of Physical Activities; time, when it began as "HH:MM" when the '
            .'user tells it ("в 6 вечера" is "18:00") or it follows from what is told ("в 10 поехал на курсы, полчаса ехал, '
            .'там два часа сидел": the drive at "10:00", the course at "10:30"; "спал с 23 до 7" is "23:00", 480 minutes; '
            .'"лёг в час, встал в 7" is "01:00", 360 minutes), otherwise null. An activity planned or done on another day '
            .'is not given.';
    }

    /**
     * The activities in a JSON schema of a model's answer.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['type', 'title', 'minutes', 'met', 'time'],
                'properties' => [
                    'type' => ['type' => 'string', 'enum' => [...array_keys(self::TYPES), self::OTHER]],
                    'title' => ['type' => 'string'],
                    'minutes' => ['type' => 'number'],
                    'met' => ['type' => 'number'],
                    'time' => ['type' => ['string', 'null']],
                ],
            ],
        ];
    }

    /**
     * The activities of a model's answer that can be true; a kind of the
     * catalogue keeps its own MET.
     *
     * @param  mixed  $activities
     * @return array<int, array{type: string|null, title: string, minutes: int, met: float, time: string|null}>
     */
    public static function valid($activities): array
    {
        $valid = [];

        foreach (is_array($activities) ? $activities : [] as $activity) {
            if (! is_array($activity)) {
                continue;
            }

            $type = (string) ($activity['type'] ?? '');
            $type = self::exists($type) ? $type : null;
            $title = trim((string) ($activity['title'] ?? ''));
            $minutes = (int) round((float) ($activity['minutes'] ?? 0));
            $met = $type !== null ? self::met($type) : round((float) ($activity['met'] ?? 0), 1);
            $time = trim((string) ($activity['time'] ?? ''));
            $longest = $type === self::SLEEP ? 960 : 600;

            if ($title === '' && $type !== null) {
                $title = self::title($type);
            }

            if ($title !== '' && $minutes >= 1 && $minutes <= $longest && $met >= 0.9 && $met <= 20) {
                $valid[] = [
                    'type' => $type,
                    'title' => mb_substr($title, 0, 150),
                    'minutes' => $minutes,
                    'met' => $met,
                    'time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : null,
                ];
            }
        }

        return $valid;
    }
}
