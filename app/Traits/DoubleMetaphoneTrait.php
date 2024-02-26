<?php

namespace App\Traits;

use DoubleMetaphone;
use voku\helper\ASCII;

trait DoubleMetaphoneTrait
{
    public function customDoubleMetaphone($query): string
    {
        $query = ASCII::to_transliterate($query);
        $words = explode(' ', $query);
        $encodedWords = [];

        foreach ($words as $word) {
            $doubleMetaphone = new DoubleMetaphone($word);
            $encodedWords[] = $doubleMetaphone->primary;
        }

        return implode(' ', $encodedWords);
    }
}
