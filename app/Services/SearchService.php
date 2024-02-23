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

        $words = explode(' ', $query);
        $encodedWords = [];

        foreach ($words as $word) {
            $doubleMetaphone = new DoubleMetaphone($word);
            $encodedWords[] = $doubleMetaphone->primary;
        }

        $encodedQuery = implode(' ', $encodedWords);
        return Product::getSearchedProducts($encodedQuery);
    }

}
