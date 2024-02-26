<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\DoubleMetaphoneTrait;

class SearchService
{
    use DoubleMetaphoneTrait;

    public function search($query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $encodedQuery = $this->customDoubleMetaphone($query);
        return Product::getSearchedProducts($encodedQuery);
    }

}
