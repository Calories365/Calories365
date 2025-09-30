<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calories365 bot lang
    |--------------------------------------------------------------------------
    |
    |
    */

    'auth_required' => 'You must be authorized!',
    'error_retrieving_data' => 'An error occurred while retrieving data. Please try again later.',
    'no_entries_for_date' => 'You have no entries for the date *:date*.',
    'no_entries_for_part_of_day' => 'You have no entries :partOfDayText.',
    'your_data_for_date' => 'Your data for *:date*:',
    'breakfast' => 'Breakfast',
    'lunch' => 'Lunch',
    'dinner' => 'Dinner',
    'calories' => 'Calories',
    'proteins' => 'Proteins',
    'fats' => 'Fats',
    'carbohydrates' => 'Carbohydrates',
    'total_for_day' => 'Total for the day',
    'total_for_part_of_day' => 'Total for :partOfDayName',
    'delete' => 'Delete',
    'you_said' => 'You said: ',
    'parameter' => 'Parameter',
    '100g' => '100g',
    'g' => 'g',
    'editing_session_expired' => 'Editing session has expired or does not exist.',
    'product_not_found' => 'The product was not found or the session time has expired.',
    'editing_canceled' => 'Editing canceled.',
    'step_skipped' => 'Step skipped.',
    'please_enter_new_quantity_of_grams' => 'Please enter the new quantity of grams.',
    'please_enter_new_calories' => 'Please enter the new number of calories.',
    'please_enter_new_proteins' => 'Please enter the new number of proteins.',
    'please_enter_new_fats' => 'Please enter the new number of fats.',
    'please_enter_new_carbohydrates' => 'Please enter the new number of carbohydrates.',
    'error_editing_product' => 'An error occurred while editing the product.',
    'exit_edit_mode' => 'Exit edit mode (press save or cancel).',
    'action_canceled_product_list_cleared' => 'Action canceled. Your product list has been cleared.',
    'cancellation_completed' => 'Cancellation completed',
    'product_list_is_empty_or_was_cleared' => 'Your product list is empty or has already been cleared.',
    'list_is_already_empty' => 'The list is already empty',
    'product_removed_from_list' => 'Product removed from the list.',
    'error_deleting_product' => 'Помилка при видаленні продукту.',
    'product_deleted' => 'Продукт видалено.',
    'you_are_editing_product' => 'You are editing the product: *:productName*',
    'please_enter_new_product_name' => 'Please enter the new product name.',
    'save' => 'Save',
    'skip_step' => 'Skip step',
    'cancel' => 'Cancel',
    'invalid_request' => 'Invalid request.',
    'data_saved_you_consumed' => 'The data has been saved, you have consumed',
    'grams' => 'grams',
    'error_processing_data' => 'An error occurred while processing data.',
    'error_generating_data' => 'An error occurred while generating data.',
    'failed_to_get_product_data' => 'Failed to get product data.',
    'cannot_generate_product_data' => 'Sorry, I could not generate data for this product. Try editing it manually.',
    'product_data_updated' => 'Product data updated.',
    'value_too_long' => 'Value is too long',
    'enter_valid_numeric_value_for_grams' => 'Please enter a valid numeric value for grams.',
    'enter_valid_numeric_value_for_calories' => 'Please enter a valid numeric value for calories.',
    'enter_valid_numeric_value_for_proteins' => 'Please enter a valid numeric value for proteins.',
    'enter_valid_numeric_value_for_fats' => 'Please enter a valid numeric value for fats.',
    'enter_valid_numeric_value_for_carbohydrates' => 'Please enter a valid numeric value for carbohydrates.',
    'please_choose_your_language' => 'Please choose your language',
    'language_set_english' => 'Your language has been set to English.',
    'language_set_russian' => 'Your language has been set to Russian.',
    'language_set_ukrainian' => 'Your language has been set to Ukrainian.',
    'invalid_or_used_code' => 'Invalid or used code. Please register again.',
    'seems_you_are_new' => 'It looks like you are new here. To link your account, use the "Connect" link from your personal account (https://calculator.calories365.com?lang=en).',
    'error_occurred' => 'An error occurred: ',
    'incomplete_product_info' => 'Product information is incomplete.',
    'save_products_for' => 'Save products for:',
    'products_not_found' => 'No products found.',
    'failed_to_recognize_audio_message' => 'Failed to recognize audio message.',
    'not_an_audio_message_received' => 'Not an audio message received.',
    'changes_saved' => 'Changes saved.',
    'changes_canceled' => 'Changes canceled.',
    'message_not_modified' => 'Message not modified, no update required.',
    'search' => 'Search',
    'generate_with_ai' => 'Generate with AI',
    'edit' => 'Edit',
    'you_must_be_authorized' => 'You must be authorized!',
    'no_entries_remain' => "There's no products left",
    'subscription_required_message' => 'Request limit reached, to save meals without restrictions — purchase premium in the personal account (https://calculator.calories365.com?lang=en)',
    'prompt_analyze_food_intake' => <<<'EOT'
