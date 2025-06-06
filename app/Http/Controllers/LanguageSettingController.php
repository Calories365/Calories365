<?php

namespace App\Http\Controllers;

use App\Models\LanguageSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageSettingController extends Controller
{
    /**
     * Toggle Russian language availability.
     */
    public function toggleRussianLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = $request->input('enabled');

        $setting = LanguageSetting::firstOrCreate(
            ['id' => 1],
            ['russian_language_enabled' => $enabled]
        );

        // Update the setting if it already exists
        if ($setting->russian_language_enabled !== $enabled) {
            $setting->update(['russian_language_enabled' => $enabled]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Russian language '.($enabled ? 'enabled' : 'disabled').' successfully',
            'data' => [
                'russian_language_enabled' => $setting->russian_language_enabled,
            ],
        ]);
    }

    /**
     * Get current Russian language status.
     */
    public function getRussianLanguageStatus(): JsonResponse
    {
        $setting = LanguageSetting::firstOrCreate(
            ['id' => 1],
            ['russian_language_enabled' => true]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'russian_language_enabled' => $setting->russian_language_enabled,
            ],
        ]);
    }
}
