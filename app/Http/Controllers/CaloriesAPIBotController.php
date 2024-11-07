<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
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
        // Получаем текст из запроса
        $text = $request->input('text');
        Log::info("Received text: " . $text);
        // Инициализируем ответ с сообщением по умолчанию
        $response = [
            'message' => 'No products mentioned',
            'products' => []
        ];

        // Проверяем, что текст не равен 'Продуктов нет' и не пустой
        if (trim($text) !== 'Продуктов нет' && !empty(trim($text))) {
            $productsInfo = [];

            // Разделяем текст по символу ';' для получения отдельных продуктов
            $products = explode(';', $text);

            foreach ($products as $product) {
                $product = trim($product); // Убираем пробелы по краям

                if (!empty($product)) {
                    // Разделяем продукт по ' - ', предполагая формат "Название - Количество грамм"
                    $parts = explode(' - ', $product);

                    if (count($parts) > 1) {
                        $productName = trim($parts[0]); // Получаем название продукта
                        $quantityStr = trim($parts[1]); // Получаем строку с количеством

                        // Извлекаем число из строки с количеством
                        // Используем регулярное выражение для поиска числовых значений
                        if (preg_match('/(\d+(\.\d+)?)/', $quantityStr, $matches)) {
                            $quantity = floatval($matches[1]); // Количество в граммах

                            if ($quantity > 0) {
                                // Ищем продукт с помощью метода getSearchedProductsViaMeili без пагинации, ограничивая 1 результатом
                                $searchedProducts = Product::getSearchedProductsViaMeili($productName, false, 1);

                                // Загружаем связанную модель 'product' для каждого найденного продукта
                                $searchedProducts->load('product');

                                if ($searchedProducts->isNotEmpty()) {
                                    // Получаем первый найденный продукт
                                    $productTranslation = $searchedProducts->first();
                                    $productModel = $productTranslation->product;

                                    // Проверяем, что продукт имеет необходимые нутриентные данные
                                    if ($productModel && $productModel->calories !== null) {
                                        Log::info('current: ' . $product);
                                        $calories = $productModel->calories;
                                        $proteins = $productModel->proteins;
                                        $carbohydrates = $productModel->carbohydrates;
                                        $fats = $productModel->fats;
                                        $fibers = ($productModel->fibers ?? 0);

                                        // Формируем данные для ответа
                                        $productsInfo[] = [
                                            'product_translation' => [
                                                'id' => $productTranslation->id,
                                                'product_id' => $productTranslation->product_id,
                                                'locale' => $productTranslation->locale,
                                                'name' => $productTranslation->name,
                                                'user_id' => $productTranslation->user_id,
                                                'created_at' => $productTranslation->created_at,
                                                'updated_at' => $productTranslation->updated_at,
                                            ],
                                            'product' => [
                                                'id' => $productModel->id,
                                                'user_id' => $productModel->user_id,
                                                'calories' => $calories, // Рассчитанные калории
                                                'proteins' => $proteins, // Рассчитанные белки
                                                'carbohydrates' => $carbohydrates, // Рассчитанные углеводы
                                                'fats' => $fats, // Рассчитанные жиры
                                                'fibers' => $fibers, // Рассчитанные клетчатка
                                                'quantity_grams' => $quantity,
                                                'is_popular' => $productModel->is_popular,
                                                'created_at' => $productModel->created_at,
                                                'updated_at' => $productModel->updated_at,
                                            ],
                                            'quantity_grams' => $quantity // Количество в граммах
                                        ];
                                    } else {
                                        // Если нутриентные данные отсутствуют
                                        Log::warning("Нутриентные данные отсутствуют для продукта: {$productName}");
                                    }
                                } else {
                                    // Если продукт не найден
                                    Log::warning("Продукт не найден: {$productName}");
                                }
                            } else {
                                // Некорректное количество
                                Log::warning("Некорректное количество для продукта: {$productName}");
                            }
                        } else {
                            // Не удалось извлечь количество
                            Log::warning("Не удалось извлечь количество из строки: {$quantityStr}");
                        }
                    } else {
                        // Некорректный формат продукта
                        Log::warning("Некорректный формат продукта: {$product}");
                    }
                }
            }

            // Если найдены продукты, обновляем ответ
            if (!empty($productsInfo)) {
                $response = [
                    'message' => 'Products found',
                    'products' => $productsInfo
                ];
            }
        }

        Log::info('products: ');
        Log::info(print_r($productsInfo, true));
        // Возвращаем JSON-ответ
        return response()->json($response);
    }


    public function saveProduct(StoreUsersFoodConsumptionsRequest $request, ProductService $productService ): JsonResponse
    {
        $validatedData = $request->validated();
        Log::info(print_r($validatedData, true));
        $userResult = $productService->createProductWithTranslationsAndConsumption($validatedData);
        return response()->json($userResult, 201);
    }

    public function saveFoodConsumption(StoreFoodConsumptionRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();
        Log::info(print_r($validatedData, true));
        $validatedData['user_id'] = $validatedData['user_id'] ?? auth()->id();
        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);
        return response()->json(['id' => $foodConsumption->id]);
    }

}
