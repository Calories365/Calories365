<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\FoodConsumption;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class DairyController extends Controller
{
    public function getPopularProducts(Request $request): ProductCollection
    {
        $locale = $request->get('locale', app()->getLocale());

        $cacheKey = 'popular_products_' . $locale;

        $products = Cache::remember($cacheKey, now()->addMinutes(1440), function () use ($locale) {
            return Product::select(
                'products.calories', 'products.proteins', 'products.carbohydrates', 'products.fats',
                'products.fibers', 'product_translations.name', 'products.id')
                ->join('product_translations', 'products.id', '=', 'product_translations.product_id')
                ->where('products.is_popular', true)
                ->where('product_translations.locale', $locale)
                ->get();
        });

        return new ProductCollection($products);
    }

    public function saveMeal(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('Request data received in store method:', $request->all());

        $validator = Validator::make($request->all(), [
            'food_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'consumed_at' => 'required|date_format:Y-m-d',
            'part_of_day' => 'required|in:morning,dinner,supper'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $foodConsumption = new FoodConsumption;
            $foodConsumption->user_id = auth()->id();
            $foodConsumption->food_id = $request->food_id;
            $foodConsumption->quantity = $request->quantity;
            $foodConsumption->consumed_at = $request->consumed_at;
            $foodConsumption->day_part = $request->part_of_day;
            $foodConsumption->save();

            $Id = $foodConsumption->id;
            return response()->json(['id' => $Id]);
        } catch (\Exception $e) {
            Log::error('Error', [$e]);

            return response()->json(['error' => 'Failed to save meal.'], 500);
        }
    }

    public function getMeal(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $locale = app()->getLocale();
        $userId = auth()->id();

        $meals = DB::table('food_consumptions')
            ->join('products', 'food_consumptions.food_id', '=', 'products.id')
            ->join('product_translations', function ($join) use ($locale) {
                $join->on('products.id', '=', 'product_translations.product_id')
                    ->where('product_translations.locale', '=', $locale);
            })
            ->where('food_consumptions.user_id', $userId)
            ->whereDate('food_consumptions.consumed_at', $request->date)
            ->select(
                'food_consumptions.*',
                'products.calories',
                'products.proteins',
                'products.carbohydrates',
                'products.fats',
                'products.fibers',
                'product_translations.name as name'
            )
            ->get();


        // Форматирование и отправка ответа
        return response()->json(['products' => $meals]);
    }

    public function deleteMeal($id): \Illuminate\Http\JsonResponse
    {
        Log::info('Request data received in store method:', ['data' => $id]);

        try {
            $meal = FoodConsumption::findOrFail($id);

            // Проверка на то, что удаляемый прием пищи принадлежит текущему пользователю
            if ($meal->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $meal->delete();

            return response()->json(['message' => 'Meal deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Meal deletion failed: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to delete meal'], 500);
        }
    }

    public function updateMeal(Request $request): \Illuminate\Http\JsonResponse
    {
        // Валидация входящих данных
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:food_consumptions,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Найти запись и обновить ее
            $foodConsumption = FoodConsumption::findOrFail($request->id);

            // Проверка, что запись принадлежит текущему пользователю
            if ($foodConsumption->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $foodConsumption->quantity = $request->quantity;
            $foodConsumption->save();

            return response()->json(['message' => 'Meal updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update meal'], 500);
        }
    }

    public function getSearchedMeal(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('query'); // Получение поисковой строки из параметров запроса
        $locale = app()->getLocale();

        Log::info('Request data received in getSearchedMeal:', $request->all());


        try {
            $products = DB::table('products')
                ->join('product_translations', 'products.id', '=', 'product_translations.product_id')
                ->where('product_translations.locale', '=', $locale)
                ->where('product_translations.name', 'LIKE', '%' . $query . '%')
                ->select(
                    'products.id',
                    'products.calories',
                    'products.proteins',
                    'products.carbohydrates',
                    'products.fats',
                    'products.fibers',
                    'product_translations.name as name'
                )
                ->paginate(10); // Пагинация для 10 записей на страницу

            return response()->json(['products' => $products]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search for meals'], 500);
        }
    }

}
