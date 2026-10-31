<?php

namespace App\Services;

/**
 * The insulin curve of a day as a PNG, for the bot: the same picture as the page,
 * the line colored by its zone and dashed after now, the zones as bands, a meal
 * as a dashed mark with its time, an activity as a blue band.
 *
 * Drawn in PHP and packed with zlib, since the images of the diary have no GD:
 * lines are anti-aliased by their distance to each pixel, and the only text, the
 * times and the levels, is a bitmap font of digits. Words go into the caption.
 */
class InsulinChartImage
{
    private const WIDTH = 1000;

    private const HEIGHT = 500;

    private const LEFT = 58;

    private const RIGHT = 978;

    private const TOP = 34;

    private const BOTTOM = 452;

    private const LINE_HALF_WIDTH = 2.6;

    private const ZONE_COLORS = ['low' => [79, 157, 85], 'medium' => [217, 164, 0], 'high' => [208, 67, 58]];

    private const ZONE_BANDS = ['low' => [[79, 157, 85], 0.08], 'medium' => [[217, 164, 0], 0.10], 'high' => [[208, 67, 58], 0.10]];

    private const ACTIVITY = [[66, 133, 244], 0.22];

    private const TEXT = [102, 102, 102];

    /**
     * Digits and the colon, five columns by seven rows.
     */
    private const FONT = [
        '0' => ['01110', '10001', '10011', '10101', '11001', '10001', '01110'],
        '1' => ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
        '2' => ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
        '3' => ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
        '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
        '5' => ['11111', '10000', '11110', '00001', '00001', '10001', '01110'],
        '6' => ['00110', '01000', '10000', '11110', '10001', '10001', '01110'],
        '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
        '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
        '9' => ['01110', '10001', '10001', '01111', '00001', '00010', '01100'],
        ':' => ['00000', '01100', '01100', '00000', '01100', '01100', '00000'],
    ];

    /** @var array<int, string> rows of RGB bytes */
    private array $rows = [];

    private float $top = 100;

    /**
     * @param  array<string, mixed>  $day  InsulinDay::forUser
     */
    public function render(array $day): string
    {
        $this->top = max(100.0, ceil($day['peak']['level'] * 1.15));
        $this->rows = array_fill(0, self::HEIGHT, str_repeat("\xFF\xFF\xFF", self::WIDTH));

        $zones = $day['zones'];
        $this->band(0, $zones['medium'], self::ZONE_BANDS['low']);
        $this->band($zones['medium'], $zones['high'], self::ZONE_BANDS['medium']);
        $this->band($zones['high'], $this->top, self::ZONE_BANDS['high']);

        foreach ($day['activities'] as $activity) {
            $left = (int) round($this->x($activity['minute']));
            $right = (int) round($this->x(min(1440, $activity['minute'] + $activity['minutes'])));
            $this->blendRect($left, self::TOP, max($left + 2, $right), self::BOTTOM, ...self::ACTIVITY);
        }

        foreach (range(0, 1440, 180) as $minute) {
            $x = (int) round($this->x($minute));
            $this->blendRect($x, self::TOP, $x + 1, self::BOTTOM, [0, 0, 0], 0.06);
            $label = sprintf('%02d:00', intdiv($minute, 60) % 24);
            $this->text($label, max(2, min(self::WIDTH - 2 - $this->textWidth($label), $x - $this->textWidth($label) / 2)), self::BOTTOM + 14, self::TEXT);
        }

        foreach (array_unique([$zones['medium'], $zones['high'], 100]) as $level) {
            if ($level > $this->top) {
                continue;
            }
            $y = (int) round($this->y($level));
            $this->blendRect(self::LEFT, $y, self::RIGHT, $y + 1, [0, 0, 0], 0.10);
            $label = (string) (int) $level;
            $this->text($label, self::LEFT - 8 - $this->textWidth($label), $y - 7, self::TEXT);
        }

        foreach ($day['meals'] as $meal) {
            $x = (int) round($this->x($meal['minute']));
            for ($y = self::TOP + 22; $y < self::BOTTOM; $y += 9) {
                $this->blendRect($x, $y, $x + 2, min($y + 5, self::BOTTOM), [0, 0, 0], 0.40);
            }
            $label = sprintf('%02d:%02d', intdiv($meal['minute'], 60) % 24, $meal['minute'] % 60);
            $left = max(self::LEFT, min(self::RIGHT - $this->textWidth($label), $x - $this->textWidth($label) / 2));
            $this->text($label, $left, self::TOP + 2, [68, 68, 68]);
        }

        $this->curve($day['points'], $zones, $day['now']['minute'] ?? null);

        if ($day['now'] !== null) {
            $x = (int) round($this->x($day['now']['minute']));
            $this->blendRect($x, self::TOP, $x + 2, self::BOTTOM, [0, 0, 0], 0.55);
            $this->disc($this->x($day['now']['minute']), $this->y($day['now']['level']), 6.5, self::ZONE_COLORS[$this->zone($day['now']['level'], $zones)]);
        }

        return $this->png();
    }

    private function x(float $minute): float
    {
        return self::LEFT + $minute / 1440 * (self::RIGHT - self::LEFT);
    }

