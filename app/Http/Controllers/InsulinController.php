<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealTimeRequest;
use App\Models\FoodConsumption;
use App\Services\InsulinChartImage;
use App\Services\InsulinDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The insulin curve of a day. Every answer is the day as InsulinDay gives it.
 */
class InsulinController extends Controller
{
    public function __construct(private InsulinDay $insulin) {}

    /**
     * The day; with chart=1 also its picture as a base64 PNG, which the bot sends.
     */
    public function show(Request $request, string $date): JsonResponse
    {
        $day = $this->insulin->forUser($request->user(), $date, app()->getLocale());

        if ($request->boolean('chart')) {
            $day['chart'] = base64_encode(app(InsulinChartImage::class)->render($day));
        }

        return response()->json($day);
    }

    /**
     * The way of life, one of the calculator's five levels.
     */
    public function updateLifestyle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lifestyle' => ['required', 'integer', 'between:1,5'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $request->user()->forceFill(['lifestyle' => $data['lifestyle']])->save();

        return response()->json($this->insulin->forUser($request->user()->fresh(), $data['date'], app()->getLocale()));
    }

    /**
     * When a meal of the day was eaten: all its foods move together.
     */
    public function updateMealTime(StoreMealTimeRequest $request): JsonResponse
    {
        $data = $request->validated();

        FoodConsumption::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('consumed_at', $data['date'])
            ->where('part_of_day', $data['part_of_day'])
            ->update(['eaten_at' => Carbon::parse($data['date'].' '.$data['time'])]);

        return response()->json($this->insulin->forUser($request->user(), $data['date'], app()->getLocale()));
    }
}
