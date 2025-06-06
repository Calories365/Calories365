<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchProductController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $count = $request->input('count', 10);

        $products = Product::getSearchedProductsViaMeili($query, $count);
        $products->load('product');
        $resourceCollection = ProductResource::collection($products);

        $result = [
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'products' => $resourceCollection->response()->getData()->data,
        ];

        return response()->json($result);
    }
}
