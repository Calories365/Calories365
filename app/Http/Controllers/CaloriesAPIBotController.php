<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Http\Resources\MealCollection;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class CaloriesAPIBotController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Returns only the products with _rankingScore ≥ 0.9.
     * If none pass the filter — products = [] and message = "Products not found".
     */
    public function storeFiltered(Request $request): JsonResponse
    {
        $text = $request->input('text', '');

        $response = [
            'message' => 'Products not found',
            'products' => [],
        ];

        if (trim($text) === '' || trim($text) === 'No products') {
            return response()->json($response);
        }

        $locale = app()->getLocale();
        $suffixMap = ['en' => 'grams', 'uk' => 'грам', 'ru' => 'грамм'];
        $suffix = $suffixMap[$locale] ?? 'грамм';
        $delimiter = str_contains($text, ';') ? ';' : $suffix;
        $rawItems = array_filter(array_map('trim', explode($delimiter, $text)));

        $productsInfo = [];
        $userId = auth()->id() ?? null;

        foreach ($rawItems as $raw) {
            $parts = array_map('trim', explode(' - ', $raw));

            if (count($parts) !== 2 || ! preg_match('/(\d+(\.\d+)?)/', $parts[1], $m)) {
                $productsInfo[] = [
                    'product_translation' => null,
                    'product' => null,
                    'said_name' => $raw,
                    'quantity_grams' => null,
                    'found' => false,
                ];

                continue;
            }

            [$productName, $quantityStr] = $parts;
            $quantity = (float) $m[1];

            if ($quantity <= 0) {
                $productsInfo[] = [
                    'product_translation' => null,
                    'product' => null,
                    'said_name' => $productName,
                    'quantity_grams' => $quantity,
                    'found' => false,
                ];

                continue;
            }

            $candidate = Product::getRawProduct($productName, $userId, $locale);

            if (! $candidate || (($candidate['_rankingScore'] ?? 0) < 0.6)) {
                $productsInfo[] = [
                    'product_translation' => null,
                    'product' => null,
                    'said_name' => $productName,
                    'quantity_grams' => $quantity,
                    'found' => false,
                ];

                continue;
            }

            $productModel = Product::find($candidate['product_id']);
            if (! $productModel || $productModel->calories === null) {
                $productsInfo[] = [
                    'product_translation' => null,
                    'product' => null,
                    'said_name' => $productName,
                    'quantity_grams' => $quantity,
                    'found' => false,
                ];

                continue;
            }

            $productsInfo[] = [
                'product_translation' => [
                    'id' => $candidate['id'],
                    'product_id' => $candidate['product_id'],
                    'locale' => $candidate['locale'],
                    'name' => $candidate['name'],
                    'said_name' => $productName,
                    'original_name' => $productName,
                ],
                'product' => [
                    'id' => $productModel->id,
                    'user_id' => $productModel->user_id,
                    'calories' => $productModel->calories,
                    'proteins' => $productModel->proteins,
                    'carbohydrates' => $productModel->carbohydrates,
                    'fats' => $productModel->fats,
                    'fibers' => $productModel->fibers ?? 0,
                    'quantity_grams' => $quantity,
                ],
                'quantity_grams' => $quantity,
                'found' => true,
            ];
        }

        if ($productsInfo) {
            $response = [
                'message' => 'Products found',
                'products' => $productsInfo,
            ];
        }

        return response()->json($response);
    }

    public function getTheMostRelevantProduct(Request $request): bool|JsonResponse
    {
        $text = $request->input('text');
        $parts = explode(' - ', $text);

        if (count($parts) > 1) {
            $productName = trim($parts[0]);
            $quantityStr = trim($parts[1]);
        }
        if (preg_match('/(\d+(\.\d+)?)/', $quantityStr, $matches)) {
            $quantity = floatval($matches[1]);
        }

        $user_id = auth()->id();
        $locale = app()->getLocale();

        $productTranslation = Product::getRawProduct($productName, $user_id, $locale);
        $product = Product::where('id', $productTranslation['product_id'])->first();

        if ($productTranslation['_rankingScore'] < 0.9) {
            return false;
        }

        if ($productTranslation) {
            $productInfo = [
                'product_translation' => [
                    'id' => $productTranslation['id'],
                    'product_id' => $productTranslation['product_id'],
                    'locale' => $productTranslation['locale'],
                    'name' => $productTranslation['name'],
                    'said_name' => $productName,
                    'original_name' => $productName,
                ],
                'product' => [
                    'id' => $product->id,
                    'user_id' => $product->user_id,
                    'calories' => $product->calories,
                    'proteins' => $product->proteins,
                    'carbohydrates' => $product->carbohydrates,
                    'fats' => $product->fats,
                    'fibers' => $product->fibers,
                    'quantity_grams' => $quantity,
                ],
                'quantity_grams' => $quantity,
            ];

            return response()->json([
                'message' => 'Product found',
                'product' => $productInfo,
            ]);
        } else {
            return false;
        }
    }

    public function saveProduct(StoreUsersFoodConsumptionsRequest $request, ProductService $productService): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $userResult = $productService->createProductWithTranslationsAndConsumption($validatedData);

        return response()->json($userResult, 201);
    }

    public function saveFoodConsumption(StoreFoodConsumptionRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);

        return response()->json(['id' => $foodConsumption->id]);
    }

    public function showUserStats(DateValidationRequest $request): MealCollection
    {
        $userId = auth()->id();
        $date = $request->route('date');
        $partOfDay = $request->route('partOfDay');
        $locale = app()->getLocale();
        $meals = FoodConsumption::getMealsWithCurrentDate($date, $userId, $locale, $partOfDay);

        return new MealCollection($meals);
    }

    public function destroy(FoodConsumption $meal): \Illuminate\Http\JsonResponse
    {
        $meal->delete();

        return response()->json(['message' => 'Success']);
    }
}
