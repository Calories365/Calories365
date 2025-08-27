<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Jobs\GenerateDietFeedbackJob;
use App\Models\DietFeedback;
use App\Models\FoodConsumption;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function index(DateValidationRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isPremium()) {
            return response()->json(['success' => false, 'message' => 'buy_premium_for_this_func'], 402);
        }

        $date = $request->validated()['date'];
        $partOfDay = $request->input('part_of_day');

        $meals = FoodConsumption::getMealsWithCurrentDate($date, $user->id, app()->getLocale(), $partOfDay);

        if (($meals instanceof \Illuminate\Support\Collection && $meals->isEmpty()) || (is_array($meals) && count($meals) === 0)) {
            return response()->json([
                'success' => false,
                'message' => 'no_products_consumed',
            ], 200);
        }

        $sig = DietFeedback::buildMealsSignature($meals);

        if ($existing = DietFeedback::findBySignature($user->id, $date, $partOfDay, $sig)) {
            if ($existing->status === 'ready' && $existing->feedback_text) {
                return response()->json([
                    'success' => true,
                    'message' => 'Feedback retrieved from cache',
                    'data' => $existing->feedback_text,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'accepted',
                'rid' => $sig,
                'poll_after_ms' => 1200,
            ], 202);
        }

        DietFeedback::insertPendingIfMissing($user->id, $date, $partOfDay, $sig);

        Log::info('start job');
        GenerateDietFeedbackJob::dispatch(
            userId: $user->id,
            date: $date,
            partOfDay: $partOfDay,
            locale: app()->getLocale(),
            signature: $sig
        )->onQueue('feedback');

        return response()->json([
            'success' => false,
            'message' => 'accepted',
            'rid' => $sig,
            'poll_after_ms' => 1200,
        ], 202);
    }

    public function status(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();
        $rid = (string) $request->query('rid');

        $rec = DietFeedback::findLatestByUserAndSignature($user->id, $rid);

        if (! $rec || $rec->status !== 'ready' || ! $rec->feedback_text) {
            return response()->json(['ready' => false], 200);
        }

        return response()->json(['ready' => true, 'data' => $rec->feedback_text], 200);
    }
}
