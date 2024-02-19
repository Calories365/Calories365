<?php

namespace App\Services;

use App\Models\Product;
use DoubleMetaphone;
use voku\helper\ASCII;

class SearchService
{
    public function search($query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ASCII::to_transliterate($query);
        $doubleMetaphone = new DoubleMetaphone($query);
        $encodedQuery = $doubleMetaphone->primary;
        return Product::getSearchedProducts($encodedQuery);
    }
}
