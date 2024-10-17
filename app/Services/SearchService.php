<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\DoubleMetaphoneTrait;
use Illuminate\Support\Facades\Log;

class SearchService
{
    use DoubleMetaphoneTrait;

    public function search($query, $pagination = true, $count = 10)
    {
        $encodedQuery = $this->customDoubleMetaphone($query);
        return Product::getSearchedProducts($encodedQuery, $pagination, $count);
    }

}
