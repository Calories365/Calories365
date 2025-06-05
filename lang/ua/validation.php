<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Поле :attribute повинно бути прийнято.',
    'accepted_if' => 'Поле :attribute повинно бути прийнято, коли :other є :value.',
    'active_url' => 'Поле :attribute повинно бути дійсним URL.',
    'after' => 'Поле :attribute повинно бути датою після :date.',
    'after_or_equal' => 'Поле :attribute повинно бути датою після або дорівнювати :date.',
    'alpha' => 'Поле :attribute повинно містити лише літери.',
    'alpha_dash' => 'Поле :attribute повинно містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute повинно містити лише літери та цифри.',
    'array' => 'Поле :attribute повинно бути масивом.',
    'ascii' => 'Поле :attribute повинно містити лише однобайтові буквено-цифрові символи та символи.',
    'before' => 'Поле :attribute повинно бути датою до :date.',
    'before_or_equal' => 'Поле :attribute повинно бути датою до або дорівнювати :date.',
    'between' => [
        'array' => 'Поле :attribute повинно містити від :min до :max елементів.',
        'file' => 'Розмір :attribute повинен бути від :min до :max кілобайт.',
        'numeric' => 'Поле :attribute повинно бути від :min до :max.',
        'string' => 'Довжина поля :attribute повинна бути від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute повинно бути true або false.',
    'can' => 'Поле :attribute містить несанкціоноване значення.',
    'confirmed' => 'Підтвердження поля :attribute не співпадає.',
    'current_password' => 'Невірний пароль.',
    'date' => 'Поле :attribute повинно бути дійсною датою.',
    'date_equals' => 'Поле :attribute повинно бути датою, рівною :date.',
    'date_format' => 'Поле :attribute повинно відповідати формату :format.',
    'decimal' => 'Поле :attribute повинно мати :decimal десяткових знаків.',
    'declined' => 'Поле :attribute повинно бути відхилене.',
    'declined_if' => 'Поле :attribute повинно бути відхилене, коли :other є :value.',
    'different' => 'Поле :attribute та :other повинні бути різними.',
    'digits' => 'Поле :attribute повинно бути довжиною :digits цифр.',
    'digits_between' => 'Поле :attribute повинно бути від :min до :max цифр.',
    'dimensions' => 'Поле :attribute має неправильні розміри зображення.',
    'distinct' => 'Поле :attribute має дубльоване значення.',
    'doesnt_end_with' => 'Поле :attribute не повинно закінчуватися одним з наступних значень: :values.',
    'doesnt_start_with' => 'Поле :attribute не повинно починатися з одного з наступних значень: :values.',
    'email' => 'Поле :attribute повинно бути дійсною адресою електронної пошти.',
    'ends_with' => 'Поле :attribute повинно закінчуватися одним з наступних значень: :values.',
    'enum' => 'Вибране значення :attribute є недійсним.',
    'exists' => 'Вибране значення :attribute є недійсним.',
    'file' => 'Поле :attribute повинно бути файлом.',
    'filled' => 'Поле :attribute повинно мати значення.',
    'gt' => [
        'array' => 'Поле :attribute повинно містити більше, ніж :value елементів.',
        'file' => 'Розмір :attribute повинен бути більший, ніж :value кілобайт.',
        'numeric' => 'Поле :attribute повинно бути більше, ніж :value.',
        'string' => 'Поле :attribute повинно бути більше, ніж :value символів.',
    ],

    'gte' => [
        'array' => 'Поле :attribute повинно містити :value елементів або більше.',
        'file' => 'Розмір :attribute повинен бути більший або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute повинно бути більше або дорівнювати :value.',
        'string' => 'Поле :attribute повинно бути більше або дорівнювати :value символам.',
    ],

    'hex_color' => 'Поле :attribute повинно бути дійсним шістнадцятковим кольором.',

    'image' => 'Поле :attribute повинно бути зображенням.',

    'in' => 'Вибране значення :attribute є недійсним.',

    'in_array' => 'Поле :attribute повинно існувати в :other.',

    'integer' => 'Поле :attribute повинно бути цілим числом.',

    'ip' => 'Поле :attribute повинно бути дійсною IP-адресою.',

    'ipv4' => 'Поле :attribute повинно бути дійсною IPv4-адресою.',

    'ipv6' => 'Поле :attribute повинно бути дійсною IPv6-адресою.',

    'json' => 'Поле :attribute повинно бути дійсним рядком JSON.',

    'lowercase' => 'Поле :attribute повинно бути в нижньому регістрі.',

    'lt' => [
        'array' => 'Поле :attribute повинно містити менше, ніж :value елементів.',
        'file' => 'Розмір :attribute повинен бути менший, ніж :value кілобайт.',
        'numeric' => 'Поле :attribute повинно бути менше, ніж :value.',
        'string' => 'Поле :attribute повинно бути менше, ніж :value символів.',
    ],

    'lte' => [
        'array' => 'Поле :attribute повинно містити не більше, ніж :value елементів.',
        'file' => 'Розмір :attribute повинен бути менше або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute повинно бути менше або дорівнювати :value.',
        'string' => 'Поле :attribute повинно бути менше або дорівнювати :value символам.',
    ],

    'mac_address' => 'Поле :attribute повинно бути дійсною MAC-адресою.',

    'max' => [
        'array' => 'Поле :attribute не повинно містити більше, ніж :max елементів.',
        'file' => 'Розмір :attribute не повинен бути більше, ніж :max кілобайт.',
        'numeric' => 'Поле :attribute не повинно бути більше, ніж :max.',
        'string' => 'Поле :attribute не повинно бути більше, ніж :max символів.',
    ],

    'max_digits' => 'Поле :attribute не повинно містити більше, ніж :max цифр.',
    'mimes' => 'Поле :attribute повинно бути файлом типу: :values.',
    'mimetypes' => 'Поле :attribute повинно бути файлом типу: :values.',
    'min' => [
        'array' => 'Поле :attribute повинно містити щонайменше :min елементів.',
        'file' => 'Розмір :attribute повинен бути щонайменше :min кілобайт.',
        'numeric' => 'Поле :attribute повинно бути щонайменше :min.',
        'string' => 'Поле :attribute повинно бути щонайменше :min символів.',
    ],

    'min_digits' => 'Поле :attribute повинно містити щонайменше :min цифр.',
    'missing' => 'Поле :attribute повинно бути відсутнім.',
    'missing_if' => 'Поле :attribute повинно бути відсутнім, коли :other є :value.',
    'missing_unless' => 'Поле :attribute повинно бути відсутнім, якщо :other не є :value.',
    'missing_with' => 'Поле :attribute повинно бути відсутнім, коли присутнє :values.',
    'missing_with_all' => 'Поле :attribute повинно бути відсутнім, коли присутні :values.',

    'multiple_of' => 'Поле :attribute повинно бути кратним :value.',
    'not_in' => 'Вибране значення :attribute є недійсним.',
    'not_regex' => 'Формат поля :attribute є недійсним.',
    'numeric' => 'Поле :attribute повинно бути числом.',
    'password' => [
        'letters' => 'Поле :attribute повинно містити щонайменше одну літеру.',
        'mixed' => 'Поле :attribute повинно містити щонайменше одну велику і одну малу літеру.',
        'numbers' => 'Поле :attribute повинно містити щонайменше одну цифру.',
        'symbols' => 'Поле :attribute повинно містити щонайменше один символ.',
        'uncompromised' => 'Зазначений :attribute з`явився у витоку даних. Будь ласка, виберіть інший :attribute.',
    ],
    'present' => 'Поле :attribute повинно бути присутнім.',
    'present_if' => 'Поле :attribute повинно бути присутнім, коли :other є :value.',
    'present_unless' => 'Поле :attribute повинно бути присутнім, якщо :other не є :value.',
    'present_with' => 'Поле :attribute повинно бути присутнім, коли присутнє :values.',
    'present_with_all' => 'Поле :attribute повинно бути присутнім, коли присутні :values.',
    'prohibited' => 'Поле :attribute заборонено.',
    'prohibited_if' => 'Поле :attribute заборонено, коли :other є :value.',
    'prohibited_unless' => 'Поле :attribute заборонено, якщо :other не є в :values.',
    'prohibits' => 'Поле :attribute забороняє :other бути присутнім.',
    'regex' => 'Формат поля :attribute є недійсним.',
    'required' => 'Поле :attribute є обов`язковим.',
    'required_array_keys' => 'Поле :attribute повинно містити записи для: :values.',
    'required_if' => 'Поле :attribute обов`язкове, коли :other є :value.',
    'required_if_accepted' => 'Поле :attribute обов`язкове, коли :other прийнято.',
    'required_unless' => 'Поле :attribute обов`язкове, якщо :other не є в :values.',
    'required_with' => 'Поле :attribute обов`язкове, коли присутнє :values.',
    'required_with_all' => 'Поле :attribute обов`язкове, коли присутні :values.',
    'required_without' => 'Поле :attribute обов`язкове, коли відсутнє :values.',
    'required_without_all' => 'Поле :attribute обов`язкове, коли відсутні всі :values.',
    'same' => 'Поле :attribute повинно співпадати з :other.',
    'size' => [
        'array' => 'Поле :attribute повинно містити :size елементів.',
        'file' => 'Розмір :attribute повинен бути :size кілобайт.',
        'numeric' => 'Поле :attribute повинно бути :size.',
        'string' => 'Поле :attribute повинно бути :size символів.',
    ],

    'starts_with' => 'Поле :attribute повинно починатися однією з наступних: :values.',
    'string' => 'Поле :attribute повинно бути рядком.',
    'timezone' => 'Поле :attribute повинно бути дійсним часовим поясом.',
    'unique' => 'Такий :attribute вже існує.',
    'uploaded' => 'Не вдалося завантажити файл :attribute.',
    'uppercase' => 'Поле :attribute повинно бути у верхньому регістрі.',
    'url' => 'Поле :attribute повинно бути дійсним URL.',
    'ulid' => 'Поле :attribute повинно бути дійсним ULID.',
    'uuid' => 'Поле :attribute повинно бути дійсним UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
