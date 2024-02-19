<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchValidationRequest;
use App\Services\SearchService;

class SearchProductController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(SearchValidationRequest $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('query');
        $products = $this->searchService->search($query);
        return response()->json(['products' => $products]);
    }
}
