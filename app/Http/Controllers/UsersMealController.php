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
     * @throws \Exception
     */
    public function store(StoreUsersFoodConsumptionsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $userResult = $this->productService->createProductWithTranslationsAndConsumption($validatedData);

        return response()->json($userResult, 201);
    }
}
