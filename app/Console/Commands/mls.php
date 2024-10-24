<?php

namespace App\Console\Commands;

use App\Models\ProductTranslation;
use Illuminate\Console\Command;
use Meilisearch\Client;

class mls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mls';

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
        $client = new Client(env('MEILISEARCH_HOST'), env('MEILISEARCH_KEY'));


        $client->index('products')
            ->updateFilterableAttributes(ProductTranslation::FILTERABLE);

        //        $client->index('product')
//            ->updateSortableAttributes(ProductTranslation::SORTABLE);
    }
}
