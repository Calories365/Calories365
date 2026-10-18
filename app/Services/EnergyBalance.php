<?php

namespace App\Services;

use App\Models\BodyWeight;
use App\Models\UserResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The user's real daily expenditure from the diary: what was eaten on average
 * minus what the weight trend says was stored or used, 7700 kcal per kg. The
 * calculator's formulas guess it from age, height and weight; this measures it,
 * once the diary and the scales have enough days.
 *
 * The weight trend is the least-squares slope of the weigh-ins of the window,
 * so a single heavy morning moves it little. A day with less than MIN_DAY_INTAKE
 * eaten counts as not logged: half-logged days would lower the average.
 */
class EnergyBalance
{
    public const WINDOW_DAYS = 28;

    public const MIN_LOGGED_DAYS = 10;

    public const MIN_WEIGHTS = 3;

    public const MIN_WEIGHT_SPAN_DAYS = 10;

    public const MIN_DAY_INTAKE = 800;

    private const KCAL_PER_KG = 7700;

    /**
     * The share of the expenditure the norm eats for each goal, as the
     * calculator counts them: 15 % less to lose, 15 % more to gain.
     */
    private const GOAL_SHARE = ['lose' => 0.85, 'stay' => 1.0, 'gain' => 1.15];

    /**
     * ready, with the expenditure, the average intake, the weight change per
     * week and the norm for the calculator's goal; collecting, with what the
     * diary has and what it needs; or unclear, when the intake and the weight
     * give an expenditure no body has.
     *
     * @return array<string, mixed>
     */
    public function estimate(int $userId, ?Carbon $today = null): array
    {
        $to = ($today ?? today())->copy()->startOfDay();
        $from = $to->copy()->subDays(self::WINDOW_DAYS - 1);

        $intakes = DB::table('food_consumptions')
            ->join('products', 'products.id', '=', 'food_consumptions.food_id')
            ->where('food_consumptions.user_id', $userId)
            ->whereBetween('food_consumptions.consumed_at', [$from->toDateString(), $to->copy()->endOfDay()->toDateTimeString()])
            ->groupByRaw('date(food_consumptions.consumed_at)')
            ->selectRaw('sum(food_consumptions.quantity * products.calories / 100) as calories')
            ->pluck('calories')
            ->map(static fn ($calories) => (float) $calories)
            ->filter(static fn (float $calories) => $calories >= self::MIN_DAY_INTAKE)
            ->values();

        $weights = BodyWeight::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get(['date', 'weight']);

        $span = $weights->count() > 1 ? (int) $weights->first()->date->diffInDays($weights->last()->date) : 0;

        $state = [
            'logged_days' => $intakes->count(),
            'weights' => $weights->count(),
            'needed' => ['logged_days' => self::MIN_LOGGED_DAYS, 'weights' => self::MIN_WEIGHTS, 'weight_span_days' => self::MIN_WEIGHT_SPAN_DAYS],
        ];

        if ($intakes->count() < self::MIN_LOGGED_DAYS || $weights->count() < self::MIN_WEIGHTS || $span < self::MIN_WEIGHT_SPAN_DAYS) {
            return ['status' => 'collecting'] + $state;
        }

        $slope = $this->slope($weights->map(static fn (BodyWeight $weight) => [(float) $from->diffInDays($weight->date), $weight->weight])->all());
        $intake = $intakes->avg();
        $expenditure = (int) round($intake - $slope * self::KCAL_PER_KG);

        // Meals left out of the diary or a scale that was moved make the measure untrue.
        if ($expenditure < 1000 || $expenditure > 6000) {
            return ['status' => 'unclear', 'intake' => (int) round($intake), 'weight_change_per_week' => round($slope * 7, 2)] + $state;
        }

        // The goal weight of the calculator against the weight now, as the
        // calculator decides the goal.
        $goalWeight = UserResult::query()->where('user_id', $userId)->value('goalWeight');
        $current = (float) $weights->last()->weight;
        $goal = match (true) {
            $goalWeight === null => 'stay',
            $current > $goalWeight + 0.5 => 'lose',
            $current < $goalWeight - 0.5 => 'gain',
            default => 'stay',
        };

        return [
            'status' => 'ready',
            'expenditure' => $expenditure,
            'intake' => (int) round($intake),
            'weight_change_per_week' => round($slope * 7, 2),
            'goal' => $goal,
            'norm' => (int) round($expenditure * self::GOAL_SHARE[$goal] / 10) * 10,
        ] + $state;
    }

    /**
     * The least-squares slope of the points, in kg per day.
     *
     * @param  array<int, array{0: float, 1: float}>  $points  day of the window, weight
     */
    private function slope(array $points): float
    {
        $count = count($points);
        $meanX = array_sum(array_column($points, 0)) / $count;
        $meanY = array_sum(array_column($points, 1)) / $count;

        $covariance = 0.0;
        $variance = 0.0;
        foreach ($points as [$x, $y]) {
            $covariance += ($x - $meanX) * ($y - $meanY);
            $variance += ($x - $meanX) ** 2;
        }

        return $variance > 0 ? $covariance / $variance : 0.0;
    }
}
