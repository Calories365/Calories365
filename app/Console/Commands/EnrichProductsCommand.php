<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductEnricher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives sugars, the glycemic index and the NOVA group to the products people
 * eat that lack them (ProductEnricher): the ones of the diary of the last 30
 * days, the most eaten first, and the popular ones. Scheduled every ten
 * minutes, so a product added to the diary from the site gets them soon; the
 * products of the whole database are never asked about at once.
 */
class EnrichProductsCommand extends Command
{
    protected $signature = 'products:enrich {--limit=100 : products to ask about at most}';

    protected $description = 'Give sugars, the glycemic index and the NOVA group to the products people eat';

    public function handle(ProductEnricher $enricher): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $eaten = DB::table('food_consumptions')
            ->join('products', 'products.id', '=', 'food_consumptions.food_id')
            ->whereNull('products.quality_source')
            ->where('food_consumptions.created_at', '>=', now()->subDays(30))
            ->groupBy('food_consumptions.food_id')
            ->orderByRaw('count(*) desc')
            ->limit($limit)
            ->pluck('food_consumptions.food_id')
            ->all();

        $popular = Product::query()
            ->whereNull('quality_source')
            ->where('is_popular', true)
            ->limit($limit)
            ->pluck('id')
            ->all();

        $ids = array_slice(array_values(array_unique([...$eaten, ...$popular])), 0, $limit);

        if ($ids === []) {
            return self::SUCCESS;
        }

        $done = $enricher->enrich($ids);
        $this->info("{$done} of ".count($ids).' products got their sugars, glycemic index and NOVA group');

        return self::SUCCESS;
    }
}