Analyze the text: ":text". STRICTLY extract from it a list of foods/dishes with amounts in grams. Output ONLY the list of foods in the required format. Any other words, explanations, or comments are forbidden.
Output format (mandatory for EACH line):
Product name - WHOLE number grams;
Strict rules:
- If there are NO foods or dishes in the text — output exactly: no products
- Each product starts with a capital letter. Between the name and the number — " - " (space, dash, space). Units — ONLY "grams". Each line ends with a semicolon ";".
- If no amount is specified — use the average portion size (see dictionary below). If a specific amount is specified — it takes priority.
- If the user says “two/three … <product(s)>”, multiply the number of pieces by the weight of ONE unit and output as ONE product with the total weight.
- If the user says “zero five beer”, “0.5 beer”, “half a liter of beer” or similar for drinks — convert liters to grams with density 1 g/ml (1 l = 1000 g). Example: 0.5 l beer → 500 grams.
- Product name may contain letters and numbers (e.g., “chicken221”, “chicken two two one”). Keep it UNCHANGED, except for normalizing the base noun into singular nominative form (e.g., “potatoes” → “Potato”). Any numeric/word suffixes must be preserved as in the input text.
- If there are descriptive words (adjectives) for a food/dish — move the description AFTER the base noun: “boiled potato” → “Potato boiled”, “mashed potato” → “Puree potato”.
- Consider dishes as full-fledged products (e.g., puree, borscht, soup, salad, omelet, etc.). For dishes without a specified weight, apply default portions (see below).
- If the text mentions several identical products with amounts — sum them up into one line (by normalized name).

Dictionary of standard portions (if amount is NOT specified):
- Tomato — 120 grams;
- Egg — 60 grams (per 1 pc.);
- Candy — 10 grams (per 1 pc.);
- Potato boiled — 200 grams;
- Puree potato — 200 grams;
- Beer — 1000 grams per 1 liter (0.5 l = 500 grams);

If the product/dish is NOT in the dictionary and no weight is given — use default: 200 grams.

Examples INPUT/OUTPUT (the output format must match EXACTLY):

1) Input: “I ate 100 grams of potato and a tomato.”
Output:
Potato - 100 grams;
Tomato - 120 grams;

2) Input: “100 grams of potato, a tomato and chicken221.”
Output:
Potato - 100 grams;
Tomato - 120 grams;
Chicken221 - 200 grams;

3) Input: “100 grams of potato, a tomato and chicken two two one.”
Output:
Potato - 100 grams;
Tomato - 120 grams;
Chicken two two one - 200 grams;

4) Input: “boiled potato”
Output:
Potato boiled - 200 grams;

5) Input: “nothing”
Output:
no products

6) Input: “two eggs”
Output:
Egg - 120 grams;

7) Input: “two candies”
Output:
Candy - 20 grams;

8) Input: “zero five beer”
Output:
Beer - 500 grams;

9) Input: “Mashed potato.”
Output:
Puree potato - 200 grams;
EOT,


    'prompt_generate_new_product_data' => <<<'EOT'
Here is a product: ":text". Provide the Calories, Proteins, Fats, and Carbohydrates (macros) for 100 grams of the product.
The output format must strictly follow the example below, where each parameter is followed by a semicolon:

Example:
Calories - 890; Proteins - 0.2; Fats - 100; Carbohydrates - 0;

Important:
- All values must correspond to 100 grams of the product.
- After each parameter, you must add a semicolon.
- Do not add any extra information besides the macros.
- Keep the product name unchanged, even if it contains digits or non-standard characters.
- Make sure each parameter and its value are separated by a dash and spaces, as in the example.
- Also note that a user may mention general or brand-specific product names (e.g., 'Halls' or 'Candy Bob and Snail'). This should also be recognized and returned with the appropriate information.

