<?php

namespace Tests\Unit;

use App\Models\ProductTranslation;
use Tests\TestCase;

/**
 * Product search filters the products index by locale, owner and flags
 * (Product::getRawProduct, Product::getSearchedProductsViaMeili). Meilisearch
 * rejects such a query unless the attributes are declared filterable, and
 * scout:sync-index-settings declares only what this config lists.
 */
class ScoutIndexSettingsTest extends TestCase
{
    public function test_the_products_index_declares_the_attributes_search_filters_by(): void
    {
        $settings = config('scout.meilisearch.index-settings')[ProductTranslation::class] ?? [];

        foreach (['locale', 'user_id', 'active', 'verified'] as $attribute) {
            $this->assertContains($attribute, $settings['filterableAttributes'] ?? []);
        }
    }
}
