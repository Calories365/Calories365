<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Post::create(['title' => 'First Post', 'content' => 'This is the first post content']);
        Post::create(['title' => 'Second Post', 'content' => 'This is the second post content']);
        Post::create(['title' => 'Third Post', 'content' => 'This is about Elasticsearch']);
    }
}
