<?php

declare(strict_types=1);

namespace App\Support\Formatters;

use App\Models\UserResult;

final class FeedbackPromptFormatter
{
    public static function buildUserInfo(?UserResult $userResult, string $locale): string
    {
        $default = __('calories365-bot.default_user_info', [], $locale);

        if (! $userResult) {
            return $default;
        }

        $gender = $userResult->gender === 'male'
            ? __('calories365-bot.male', [], $locale)
            : __('calories365-bot.female', [], $locale);

        $age = (int) date('Y') - (int) $userResult->birthYear;
        $weight = $userResult->weight;
        $height = $userResult->height;
        $goalWeight = $userResult->goalWeight;
        $dailyCalories = $userResult->dailyCalories;

        return $gender.
            ', '.$age.' '.__('calories365-bot.years_old', [], $locale).
            ', '.$weight.' '.__('calories365-bot.kg', [], $locale).
            ', '.__('calories365-bot.height_cm', [], $locale).' '.$height.' '.__('calories365-bot.cm', [], $locale).
            ', '.__('calories365-bot.goal', [], $locale).' — '.$goalWeight.' '.__('calories365-bot.kg', [], $locale).
            ', '.__('calories365-bot.daily_calories_norm', [], $locale).' — '.$dailyCalories;
    }

    public static function buildPartOfDayInfo(?string $partOfDay, string $locale): string
    {
        if (! $partOfDay) {
            return __('calories365-bot.daily_diet', [], $locale);
        }

        $map = [
            'morning' => __('calories365-bot.breakfast', [], $locale),
            'dinner' => __('calories365-bot.lunch', [], $locale),
            'supper' => __('calories365-bot.dinner', [], $locale),
        ];

        $name = $map[$partOfDay] ?? $partOfDay;

        return __('calories365-bot.meal_time', [], $locale).': '.$name;
    }

    /**
     * @param  array<int,string>  $nameGramsList
     */
    public static function buildMealsList(array $nameGramsList, string $locale): string
    {
        $mealsList = implode(', ', $nameGramsList);

        return $mealsList !== '' ? $mealsList : __('calories365-bot.no_products_consumed', [], $locale);
    }

    public static function buildPrompt(string $userInfo, string $partOfDayInfo, string $mealsList, string $locale): string
    {
        return __('calories365-bot.get_feedback_for_part_of_day', [
            'user_info' => $userInfo,
            'part_of_day_info' => $partOfDayInfo,
            'meals_list' => $mealsList,
        ], $locale);
    }
}
