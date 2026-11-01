<?php

namespace App\Services;

/**
 * The sugar and insulin of a day as a PNG, for the bot: the same picture as the
 * page. Above, blood sugar in mmol/L, the line colored by its zone (normal, above
 * 7.8, above 10); below, insulin, colored by its zones; both dashed after now. A
 * meal is a dashed mark with its time, movement a blue band, sleep a grey one.
 *
 * Drawn in PHP and packed with zlib, since the images of the diary have no GD:
 * lines are anti-aliased by their distance to each pixel, and the only text, the
 * times and the levels, is a bitmap font of digits. Words go into the caption.
 */
class InsulinChartImage
{
    private const WIDTH = 1000;

    private const HEIGHT = 760;

    private const LEFT = 64;

    private const RIGHT = 978;

    /**
     * The two panels: top and bottom in pixels.
     */
    private const GLUCOSE_PANEL = [34, 440];

    private const INSULIN_PANEL = [476, 716];

    private const LINE_HALF_WIDTH = 2.6;

    private const ZONE_COLORS = ['low' => [79, 157, 85], 'medium' => [217, 164, 0], 'high' => [208, 67, 58]];

    private const ZONE_ALPHAS = ['low' => 0.08, 'medium' => 0.10, 'high' => 0.10];

    private const ACTIVITY = [[66, 133, 244], 0.22];

    private const SLEEP = [[110, 110, 140], 0.16];

    private const TEXT = [102, 102, 102];

    /**
     * Digits, the colon and the decimal marks, five columns by seven rows.
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
        '.' => ['00000', '00000', '00000', '00000', '00000', '01100', '01100'],
        ',' => ['00000', '00000', '00000', '00000', '01100', '00100', '01000'],
    ];

    /** @var array<int, string> rows of RGB bytes */
    private array $rows = [];

    /**
     * The panel being drawn: pixel top and bottom, the values at them.
     *
     * @var array{top: int, bottom: int, from: float, to: float}
     */
    private array $panel = ['top' => 0, 'bottom' => 0, 'from' => 0.0, 'to' => 1.0];

    /**
     * @param  array<string, mixed>  $day  InsulinDay::forUser
     */
    public function render(array $day, string $decimalMark = ','): string
    {
        $this->rows = array_fill(0, self::HEIGHT, str_repeat("\xFF\xFF\xFF", self::WIDTH));
        $now = $day['now']['minute'] ?? null;
        $glucose = $day['glucose'];

        // Sugar.
        $top = max($glucose['very_high'] - 0.5, ceil(($glucose['peak']['level'] + 0.6) * 2) / 2);
        $this->panel(self::GLUCOSE_PANEL, 3.5, $top);
        $glucoseZones = ['medium' => $glucose['high'], 'high' => $glucose['very_high']];
        $this->zones($glucoseZones);
        $this->marks($day);
        foreach ([$glucose['base'], $glucose['high'], $glucose['very_high']] as $level) {
            $this->level($level, str_replace('.', $decimalMark, (string) $level));
        }
        $this->meals($day['meals']);
        $this->curve(array_map(static fn (array $point) => ['minute' => $point['minute'], 'value' => $point['glucose']], $day['points']), $glucoseZones, $now);
        if ($day['now'] !== null) {
            $this->now($now, $day['now']['glucose'], $glucoseZones);
        }

        // Insulin.
        $this->panel(self::INSULIN_PANEL, 0, max(100.0, ceil($day['peak']['level'] * 1.15)));
        $this->zones($day['zones']);
        $this->marks($day);
        foreach (array_unique([$day['zones']['medium'], $day['zones']['high'], 100]) as $level) {
            $this->level($level, (string) (int) $level);
        }
        $this->curve(array_map(static fn (array $point) => ['minute' => $point['minute'], 'value' => $point['level']], $day['points']), $day['zones'], $now);
        if ($day['now'] !== null) {
            $this->now($now, $day['now']['level'], $day['zones']);
        }

        foreach (range(0, 1440, 180) as $minute) {
            $x = (int) round($this->x($minute));
            $label = sprintf('%02d:00', intdiv($minute, 60) % 24);
            $this->text($label, max(2, min(self::WIDTH - 2 - $this->textWidth($label), $x - $this->textWidth($label) / 2)), self::INSULIN_PANEL[1] + 14, self::TEXT);
        }

        return $this->png();
    }

    /**
     * @param  array{0: int, 1: int}  $pixels
     */
    private function panel(array $pixels, float $from, float $to): void
    {
        $this->panel = ['top' => $pixels[0], 'bottom' => $pixels[1], 'from' => $from, 'to' => $to];
    }

