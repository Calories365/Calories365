<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateValidationRequest;
use App\Http\Requests\StoreFoodConsumptionRequest;
use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Http\Resources\MealCollection;
use App\Models\FoodConsumption;
use App\Models\Product;
use App\Services\ProductMatcher;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class CaloriesAPIBotController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Find the products of an analysed voice message, "Name - N grams;" per item.
     *
     * The items go to ProductMatcher together. An item it finds no product for, or
     * one without a quantity, comes back with found = false, and the panel
     * generates its nutrition facts.
     */
    public function storeFiltered(Request $request, ProductMatcher $matcher): JsonResponse
    {
        $text = $request->input('text', '');

        $response = [
            'message' => 'Products not found',
            'products' => [],
        ];

        if (trim($text) === '' || trim($text) === 'No products') {
            return response()->json($response);
        }

        $locale = app()->getLocale();
        $suffixMap = ['en' => 'grams', 'ua' => 'грам', 'ru' => 'грамм'];
        $suffix = $suffixMap[$locale] ?? 'грамм';
        $delimiter = str_contains($text, ';') ? ';' : $suffix;
        $rawItems = array_filter(array_map('trim', explode($delimiter, $text)));

        $items = [];
        foreach ($rawItems as $raw) {
            $parts = array_map('trim', explode(' - ', $raw));

            if (count($parts) !== 2 || ! preg_match('/(\d+(\.\d+)?)/', $parts[1], $m)) {
                $items[] = ['said_name' => $raw, 'quantity' => null];

                continue;
            }

            $items[] = ['said_name' => $parts[0], 'quantity' => (float) $m[1]];
        }

        $toMatch = array_keys(array_filter($items, static fn (array $item) => $item['quantity'] > 0));
        $hits = $toMatch === []
            ? []
            : array_combine($toMatch, $matcher->match(
                array_map(static fn (int $key) => $items[$key]['said_name'], $toMatch),
                auth()->id(),
                $locale
            ));

        $productsInfo = [];
        foreach ($items as $key => $item) {
            $hit = $hits[$key] ?? null;
            $productModel = $hit ? Product::find($hit['product_id']) : null;

            if (! $productModel || $productModel->calories === null) {
                $productsInfo[] = [
                    'product_translation' => null,
                    'product' => null,
                    'said_name' => $item['said_name'],
                    'quantity_grams' => $item['quantity'],
                    'found' => false,
                ];

                continue;
            }

            $productsInfo[] = $this->foundProduct($hit, $productModel, $item['said_name'], $item['quantity'])
                + ['found' => true];
        }

        if ($productsInfo) {
            $response = [
                'message' => 'Products found',
                'products' => $productsInfo,
            ];
        }

        return response()->json($response);
    }

    /**
     * The product for one "Name - N grams" item, for the bot's "search" button.
     *
     * Answers false when the text has no quantity or no product is the same food.
     */
    public function getTheMostRelevantProduct(Request $request, ProductMatcher $matcher): bool|JsonResponse
    {
        $parts = array_map('trim', explode(' - ', (string) $request->input('text')));

        if (count($parts) < 2 || ! preg_match('/(\d+(\.\d+)?)/', $parts[1], $matches)) {
            return false;
        }

        $productName = $parts[0];
        $quantity = (float) $matches[1];

        $hit = $matcher->match([$productName], auth()->id(), app()->getLocale())[0] ?? null;
        $product = $hit ? Product::find($hit['product_id']) : null;

        if (! $product) {
            return false;
        }

        return response()->json([
            'message' => 'Product found',
            'product' => $this->foundProduct($hit, $product, $productName, $quantity),
        ]);
    }

    /**
     * One found item in the shape the bot panel reads.
     *
     * @param  array<string, mixed>  $hit  the product translation from the search index
     * @return array<string, mixed>
     */
    private function foundProduct(array $hit, Product $product, string $saidName, float $quantity): array
    {
        return [
            'product_translation' => [
                'id' => $hit['id'],
                'product_id' => $hit['product_id'],
                'locale' => $hit['locale'],
                'name' => $hit['name'],
                'said_name' => $saidName,
                'original_name' => $saidName,
            ],
            'product' => [
                'id' => $product->id,
                'user_id' => $product->user_id,
                'calories' => $product->calories,
                'proteins' => $product->proteins,
                'carbohydrates' => $product->carbohydrates,
                'fats' => $product->fats,
                'fibers' => $product->fibers ?? 0,
                'quantity_grams' => $quantity,
            ],
            'quantity_grams' => $quantity,
        ];
    }

    public function saveProduct(StoreUsersFoodConsumptionsRequest $request, ProductService $productService): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $userResult = $productService->createProductWithTranslationsAndConsumption($validatedData);

        return response()->json($userResult, 201);
    }

    public function saveFoodConsumption(StoreFoodConsumptionRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $foodConsumption = FoodConsumption::createFoodConsumption($validatedData);

        return response()->json(['id' => $foodConsumption->id]);
    }

    public function showUserStats(DateValidationRequest $request): MealCollection
    {
        $userId = auth()->id();
        $date = $request->route('date');
        $partOfDay = $request->route('partOfDay');
        $locale = app()->getLocale();
        $meals = FoodConsumption::getMealsWithCurrentDate($date, $userId, $locale, $partOfDay);

        return new MealCollection($meals);
    }

    /**
     * Delete a meal of the account the panel acts for.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(FoodConsumption $meal): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $meal);

        $meal->delete();

        return response()->json(['message' => 'Success']);
    }
}