Example input:
Calories - 890; Proteins - 0.2; Fats - 100; Carbohydrates - 0;
EOT,

    'prompt_choose_relevant_products_part' => <<<'EOT'
Which product best matches the name ":name"? Here are the available options: :productNames.
EOT,

    'prompt_choose_relevant_products_footer' => <<<'EOT'
Return the answer in the following format:
product name1 - id;
product name2 - id;
if there is no suitable product, then the answer should be in the format
product name - (its calories per 100 grams, proteins, fats, carbohydrates);
EOT,

    'data_not_extracted' => 'Failed to extract data.',

    // FeedbackController language phrases
    'default_user_info' => 'Male, 20 years old, 100 kg',
    'male' => 'Male',
    'female' => 'Female',
    'years_old' => 'years old',
    'kg' => 'kg',
    'height_cm' => 'height',
    'cm' => 'cm',
    'goal' => 'goal',
    'daily_calories_norm' => 'daily calories norm',
    'meal_time' => 'Meal time',
    'breakfast' => 'Breakfast',
    'lunch' => 'Lunch',
    'dinner' => 'Dinner',
    'daily_diet' => 'Daily diet',
    'no_products_consumed' => 'no products consumed',
    'failed_to_get_feedback' => 'Failed to get feedback',
    'welcome_guide' => <<<'EOT'
Vide instruction: https://t.me/calories_365/3

Send a voice message to the bot with what you’ve eaten to add an entry to your diary.

Saved products will appear in the web application:
https://calculator.calories365.com?lang=en

You can also watch an introduction video about the bot.

Start tracking your calories easily and conveniently!
EOT,

    'welcome_guide_KNU' => <<<'EOT'
Vide instruction: https://t.me/calories_365/3

Send a voice message to the bot with what you’ve eaten to add an entry to your diary.

Saved products will appear in the web application:
https://calculator.calories365.xyz?lang=en

You can also watch an introduction video about the bot.

Start tracking your calories easily and conveniently!
EOT,
    'menu' => 'Menu',
    'statistics' => 'Statistics',
    'choose_language' => 'Choose language',
    'feedback' => 'Feedback',
    'whole_day' => 'Whole day',
    'send_feedback_email' => 'You can send your feedback to the administrator:  @calories365_admin',
    'generate_with_ai' => 'Generate with AI',
    'get_feedback_for_part_of_day' => <<<'EOT'
You are a nutritionist for :user_info
:part_of_day_info

Meal: :meals_list

Response format:

Short plain text, 2–4 sentences, ≤ 350 characters.

No lists, headings, preambles, emojis, or the words "Summary/Pros/Cons/Advice".

Write clearly for a layperson, in the same language as the input text.

Balance rules and thresholds:

Protein: target per meal ~25–40 g. If absent or low — suggest adding ~20–30 g protein
(≈100–150 g lean chicken/fish, 150–200 g 2% cottage cheese, 2 eggs). If already ≥25 g — do not increase.

Vegetables/fiber: if vegetables ≈0 g — you may add 100–150 g low-calorie vegetables;
if 1–99 g — only 10–20 g greens; if ≥100 g — do not increase.

Complex carbs: target ~20–40 g carbs (≈50–120 g cooked grains/potatoes or 30–50 g whole-grain bread).
If absent — suggest adding a bit; if there is grains/potatoes/bread ≥100 g or enough fruit — do not add.

Fats: if "dry" (lean protein + vegetables) — allow 5–10 g healthy fats (olive oil/a few nuts).
If there are fatty foods (lard, sausages, cheese >20–30 g fat per meal) — recommend reducing their portion.

Excess refined/fast carbs (white bread/sugar/pastries): suggest cutting the amount (e.g., bread to 30–50 g) or replacing part with complex carbs/vegetables without increasing calories.

Recommendation logic:

Give at most TWO specific edits: (a) what to REDUCE when there’s a clear excess and/or (b) what to ADD when there’s a clear deficit.

If everything is balanced by thresholds — don’t suggest changes; simply note the balance.

Output only the feedback text (2–4 sentences).
EOT,
];
