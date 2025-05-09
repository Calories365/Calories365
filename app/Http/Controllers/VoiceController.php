<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Services\SpeechToTextService;
use App\Services\AudioConversionService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {

            $res = $this->canTranscribeAudio(Auth::id());

           if(!$res['canTranscribeAudio']){
               return response()->json([
                   'success' => false,
                   'message' => 'please_buy_premium'
               ], 200);
           }

            if (!$request->hasFile('audio')) {
                return response()->json(['success' => false, 'message' => 'Аудиофайл не найден'], 400);
            }
            $audioFile = $request->file('audio');

            $fileName = Str::uuid().'.'.$audioFile->getClientOriginalExtension();
            $localPath = $audioFile->storeAs('voice_records', $fileName, 'public');
            $fullPath  = Storage::disk('public')->path($localPath);

            [$mp3Local, $mp3Full] = $this->audioConversionService->convertToMp3($localPath, $fullPath);

            $fileForStt = $mp3Full ?: $fullPath;


            $transcription = $this->speechToTextService->convertSpeechToText(
                $fileForStt,
                app()->getLocale()
            );
            Log::info('$transcription: ');
            Log::info(print_r($transcription, true));
            Storage::disk('public')->delete($localPath);
            if ($mp3Local) {
                Storage::disk('public')->delete($mp3Local);
            }

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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveProducts(Request $request)
    {
        try {
            $data = $request->all();
            $products = $data['products'] ?? [];
            $mealType = $data['meal_type'] ?? 'morning';

            $mealTypeMap = [
                'завтрак' => 'morning',
                'обед' => 'dinner',
                'ужин' => 'supper'
            ];

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

                if (!isset($product['name'])) {
                    Log::warning('Missing name in product data', $product);
                    continue;
                }

                $productData = [
                    'user_id' => Auth::id(),
                    'part_of_day' => $mealType,
                    'name' => $product['name'],
                    'product_translation' => [
                        'name' => $product['name'],
                        'locale' => app()->getLocale(),
                        'verified' => 0,
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

                $productData['verified'] = 0;
                $productData['active'] = 1;

                Log::info('product data');
                Log::info(print_r($productData, true));

                $wasModified = isset($product['isModified']) && $product['isModified'] === true;

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
                else {
                    Log::info('Creating new product or saving modified product', [
                        'name' => $productData['name'],
                        'wasModified' => $wasModified,
                        'hasProductId' => !empty($product['product_id'])
                    ]);

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
     *
     * @param string $text
     * @return array
     */
    private function searchProductsFromTranscription(string $text): array
    {
        $productsInfo = [];
        $locale = app()->getLocale();
        $user_id = Auth::id();
        Log::info(print_r($locale, true));

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

        if (str_contains($text, ';')) {
            $products = explode(';', $text);
        } else {
            $products = explode($suffix, $text);
        }

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
                            $productData = $this->findProduct($productName, $quantity, $user_id, $locale);

                            if ($productData) {
                                $productsInfo[] = $productData;
                            } else {
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
     *
     * @param string $productName
     * @param float $quantity
     * @param int $user_id
     * @param string $locale
     * @return array|null
     */
    private function findProduct(string $productName, float $quantity, int $user_id, string $locale): ?array
    {
        $productTranslation = Product::getRawProduct($productName, $user_id, $locale);

        if (is_array($productTranslation) && isset($productTranslation['_rankingScore']) && $productTranslation['_rankingScore'] < 0.9) {
            Log::info("Продукт не найден (низкий рейтинг): {$productName}");
            return null;
        }

        if (is_array($productTranslation)) {
            $product = Product::find($productTranslation['product_id']);

            if ($product && $product->calories !== null) {
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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateProductData(Request $request)
    {
        try {
            if (!$request->has('product_name')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Имя продукта не указано'
                ], 400);
            }

            $productName = $request->input('product_name');

            Log::info('Генерация данных для продукта', ['name' => $productName]);


            $generatedData = $this->speechToTextService->generateNewProductData($productName);

            Log::info('$generatedData: ');
            Log::info(print_r($generatedData, true));

            if (is_array($generatedData) && isset($generatedData['error'])) {
                Log::error('Ошибка при генерации данных продукта', ['error' => $generatedData['error']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при генерации данных: ' . $generatedData['error']
                ], 500);
            }

            if (!is_string($generatedData)) {
                Log::error('Неожиданный формат данных от OpenAI', ['data' => $generatedData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Неожиданный формат данных от API'
                ], 500);
            }

            $parsedData = $this->parseGeneratedProductData($generatedData);

            if (!$parsedData) {
                Log::error('Не удалось распарсить данные продукта', ['raw_data' => $generatedData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось распарсить данные продукта'
                ], 500);
            }

            Log::info('Сгенерированы данные о продукте', ['product' => $productName, 'data' => $parsedData]);

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
     *
     * @param string $generatedData
     * @return array|null
     */
    private function parseGeneratedProductData(string $generatedData): ?array
    {
        try {
            if (Str::startsWith($generatedData, '{') && Str::endsWith($generatedData, '}')) {
                $data = json_decode($generatedData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            $data = [];

            Log::info('Начало парсинга данных продукта:', ['raw' => $generatedData]);

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

            if (empty($data)) {
                $parts = preg_split('/[;,]/', $generatedData);

                foreach ($parts as $part) {
                    $part = trim($part);

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

            if (!isset($data['fibers'])) {
                $data['fibers'] = 0;
            }

            Log::info('Результат парсинга данных продукта:', $data);

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
     *
     * @param string $productName
     * @param float $quantity
     * @return array|null
     */
    private function generateProductsData(string $productName, float $quantity): ?array
    {
        try {
            $generatedData = $this->speechToTextService->generateNewProductData($productName);

            Log::info('Генерация данных для продукта из расшифровки', [
                'name' => $productName,
                'quantity' => $quantity,
                'response' => $generatedData
            ]);

            if (is_array($generatedData) && isset($generatedData['error'])) {
                Log::error("Ошибка при генерации данных продукта: " . $generatedData['error']);
                return null;
            }

            if (!is_string($generatedData)) {
                Log::error('Неожиданный формат данных от OpenAI', ['data' => $generatedData]);
                return null;
            }

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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProduct(Request $request)
    {
        try {
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

            $searchResult = Product::getRawProduct($productName, $userId, $locale);

            if ($searchResult) {
                $rankingScore = $searchResult['_rankingScore'] ?? 0;

                Log::info('Найден продукт', [
                    'product' => $searchResult,
                    'ranking_score' => $rankingScore
                ]);

                $productId = $searchResult['product_id'] ?? null;

                if (!$productId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Продукт найден, но отсутствует ID',
                        'should_generate' => true
                    ]);
                }

                $fullProduct = Product::find($productId);

                if (!$fullProduct) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Не удалось загрузить полные данные о продукте',
                        'should_generate' => true
                    ]);
                }

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
    private function canTranscribeAudio($user_id){
        $botPanelUrl = env('BOT_PANEL_URL');
        $botApiKey   = env('BOT_API_KEY');
        $host   = env('BOT_HOST');

        if (!$botPanelUrl || !$botApiKey) {
            Log::warning('Bot panel URL или API key не настроены');
            return;
        }

        $client = new Client();
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Host'         => $host,
            'X-Api-Key'    => $botApiKey,
        ];
        try {
            $response = $client->get($botPanelUrl . '/api/subscription-check/' . $user_id, [
                'headers' => $headers,
            ]);
            Log::info('response from sub');
            Log::info(print_r($response, true));
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("Error sending text to diary service: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
