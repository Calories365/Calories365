<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
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
        $text = $request->input('text');
        Log::info("Received text: " . $text);

        $response = ['message' => 'No products mentioned', 'products' => []];

        if ($text !== 'Продуктов нет') {
            $productsInfo = [];

            $products = explode(';', $text);

            foreach ($products as $product) {
                $product = trim($product);
                if (!empty($product)) {
                    $parts = explode(' - ', $product);
                    if (count($parts) > 1) {
                        $productName = trim($parts[0]);
                        $searchedProducts = $this->searchService->search($productName, false, 3);

                        // Преобразуем результаты в массив
                        $productsArray = $searchedProducts->toArray();

                        // Формируем массив только с ID и названиями продуктов
                        $productNamesAndIds = array_map(function ($item) {
                            return ['id' => $item->id, 'name' => $item->name];
                        }, $productsArray);

                        // Добавляем информацию о продукте в ответ
                        $productsInfo[] = [
                            'name' => $productName,
                            'weight' => trim($parts[1]),
                            'details' => $productNamesAndIds
                        ];
                    }
                }
            }

            $response = [];

            if (!empty($productsInfo)) {
                Log::info('products from data base');
                Log::info(print_r($productsInfo, true));
                $response = $productsInfo;
            }
        }

        // Возвращаем JSON ответ
        return response()->json($response);
    }

}
