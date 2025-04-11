<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Services\SpeechToTextService;
use App\Services\AudioConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    protected SpeechToTextService $speechToTextService;
    protected ProductService $productService;
    protected AudioConversionService $audioConversionService;

    public function __construct(SpeechToTextService $speechToTextService, ProductService $productService,   AudioConversionService $audioConversionService  )
    {
        $this->speechToTextService = $speechToTextService;
        $this->productService = $productService;
        $this->audioConversionService = $audioConversionService;

    }

    /**
     * Загрузка голосовой записи, расшифровка и поиск продуктов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            /** === 1. проверяем файл === */
            if (!$request->hasFile('audio')) {
                return response()->json(['success' => false, 'message' => 'Аудиофайл не найден'], 400);
            }
            $audioFile = $request->file('audio');

            /** === 2. сохраняем оригинал === */
            $fileName = Str::uuid().'.'.$audioFile->getClientOriginalExtension();
            $localPath = $audioFile->storeAs('voice_records', $fileName, 'public');
            $fullPath  = Storage::disk('public')->path($localPath);

            /** === 3. конвертируем в mp3 === */
            [$mp3Local, $mp3Full] = $this->audioConversionService->convertToMp3($localPath, $fullPath);

            // если конвертация не удалась — работаем с исходным файлом
            $fileForStt = $mp3Full ?: $fullPath;

            /** === 4. Whisper (передаём локаль) === */
            $transcription = $this->speechToTextService->convertSpeechToText(
                $fileForStt,
                app()->getLocale()          // метод в сервисе должен принимать language
            );

            /** === 5. чистим временные файлы === */
            Storage::disk('public')->delete($localPath);
            if ($mp3Local) {
                Storage::disk('public')->delete($mp3Local);
            }

            /** === 6. проверяем результат === */
            if (is_array($transcription) && isset($transcription['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при расшифровке: '.$transcription['error']
                ], 500);
            }

            $products = $this->searchProductsFromTranscription($transcription);

            Log::info('Voice record processed', [
                'user_id'       => Auth::id(),
                'transcription' => $transcription,
                'products_found'=> $products
            ]);

            return response()->json([
                'success'       => true,
                'message'       => 'Голосовая запись успешно обработана',
                'transcription' => $transcription,
                'products'      => $products
            ]);

        } catch (\Throwable $e) {
            // попытка удалить всё, что могло остаться
            if (isset($localPath)) Storage::disk('public')->delete($localPath);
            if (isset($mp3Local))  Storage::disk('public')->delete($mp3Local);

            Log::error('Error processing voice record: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке записи: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохранение продуктов в базу данных
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveProducts(Request $request)
    {
        try {
            $data = $request->all();
            $products = $data['products'] ?? [];
            $mealType = $data['meal_type'] ?? 'morning'; // morning, dinner, supper

            // Преобразование русских названий в английские
            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper'
            ];

            // Если передано русское название, преобразуем в английское
            if (isset($mealTypeMap[$mealType])) {
                $mealType = $mealTypeMap[$mealType];
                Log::info('Meal type converted to English', ['original' => $data['meal_type'], 'converted' => $mealType]);
            }

            if (empty($products)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нет продуктов для сохранения'
                ], 400);
            }

            $savedProducts = [];
            foreach ($products as $product) {
                Log::info('Processing product', $product);

                // Проверяем наличие необходимых полей
                if (!isset($product['name'])) {
                    Log::warning('Missing name in product data', $product);
                    continue;
                }

                // Подготавливаем данные для создания/поиска продукта
                $productData = [
                    'user_id' => Auth::id(),
                    'part_of_day' => $mealType,
                    'name' => $product['name'], // Добавляем в корень для ProductService
                    'product_translation' => [
                        'name' => $product['name'],
                        'locale' => app()->getLocale(),
                        'verified' => 0, // Добавляем поле verified, по умолчанию 0 (не проверено)
                    ],
                    'product' => [
                        'calories' => $product['calories'] ?? 0,
                        'proteins' => $product['protein'] ?? 0, // Front: protein, Back: proteins
                        'fats' => $product['fats'] ?? 0,
                        'carbohydrates' => $product['carbs'] ?? 0, // Front: carbs, Back: carbohydrates
                        'fibers' => $product['fibers'] ?? 0,
                    ],
                    'quantity' => $product['weight'] ?? 0, // Front: weight, Back: quantity
                ];

                // Добавляем поле verified на верхнем уровне для ProductTranslation::createProductTranslations
                $productData['verified'] = 0;
                $productData['active'] = 1;

                Log::info('product data');
                Log::info(print_r($productData, true));

                // Проверяем, был ли модифицирован продукт
                $wasModified = isset($product['isModified']) && $product['isModified'] === true;

                // Если продукт имеет ID (уже существует в базе) и НЕ был модифицирован
                if (!empty($product['product_id']) && !$wasModified) {
                    Log::info('Using existing product ID', ['product_id' => $product['product_id']]);
                    Log::info('Saving product with weight', [
                        'name' => $product['name'],
                        'weight' => $product['weight'] ?? 0,
                    ]);
                    $result = $this->productService->createFoodConsumption([
                        'user_id' => Auth::id(),
                        'product_id' => $product['product_id'],
                        'part_of_day' => $mealType,
                        'quantity' => $product['weight'] ?? 0,
                    ]);
                }
                // Если продукт был модифицирован или это новый продукт
                else {
                    Log::info('Creating new product or saving modified product', [
                        'name' => $productData['name'],
                        'wasModified' => $wasModified,
                        'hasProductId' => !empty($product['product_id'])
                    ]);

                    // Если продукт был модифицирован, сохраняем его как пользовательскую версию
                    if ($wasModified && !empty($product['product_id'])) {
                        Log::info('Creating custom version of existing product', [
                            'original_id' => $product['product_id'],
                            'new_values' => [
                                'calories' => $product['calories'],
                                'protein' => $product['protein'],
                                'fats' => $product['fats'],
                                'carbs' => $product['carbs']
                            ]
                        ]);
                    }

                    $result = $this->productService->createProductWithTranslationsAndConsumption($productData);
                }

                $savedProducts[] = $result;
            }

            return response()->json([
                'success' => true,
                'message' => 'Продукты успешно сохранены',
                'data' => $savedProducts
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error saving products: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при сохранении продуктов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Поиск продуктов на основе расшифрованного текста
     *
     * @param string $text
     * @return array
     */
    private function searchProductsFromTranscription(string $text): array
    {
        $productsInfo = [];
        $locale = app()->getLocale();
        $user_id = Auth::id();

        // Определение суффикса в зависимости от локали
        $suffix = 'грамм';
        switch ($locale) {
            case 'en':
                $suffix = 'grams';
                break;
            case 'ua':
                $suffix = 'грам';
                break;
            case 'ru':
            default:
                $suffix = 'грамм';
                break;
        }

        // Разбиваем текст на отдельные продукты
        if (str_contains($text, ';')) {
            $products = explode(';', $text);
        } else {
            $products = explode($suffix, $text);
        }

        // Фильтруем пустые строки из массива продуктов
        $products = array_filter($products, function($item) {
            return trim($item) !== '';
        });

        Log::info('Products from transcription:', ['products' => $products]);

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
                            // Ищем продукт в базе данных
                            $productData = $this->findProduct($productName, $quantity, $user_id, $locale);

                            if ($productData) {
                                $productsInfo[] = $productData;
                            } else {
                                // Если продукт не найден, генерируем данные продукта
                                $generatedProductData = $this->generateProductsData($productName, $quantity);
                                if ($generatedProductData) {
                                    $productsInfo[] = $generatedProductData;
                                }
                            }
                        } else {
                            Log::warning("Некорректное количество продукта: {$productName}");
                        }
                    } else {
                        Log::warning("Ошибка при извлечении количества: {$quantityStr}");
                    }
                } else {
                    Log::warning("Некорректный формат продукта: {$product}");
                }
            }
        }

        return $productsInfo;
    }

    /**
     * Поиск продукта в базе данных
     *
     * @param string $productName
     * @param float $quantity
     * @param int $user_id
     * @param string $locale
     * @return array|null
     */
    private function findProduct(string $productName, float $quantity, int $user_id, string $locale): ?array
    {
        // Пытаемся найти продукт через MeiliSearch
        $productTranslation = Product::getRawProduct($productName, $user_id, $locale);

        // Если рейтинг совпадения слишком низкий, считаем, что продукт не найден
        if (is_array($productTranslation) && isset($productTranslation['_rankingScore']) && $productTranslation['_rankingScore'] < 0.9) {
            Log::info("Продукт не найден (низкий рейтинг): {$productName}");
            return null;
        }

        if (is_array($productTranslation)) {
            // Загружаем данные продукта
            $product = Product::find($productTranslation['product_id']);

            if ($product && $product->calories !== null) {
                // Передаем точное количество, указанное пользователем
                Log::info("Найден продукт '{$productTranslation['name']}', указанное количество: {$quantity} грамм");
                return [
                    'product_translation' => [
                        'id' => $productTranslation['id'],
                        'product_id' => $productTranslation['product_id'],
                        'locale' => $productTranslation['locale'],
                        'name' => $productTranslation['name'],
                        'user_id' => $productTranslation['user_id'] ?? null,
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
                        'fibers' => $product->fibers ?? 0,
                        'quantity' => $quantity,
                    ],
                    'quantity' => $quantity
                ];
            }
        }

        return null;
    }

    /**
     * Генерация данных продукта
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateProductData(Request $request)
    {
        try {
            // Проверяем наличие имени продукта в запросе
            if (!$request->has('product_name')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Имя продукта не указано'
                ], 400);
            }

            $productName = $request->input('product_name');

            Log::info('Генерация данных для продукта', ['name' => $productName]);


            // Генерируем данные о продукте через OpenAI
            $generatedData = $this->speechToTextService->generateNewProductData($productName);

            Log::info('$generatedData: ');
            Log::info(print_r($generatedData, true));

            // Проверяем на ошибки (если вернулся массив с ошибкой)
            if (is_array($generatedData) && isset($generatedData['error'])) {
                Log::error('Ошибка при генерации данных продукта', ['error' => $generatedData['error']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при генерации данных: ' . $generatedData['error']
                ], 500);
            }

            // Убедимся, что у нас есть строка для парсинга
            if (!is_string($generatedData)) {
                Log::error('Неожиданный формат данных от OpenAI', ['data' => $generatedData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Неожиданный формат данных от API'
                ], 500);
            }

            // Парсим результат
            $parsedData = $this->parseGeneratedProductData($generatedData);

            if (!$parsedData) {
                Log::error('Не удалось распарсить данные продукта', ['raw_data' => $generatedData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось распарсить данные продукта'
                ], 500);
            }

            Log::info('Сгенерированы данные о продукте', ['product' => $productName, 'data' => $parsedData]);

            // Возвращаем сгенерированные данные
            return response()->json([
                'success' => true,
                'message' => 'Данные продукта успешно сгенерированы',
                'data' => [
                    'name' => $productName,
                    'calories' => $parsedData['calories'] ?? 0,
                    'proteins' => $parsedData['proteins'] ?? 0,
                    'carbohydrates' => $parsedData['carbohydrates'] ?? 0,
                    'fats' => $parsedData['fats'] ?? 0,
                    'fibers' => $parsedData['fibers'] ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при генерации данных продукта: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при генерации данных продукта: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Парсинг сгенерированных данных продукта
     *
     * @param string $generatedData
     * @return array|null
     */
    private function parseGeneratedProductData(string $generatedData): ?array
    {
        try {
            // Пытаемся распарсить как JSON
            if (Str::startsWith($generatedData, '{') && Str::endsWith($generatedData, '}')) {
                $data = json_decode($generatedData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            // Если не удается распарсить как JSON, пытаемся извлечь данные из текста
            $data = [];

            // Логирование входных данных для отладки
            Log::info('Начало парсинга данных продукта:', ['raw' => $generatedData]);

            // Проверяем русский формат данных (Калории - 98; Белки - 16.7; Жиры - 9; Углеводы - 2;)
            if (preg_match('/Калории[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['calories'] = floatval($matches[1]);
                Log::info('Распознаны калории (русский формат):', ['value' => $data['calories']]);
            }

            if (preg_match('/Белки[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['proteins'] = floatval($matches[1]);
                Log::info('Распознаны белки (русский формат):', ['value' => $data['proteins']]);
            }

            if (preg_match('/Углеводы[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['carbohydrates'] = floatval($matches[1]);
                Log::info('Распознаны углеводы (русский формат):', ['value' => $data['carbohydrates']]);
            }

            if (preg_match('/Жиры[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fats'] = floatval($matches[1]);
                Log::info('Распознаны жиры (русский формат):', ['value' => $data['fats']]);
            }

            if (preg_match('/Клетчатка[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fibers'] = floatval($matches[1]);
                Log::info('Распознана клетчатка (русский формат):', ['value' => $data['fibers']]);
            }

            // Проверяем украинский формат данных (Калорії - 77; Білки - 2; Жири - 0.1; Вуглеводи - 17.5;)
            if (!isset($data['calories']) && preg_match('/Калорії[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['calories'] = floatval($matches[1]);
                Log::info('Распознаны калории (украинский формат):', ['value' => $data['calories']]);
            }

            if (!isset($data['proteins']) && preg_match('/Білки[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['proteins'] = floatval($matches[1]);
                Log::info('Распознаны белки (украинский формат):', ['value' => $data['proteins']]);
            }

            if (!isset($data['carbohydrates']) && preg_match('/Вуглеводи[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['carbohydrates'] = floatval($matches[1]);
                Log::info('Распознаны углеводы (украинский формат):', ['value' => $data['carbohydrates']]);
            }

            if (!isset($data['fats']) && preg_match('/Жири[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fats'] = floatval($matches[1]);
                Log::info('Распознаны жиры (украинский формат):', ['value' => $data['fats']]);
            }

            if (!isset($data['fibers']) && preg_match('/Клітковина[\s\-]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fibers'] = floatval($matches[1]);
                Log::info('Распознана клетчатка (украинский формат):', ['value' => $data['fibers']]);
            }

            // Стандартный английский формат (как запасной вариант)
            if (!isset($data['calories']) && preg_match('/calories[\s\:]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['calories'] = floatval($matches[1]);
            }

            if (!isset($data['proteins']) && preg_match('/proteins[\s\:]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['proteins'] = floatval($matches[1]);
            }

            if (!isset($data['carbohydrates']) && preg_match('/carbohydrates[\s\:]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['carbohydrates'] = floatval($matches[1]);
            }

            if (!isset($data['fats']) && preg_match('/fats[\s\:]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fats'] = floatval($matches[1]);
            }

            if (!isset($data['fibers']) && preg_match('/fibers[\s\:]+(\d+\.?\d*)/i', $generatedData, $matches)) {
                $data['fibers'] = floatval($matches[1]);
            }

            // Более общий подход для любого формата с разделителями
            if (empty($data)) {
                // Разбиваем строку на части по точке с запятой или запятой
                $parts = preg_split('/[;,]/', $generatedData);

                foreach ($parts as $part) {
                    $part = trim($part);

                    // Пробуем найти пары ключ-значение по шаблону "название - число"
                    if (preg_match('/([а-яА-Яa-zA-Zіїєґ]+)[\s\-:]+(\d+\.?\d*)/u', $part, $matches)) {
                        $key = strtolower(trim($matches[1]));
                        $value = floatval($matches[2]);

                        switch ($key) {
                            case 'калории':
                            case 'калорії':
                            case 'calories':
                            case 'ккал':
                                $data['calories'] = $value;
                                break;
                            case 'белки':
                            case 'білки':
                            case 'proteins':
                            case 'протеин':
                                $data['proteins'] = $value;
                                break;
                            case 'углеводы':
                            case 'вуглеводи':
                            case 'carbohydrates':
                            case 'carbs':
                                $data['carbohydrates'] = $value;
                                break;
                            case 'жиры':
                            case 'жири':
                            case 'fats':
                                $data['fats'] = $value;
                                break;
                            case 'клетчатка':
                            case 'клітковина':
                            case 'fibers':
                                $data['fibers'] = $value;
                                break;
                        }
                    }
                }
            }

            // Устанавливаем значения по умолчанию, если не найдены
            if (!isset($data['fibers'])) {
                $data['fibers'] = 0;
            }

            // Вывод результата парсинга для отладки
            Log::info('Результат парсинга данных продукта:', $data);

            // Если удалось извлечь хотя бы калории или белки или жиры, возвращаем данные
            if (isset($data['calories']) || isset($data['proteins']) || isset($data['fats'])) {
                return $data;
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при парсинге данных продукта: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $generatedData
            ]);
        }

        return null;
    }

    /**
     * Генерация данных продукта через AI
     *
     * @param string $productName
     * @param float $quantity
     * @return array|null
     */
    private function generateProductsData(string $productName, float $quantity): ?array
    {
        try {
            // Генерируем данные продукта через AI
            $generatedData = $this->speechToTextService->generateNewProductData($productName);

            Log::info('Генерация данных для продукта из расшифровки', [
                'name' => $productName,
                'quantity' => $quantity,
                'response' => $generatedData
            ]);

            // Проверяем на ошибки (в случае массива с ошибкой)
            if (is_array($generatedData) && isset($generatedData['error'])) {
                Log::error("Ошибка при генерации данных продукта: " . $generatedData['error']);
                return null;
            }

            // Убедимся, что у нас есть строка для парсинга
            if (!is_string($generatedData)) {
                Log::error('Неожиданный формат данных от OpenAI', ['data' => $generatedData]);
                return null;
            }

            // Парсим сгенерированные данные
            $nutritionData = $this->parseGeneratedProductData($generatedData);

            if ($nutritionData) {
                return [
                    'product_translation' => [
                        'id' => null,
                        'product_id' => null,
                        'locale' => app()->getLocale(),
                        'name' => $productName,
                        'user_id' => Auth::id(),
                        'said_name' => $productName,
                        'original_name' => $productName,
                        'is_generated' => true,
                    ],
                    'product' => [
                        'id' => null,
                        'user_id' => Auth::id(),
                        'calories' => $nutritionData['calories'] ?? 0,
                        'proteins' => $nutritionData['proteins'] ?? 0,
                        'carbohydrates' => $nutritionData['carbohydrates'] ?? 0,
                        'fats' => $nutritionData['fats'] ?? 0,
                        'fibers' => $nutritionData['fibers'] ?? 0,
                        'quantity' => $quantity,
                    ],
                    'quantity' => $quantity,
                    'is_generated' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при генерации данных продукта: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'product' => $productName,
                'quantity' => $quantity
            ]);
        }

        return null;
    }

    /**
     * Поиск продукта по названию
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProduct(Request $request)
    {
        try {
            // Проверяем наличие имени продукта в запросе
            if (!$request->has('product_name')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Имя продукта не указано'
                ], 400);
            }

            $productName = $request->input('product_name');
            $userId = Auth::id();
            $locale = app()->getLocale();

            Log::info('Поиск продукта', [
                'name' => $productName,
                'user_id' => $userId,
                'locale' => $locale
            ]);

            // Ищем продукт в базе данных с использованием MeiliSearch
            $searchResult = Product::getRawProduct($productName, $userId, $locale);

            if ($searchResult) {
                $rankingScore = $searchResult['_rankingScore'] ?? 0;

                Log::info('Найден продукт', [
                    'product' => $searchResult,
                    'ranking_score' => $rankingScore
                ]);

                // Загружаем полные данные о продукте из базы данных
                $productId = $searchResult['product_id'] ?? null;

                if (!$productId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Продукт найден, но отсутствует ID',
                        'should_generate' => true
                    ]);
                }

                // Получаем полные данные о продукте из базы данных
                $fullProduct = Product::find($productId);

                if (!$fullProduct) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Не удалось загрузить полные данные о продукте',
                        'should_generate' => true
                    ]);
                }

                // Форматируем данные о продукте
                $formattedProduct = [
                    'product_translation' => [
                        'id' => $searchResult['id'],
                        'product_id' => $productId,
                        'locale' => $locale,
                        'name' => $searchResult['name'],
                        'user_id' => $searchResult['user_id'] ?? null,
                        'said_name' => $productName,
                        'original_name' => $searchResult['name']
                    ],
                    'product' => [
                        'id' => $productId,
                        'user_id' => $fullProduct->user_id,
                        'calories' => floatval($fullProduct->calories ?? 0),
                        'proteins' => floatval($fullProduct->proteins ?? 0),
                        'carbohydrates' => floatval($fullProduct->carbohydrates ?? 0),
                        'fats' => floatval($fullProduct->fats ?? 0),
                        'fibers' => floatval($fullProduct->fibers ?? 0),
                        'quantity' => 100.0
                    ],
                    'quantity' => 100.0,
                    'ranking_score' => $rankingScore
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Продукт найден',
                    'product' => $formattedProduct,
                    'should_generate' => $rankingScore < 0.9
                ]);
            } else {
                // Если продукт не найден, возвращаем соответствующий статус
                return response()->json([
                    'success' => false,
                    'message' => 'Продукт не найден',
                    'should_generate' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при поиске продукта: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при поиске продукта: ' . $e->getMessage(),
                'should_generate' => true
            ], 500);
        }
    }
}
