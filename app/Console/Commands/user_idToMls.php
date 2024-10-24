<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Console\Command;

class user_idToMls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_id-to-mls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::whereNotNull('user_id')->get();

        foreach ($products as $product) {
            ProductTranslation::where('product_id', $product->id)
                ->update(['user_id' => $product->user_id]);
        }

        $this->info('Updated product_translations with user_id from products.');
        return 0;
    }
}
