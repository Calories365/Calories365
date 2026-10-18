<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsersFoodConsumptionsRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class UsersMealController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Save a product the user typed in on the site, with the meal; it stays the
     * user's own.
     *
     * @throws \Exception
     */
    public function store(StoreUsersFoodConsumptionsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id();
        $userResult = $this->productService->createProductWithTranslationsAndConsumption($validatedData);

        return response()->json($userResult, 201);
    }
}