    /**
     * @param  array{medium: float, high: float}  $zones
     */
    private function zones(array $zones): void
    {
        $this->band($this->panel['from'], $zones['medium'], self::ZONE_COLORS['low'], self::ZONE_ALPHAS['low']);
        $this->band($zones['medium'], $zones['high'], self::ZONE_COLORS['medium'], self::ZONE_ALPHAS['medium']);
        $this->band($zones['high'], $this->panel['to'], self::ZONE_COLORS['high'], self::ZONE_ALPHAS['high']);
    }

    /**
     * The bands of movement and sleep and the grid of hours.
     *
     * @param  array<string, mixed>  $day
     */
    private function marks(array $day): void
    {
        foreach ($day['activities'] as $activity) {
            $left = (int) round($this->x(max(0, $activity['minute'])));
            $right = (int) round($this->x(min(1440, $activity['minute'] + $activity['minutes'])));
            [$color, $alpha] = ($activity['sleep'] ?? false) ? self::SLEEP : self::ACTIVITY;
            $this->blendRect($left, $this->panel['top'], max($left + 2, $right), $this->panel['bottom'], $color, $alpha);
        }

        foreach (range(0, 1440, 180) as $minute) {
            $x = (int) round($this->x($minute));
            $this->blendRect($x, $this->panel['top'], $x + 1, $this->panel['bottom'], [0, 0, 0], 0.06);
        }
    }

    private function level(float $level, string $label): void
    {
        if ($level > $this->panel['to'] || $level < $this->panel['from']) {
            return;
        }

        $y = (int) round($this->y($level));
        $this->blendRect(self::LEFT, $y, self::RIGHT, $y + 1, [0, 0, 0], 0.10);
        $this->text($label, self::LEFT - 8 - $this->textWidth($label), $y - 7, self::TEXT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $meals
     */
    private function meals(array $meals): void
    {
        foreach ($meals as $meal) {
            $x = (int) round($this->x($meal['minute']));
            for ($y = $this->panel['top'] + 22; $y < self::INSULIN_PANEL[1]; $y += 9) {
                $this->blendRect($x, $y, $x + 2, min($y + 5, self::INSULIN_PANEL[1]), [0, 0, 0], 0.40);
            }
            $label = sprintf('%02d:%02d', intdiv($meal['minute'], 60) % 24, $meal['minute'] % 60);
            $left = max(self::LEFT, min(self::RIGHT - $this->textWidth($label), $x - $this->textWidth($label) / 2));
            $this->text($label, $left, $this->panel['top'] + 2, [68, 68, 68]);
        }
    }

    /**
     * @param  array{medium: float, high: float}  $zones
     */
    private function now(int $minute, float $value, array $zones): void
    {
        $x = (int) round($this->x($minute));
        $this->blendRect($x, $this->panel['top'], $x + 2, $this->panel['bottom'], [0, 0, 0], 0.55);
        $this->disc($this->x($minute), $this->y($value), 6.5, self::ZONE_COLORS[$this->zone($value, $zones)]);
    }

    private function x(float $minute): float
    {
        return self::LEFT + $minute / 1440 * (self::RIGHT - self::LEFT);
    }

    private function y(float $value): float
    {
        $share = (min(max($value, $this->panel['from']), $this->panel['to']) - $this->panel['from']) / ($this->panel['to'] - $this->panel['from']);

        return $this->panel['bottom'] - $share * ($this->panel['bottom'] - $this->panel['top']);
    }

    /**
     * @param  array{medium: float, high: float}  $zones
     */
    private function zone(float $level, array $zones): string
    {
        return $level >= $zones['high'] ? 'high' : ($level >= $zones['medium'] ? 'medium' : 'low');
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function band(float $from, float $to, array $color, float $alpha): void
    {
        if ($to <= $from) {
            return;
        }

        $this->blendRect(self::LEFT, (int) round($this->y($to)), self::RIGHT, (int) round($this->y($from)), $color, $alpha);
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
     * @param  array<int, array{minute: int, value: float}>  $points
     * @param  array{medium: float, high: float}  $zones
     */
    private function curve(array $points, array $zones, ?int $now): void
    {
        $coverage = [];
        $colors = [];
        $nowX = $now === null ? null : $this->x($now);
        $reach = self::LINE_HALF_WIDTH + 1;

        for ($i = 1, $count = count($points); $i < $count; $i++) {
            [$ax, $ay] = [$this->x($points[$i - 1]['minute']), $this->y($points[$i - 1]['value'])];
            [$bx, $by] = [$this->x($points[$i]['minute']), $this->y($points[$i]['value'])];
            $color = self::ZONE_COLORS[$this->zone($points[$i]['value'], $zones)];
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
