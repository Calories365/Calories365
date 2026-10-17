<?php

namespace Tests\Unit;

use App\Services\FoodName;
use PHPUnit\Framework\TestCase;

/**
 * FoodName reads a said name and a product name by the same rules; the search
 * finds a product by the stems and the cooking method these rules give.
 */
class FoodNameTest extends TestCase
{
    public function test_a_said_name_and_a_product_name_of_the_same_food_come_apart_alike(): void
    {
        $said = FoodName::parse('Нога куриная запечённая');

        foreach (['Куриная ножка в духовке', 'Куриные ножки запеченные', 'Куряча гомілка запечена', 'Baked chicken drumsticks'] as $name) {
            $product = FoodName::parse($name);

            $this->assertSame(FoodName::BAKED, $product->cooking, $name);
            $this->assertSame(0, $said->penaltyFor($product, true), $name);
        }
    }

    public function test_a_food_eaten_only_cooked_is_the_cooked_food_when_no_method_is_said(): void
    {
        $buckwheat = FoodName::parse('Гречка')->acceptedCooking();

        $this->assertSame(FoodName::BOILED, array_key_first($buckwheat));
        $this->assertArrayNotHasKey(FoodName::AS_SOLD, $buckwheat);
        $this->assertSame([FoodName::AS_SOLD, FoodName::RAW], array_keys(FoodName::parse('Яблоко')->acceptedCooking()));
        $this->assertArrayHasKey(FoodName::AS_SOLD, FoodName::parse('Гречка сухая')->acceptedCooking());
    }

    public function test_a_food_named_with_a_grain_is_as_sold(): void
    {
        foreach (['Хлопья овсяные', 'Мука гречневая', 'Колбаса куриная', 'Яйцо куриное'] as $name) {
            $this->assertFalse(FoodName::parse($name)->needsCooking(), $name);
        }
    }

    public function test_porridge_and_water_mean_a_boiled_grain(): void
    {
        foreach (['Гречневая каша на воде', 'Вареная гречка на воде', 'Каша гречневая'] as $name) {
            $food = FoodName::parse($name);

            $this->assertSame(FoodName::BOILED, $food->cooking, $name);
            $this->assertSame(['гречк'], $food->food, $name);
            $this->assertSame([], $food->additions, $name);
        }
    }

    public function test_a_cooking_word_of_an_added_part_stays_with_it(): void
    {
        $food = FoodName::parse('Гречка с жареным луком');

        $this->assertSame(FoodName::COOKED, $food->cooking);
        $this->assertSame([['лук']], $food->additions);
        $this->assertSame(FoodName::BAKED, FoodName::parse('Грудка куриная с сыром запечённая')->cooking);
    }

    public function test_a_product_with_something_added_the_user_did_not_name_is_another_dish(): void
    {
        $said = FoodName::parse('Гречка варёная');

        $this->assertNull($said->penaltyFor(FoodName::parse('гречка вареная с луком и морковью'), true));
        $this->assertNull($said->penaltyFor(FoodName::parse('Гречка по купечески'), true));
        $this->assertSame(0, FoodName::parse('Гречка с луком и морковью')->penaltyFor(FoodName::parse('гречка вареная с луком и морковью'), true));
    }

    public function test_a_word_the_user_did_not_say_counts_against_the_product(): void
    {
        $said = FoodName::parse('Макароны');

        $this->assertSame(0, $said->penaltyFor(FoodName::parse('Макароны отварные'), true));
        $this->assertSame(FoodName::UNSAID_WORD, FoodName::parse('Рис')->penaltyFor(FoodName::parse('Рис бурый отварной'), true));
        $this->assertSame(FoodName::UNSAID_BRAND, $said->penaltyFor(FoodName::parse('Макароны Barilla отварные'), true));
        $this->assertSame(FoodName::UNSAID_WORD, FoodName::parse('Pasta')->penaltyFor(FoodName::parse('Pasta Barilla'), false));
    }

    public function test_a_word_the_user_did_not_say_that_is_a_food_of_its_own_makes_another_food(): void
    {
        $this->assertNull(FoodName::parse('Апельсин')->penaltyFor(FoodName::parse('Хлебцы апельсин Fitstart'), true));
        $this->assertNull(FoodName::parse('Семечки')->penaltyFor(FoodName::parse('Казинак из семечек'), true));
        $this->assertNull(FoodName::parse('Свинина')->penaltyFor(FoodName::parse('Язык свиной отварной'), true));
        $this->assertNull(FoodName::parse('Яйцо варёное')->penaltyFor(FoodName::parse('Белок вареного яйца'), true));
        $this->assertNull(FoodName::parse('Масло сливочное')->penaltyFor(FoodName::parse('топленое сливочное масло'), true));
        $this->assertNull(FoodName::parse('Кофе')->penaltyFor(FoodName::parse('Кофе латте'), true));
        $this->assertSame(FoodName::UNSAID_WORD, FoodName::parse('Яйцо')->penaltyFor(FoodName::parse('Яйцо куриное'), true));
    }

    public function test_a_word_made_from_a_food_the_database_names_is_another_dish(): void
    {
        $foods = FoodName::foodWords(['Грибы', 'Суп грибной', 'Курица']);
        $said = FoodName::parse('Суп куриный');
        $mushroom = FoodName::parse('Суп грибной с курицей');

        $this->assertArrayHasKey('гриб', $foods);
        $this->assertSame(FoodName::UNSAID_WORD, $said->penaltyFor($mushroom, true));
        $this->assertNull($said->penaltyFor($mushroom, true, $foods));
        $this->assertSame(FoodName::UNSAID_WORD, FoodName::parse('Яйцо')->penaltyFor(FoodName::parse('Яйцо куриное'), true, $foods));
    }

    public function test_the_fat_content_must_be_the_one_said(): void
    {
        $said = FoodName::parse('Творог 5%');

        $this->assertSame(0, $said->penaltyFor(FoodName::parse('Творог 5%'), true));
        $this->assertNull($said->penaltyFor(FoodName::parse('Творог 9%'), true));
        $this->assertSame(FoodName::UNSAID_NUMBER, FoodName::parse('Молоко')->penaltyFor(FoodName::parse('Молоко 2,5%'), true));
    }

    public function test_a_kind_word_said_with_the_sort_may_be_left_out(): void
    {
        $this->assertSame(FoodName::UNSAID_NUMBER, FoodName::parse('Орехи кешью')->penaltyFor(FoodName::parse('Кешью'), true));
        $this->assertSame('кеш', FoodName::parse('Орехи кешью')->queryText());
        $this->assertNull(FoodName::parse('Орехи')->penaltyFor(FoodName::parse('Кешью'), true));
    }

    public function test_the_search_text_holds_the_stems_and_numbers(): void
    {
        $this->assertSame('кур грудк сыр', FoodName::parse('Куриная грудка запеченная с сыром')->searchText());
        $this->assertSame('молок нытвенск 2.5', FoodName::parse('Молоко 2,5% Нытвенское')->searchText());
    }
}
