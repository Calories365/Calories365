<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Http\Resources\MealCollection;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SearchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CaloriesAPIBotController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function store(Request $request)
    {


        $text = $request->input('text');
        Log::info("Received text: " . $text);
        $response = [
            'message' => 'No products mentioned',
            'products' => []
        ];

        if (trim($text) !== 'Продуктов нет' && !empty(trim($text))) {
            $productsInfo = [];

            // изначально должно быть по ; но чат напрочь отказывается ставить этот символ
            if (str_contains($text, ';')) {
                $products = explode(';', $text);
            } else {
                $products = explode('грамм', $text);
            }
            Log::info('products from bot: ');
            Log::info(print_r($products, true));
            foreach ($products as $product) {
                $product = trim($product);

                if (!empty($product)) {
                    $parts = explode(' - ', $product);

                    if (count($parts) > 1) {
                        $productName = trim($parts[0]);
                        $quantityStr = trim($parts[1]);

                        if (preg_match('/(\d+(\.\d+)?)/', $quantityStr, $matches)) {
                            $quantity = floatval($matches[1]);

                            if ($quantity > 0) {
                                $searchedProducts = Product::getSearchedProductsViaMeili($productName, false, 1);

                                $searchedProducts->load('product');

                                if ($searchedProducts->isNotEmpty()) {
                                    $productTranslation = $searchedProducts->first();
                                    $productModel = $productTranslation->product;

                                    if ($productModel && $productModel->calories !== null) {
                                        $calories = $productModel->calories;
                                        $proteins = $productModel->proteins;
                                        $carbohydrates = $productModel->carbohydrates;
                                        $fats = $productModel->fats;
                                        $fibers = ($productModel->fibers ?? 0);

                                        $productsInfo[] = [
                                            'product_translation' => [
                                                'id' => $productTranslation->id,
                                                'product_id' => $productTranslation->product_id,
                                                'locale' => $productTranslation->locale,
                                                'name' => $productTranslation->name,
                                                'user_id' => $productTranslation->user_id,
//                                                'created_at' => $productTranslation->created_at,
//                                                'updated_at' => $productTranslation->updated_at,
                                                'said_name' => $productName,
                                                'original_name' => $productName,
                                            ],
                                            'product' => [
                                                'id' => $productModel->id,
                                                'user_id' => $productModel->user_id,
                                                'calories' => $calories,
                                                'proteins' => $proteins,
                                                'carbohydrates' => $carbohydrates,
                                                'fats' => $fats,
                                                'fibers' => $fibers,
                                                'quantity_grams' => $quantity,
//                                                'is_popular' => $productModel->is_popular,
//                                                'created_at' => $productModel->created_at,
//                                                'updated_at' => $productModel->updated_at,
                                            ],
                                            'quantity_grams' => $quantity
                                        ];
                                        Log::info('for ' . $productName);
                                        Log::info('found product: ' . $productTranslation->name);
                                    } else {
                                        Log::warning("Нутриентные данные отсутствуют для продукта: {$productName}");
                                    }
                                } else {
                                    Log::warning("Продукт не найден: {$productName}");
                                }
                            } else {
                                Log::warning("Некорректное количество для продукта: {$productName}");
                            }
                        } else {
                            Log::warning("Не удалось извлечь количество из строки: {$quantityStr}");
                        }
                    } else {
                        Log::warning("Некорректный формат продукта: {$product}");
                    }
                }
            }

            if (!empty($productsInfo)) {
                $response = [
                    'message' => 'Products found',
                    'products' => $productsInfo
                ];
            }
        }

//        Log::info('products: ');
//        Log::info(print_r($productsInfo, true));
        return response()->json($response);
    }

    public function saveProduct(StoreUsersFoodConsumptionsRequest $request, ProductService $productService ): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
        $userResult = $productService->createProductWithTranslationsAndConsumption($validatedData);
        return response()->json($userResult, 201);
    }

    public function saveFoodConsumption(StoreFoodConsumptionRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();
//        Log::info(print_r($validatedData, true));
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);
        return response()->json(['id' => $foodConsumption->id]);
    }

    public function showUserStats(DateValidationRequest $request){
        $id = Auth::id();
        Log::info("Received text from: " . $id);
        $date = $request->route('date');
        $partOfDay = $request->route('partOfDay');
        Log::info($date);
        $locale = app()->getLocale();
        Log::info('locale: ');
        Log::info($locale);
        $locale = 'ru';
        $userId = auth()->id();
        $meals = FoodConsumption::getMealsWithCurrentDate($date, $userId, $locale, $partOfDay);

        return new MealCollection($meals);
    }

    public function getTheMostRelevantProduct(Request $request)
    {
        Log::info('getTheMostRelevantProduct');
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

        $productTranslation = Product::getRawProduct($productName, $user_id, 'ru');
        $product = Product::where('id', $productTranslation['product_id'])->first();



        if($productTranslation){
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
                'quantity_grams' => $quantity
            ];

            Log::info(print_r($productInfo, true));
            return response()->json([
                'message' => 'Product found',
                'product' => $productInfo
            ]);
        } else {
            return response()->json([
                'message' => 'Product not found',
                'product' => false
            ]);
        }
    }

    public function destroy(FoodConsumption $meal): \Illuminate\Http\JsonResponse
    {
        $meal->delete();
        return response()->json(['message' => 'Success']);
    }

}
