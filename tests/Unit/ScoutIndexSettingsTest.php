<?php

namespace Tests\Unit;

use App\Models\ProductTranslation;
use Tests\TestCase;

/**
 * Product search filters the products index by locale, owner, flags and the
 * cooking method, and ProductMatcher searches the stems (Product::getRawProduct,
 * Product::getSearchedProductsViaMeili, ProductMatcher::search). Meilisearch
 * rejects such a query unless the attributes are declared filterable and
 * searchable, and scout:sync-index-settings declares only what this config lists.
 */
class ScoutIndexSettingsTest extends TestCase
{
    public function test_the_products_index_declares_the_attributes_search_filters_by(): void
    {
        $settings = config('scout.meilisearch.index-settings')[ProductTranslation::class] ?? [];

        foreach (['locale', 'user_id', 'active', 'verified', 'cooking'] as $attribute) {
            $this->assertContains($attribute, $settings['filterableAttributes'] ?? []);
        }

        $this->assertContains('stems', $settings['searchableAttributes'] ?? []);
    }
}