    private function y(float $level): float
    {
        return self::BOTTOM - min($level, $this->top) / $this->top * (self::BOTTOM - self::TOP);
    }

    /**
     * @param  array{medium: float, high: float}  $zones
     */
    private function zone(float $level, array $zones): string
    {
        return $level >= $zones['high'] ? 'high' : ($level >= $zones['medium'] ? 'medium' : 'low');
    }

    /**
     * @param  array{0: array{0: int, 1: int, 2: int}, 1: float}  $color
     */
    private function band(float $from, float $to, array $color): void
    {
        $this->blendRect(self::LEFT, (int) round($this->y($to)), self::RIGHT, (int) round($this->y($from)), ...$color);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function blendRect(int $left, int $top, int $right, int $bottom, array $color, float $alpha): void
    {
        $left = max(0, $left);
        $right = min(self::WIDTH, $right);

        for ($y = max(0, $top); $y < min(self::HEIGHT, $bottom); $y++) {
            for ($x = $left; $x < $right; $x++) {
                $this->blend($x, $y, $color, $alpha);
            }
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function blend(int $x, int $y, array $color, float $alpha): void
    {
        $row = &$this->rows[$y];
        $at = $x * 3;

        for ($channel = 0; $channel < 3; $channel++) {
            $under = ord($row[$at + $channel]);
            $row[$at + $channel] = chr((int) round($under + ($color[$channel] - $under) * $alpha));
        }
    }

    /**
     * The line of the day, thick and anti-aliased; dashed after now.
     *
     * @param  array<int, array{minute: int, level: float}>  $points
     * @param  array{medium: float, high: float}  $zones
     */
    private function curve(array $points, array $zones, ?int $now): void
    {
        $coverage = [];
        $colors = [];
        $nowX = $now === null ? null : $this->x($now);
        $reach = self::LINE_HALF_WIDTH + 1;

        for ($i = 1, $count = count($points); $i < $count; $i++) {
            [$ax, $ay] = [$this->x($points[$i - 1]['minute']), $this->y($points[$i - 1]['level'])];
            [$bx, $by] = [$this->x($points[$i]['minute']), $this->y($points[$i]['level'])];
            $color = self::ZONE_COLORS[$this->zone($points[$i]['level'], $zones)];
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $length = $dx * $dx + $dy * $dy;

            for ($y = (int) floor(min($ay, $by) - $reach); $y <= (int) ceil(max($ay, $by) + $reach); $y++) {
                for ($x = (int) floor(min($ax, $bx) - $reach); $x <= (int) ceil(max($ax, $bx) + $reach); $x++) {
                    $px = $x + 0.5;
                    if ($nowX !== null && $px > $nowX && fmod($px - $nowX, 16) >= 10) {
                        continue;
                    }

                    $t = $length > 0 ? max(0, min(1, (($px - $ax) * $dx + ($y + 0.5 - $ay) * $dy) / $length)) : 0;
                    $distance = hypot($px - ($ax + $t * $dx), $y + 0.5 - ($ay + $t * $dy));
                    $cover = max(0.0, min(1.0, self::LINE_HALF_WIDTH + 0.5 - $distance));
                    $key = $y * self::WIDTH + $x;

                    if ($cover > 0 && $cover > ($coverage[$key] ?? 0)) {
                        $coverage[$key] = $cover;
                        $colors[$key] = $color;
                    }
                }
            }
        }

        foreach ($coverage as $key => $cover) {
            $y = intdiv($key, self::WIDTH);
            if ($y >= 0 && $y < self::HEIGHT) {
                $this->blend($key % self::WIDTH, $y, $colors[$key], $cover);
            }
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function disc(float $cx, float $cy, float $radius, array $color): void
    {
        for ($y = (int) floor($cy - $radius - 1); $y <= (int) ceil($cy + $radius + 1); $y++) {
            for ($x = (int) floor($cx - $radius - 1); $x <= (int) ceil($cx + $radius + 1); $x++) {
                $cover = max(0.0, min(1.0, $radius + 0.5 - hypot($x + 0.5 - $cx, $y + 0.5 - $cy)));
                if ($cover > 0 && $x >= 0 && $x < self::WIDTH && $y >= 0 && $y < self::HEIGHT) {
                    $this->blend($x, $y, $color, $cover);
                }
            }
        }
    }

    private function textWidth(string $text): int
    {
        return strlen($text) * 12 - 2;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function text(string $text, float $left, int $top, array $color): void
    {
        $x = (int) round($left);

        foreach (str_split($text) as $char) {
            foreach (self::FONT[$char] ?? [] as $row => $bits) {
                foreach (str_split($bits) as $column => $bit) {
                    if ($bit === '1') {
                        $this->blendRect($x + $column * 2, $top + $row * 2, $x + $column * 2 + 2, $top + $row * 2 + 2, $color, 1.0);
                    }
                }
            }
            $x += 12;
        }
    }

    private function png(): string
    {
        $raw = '';
        foreach ($this->rows as $row) {
            $raw .= "\x00".$row;
        }

        $chunk = static fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', self::WIDTH, self::HEIGHT, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress($raw, 6))
            .$chunk('IEND', '');
    }
}
