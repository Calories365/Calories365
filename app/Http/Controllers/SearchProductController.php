<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchValidationRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\SearchService;

class SearchProductController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(SearchValidationRequest $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('query');
        $count = $request->input('count', 10);

        $products = Product::getSearchedProductsViaMeili($query, $count);
        $products->load('product');
        $resourceCollection = ProductResource::collection($products);

        $result = [
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'products' => $resourceCollection->response()->getData()->data
        ];

        return response()->json($result);
    }

}
