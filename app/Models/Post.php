<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return 'posts_index';
    }
}
