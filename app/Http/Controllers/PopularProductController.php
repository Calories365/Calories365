<?php

namespace App\Http\Controllers;

use App\Http\Resources\PopularProductCollection;
use App\Models\Product;

class PopularProductController extends Controller
{
    public function index(): PopularProductCollection
    {
        $locale = app()->getLocale();
        $cacheKey = 'popular_products_'.$locale;
        $products = Product::getPopularProducts($cacheKey);

        return new PopularProductCollection($products);
    }
}
