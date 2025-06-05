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
Analyze the text: ":text". Output only the list of products with their amount in grams. If the amount is not specified, use an average weight or portion. The output format must strictly follow the example below, where each product is followed by a semicolon:

Example:
Potato - 100 grams;
Tomato - 120 grams;
Chicken221 - 200 grams;

If the text contains no products, output: 'no products'.

Important:
- All quantities must be in grams.
- After each product, add a semicolon.
- Do not add any extra information besides the list of products.
- A product may contain letters and digits (e.g., chicken221 or курица два два один). Keep the full product names unchanged.
- If a product has descriptive words (e.g., boiled potato), move the description after the name (e.g., 'boiled potato' → 'potato boiled').
- Ensure each product and its amount are separated by a dash and spaces, as in the example.
- Do not change the original product name, even if it contains digits or non-standard characters.
- If the user says they ate two units of something, for example, two candies, the weight of both candies should be displayed as a single product.
- If the user says they ate or drank zero point five beer (0.5), you should also take this into account and add it to the output text.

Examples of input text and the expected output:

1. Input text: 'I ate 100 grams of potatoes and a tomato.'
   Expected output:
   Potatoes - 100 grams;
   Tomato - 120 grams;

2. Input text: 'I ate 100 grams of potatoes, a tomato, and chicken221.'
   Expected output:
   Potatoes - 100 grams;
   Tomato - 120 grams;
   Chicken221 - 200 grams;

3. Input text: 'I ate 100 grams of potatoes, a tomato, and chicken two two one.'
   Expected output:
   Potatoes - 100 grams;
   Tomato - 120 grams;
   Chicken two two one - 200 grams;

4. Input text: 'I ate boiled potato'
   Expected output:
   Potato boiled - 200 grams;

5. Input text: 'I haven't eaten anything today.'
   Expected output:
   no products

 6. Input text: 'I ate two eggs'
    Expected output:
    Egg - 120 grams;

 7. Input text: "I ate two candies"
    Expected output:
    Candy – 20 grams;

 8. Input text: "I drank zero point five beer"
    Expected output:
    Beer – 500 grams;
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
];
