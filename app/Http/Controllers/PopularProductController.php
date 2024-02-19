<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\Product;

class PopularProductController extends Controller
{
    public function index(): ProductCollection
    {
        $locale = app()->getLocale();
        $cacheKey = 'popular_products_' . $locale;
        $products = Product::getPopularProducts($cacheKey);
        return new ProductCollection($products);
    }
}
