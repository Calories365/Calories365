<?php

namespace App\Services;

/**
 * A food name taken apart by words: the food itself, how it was cooked and what
 * was added to it. Russian, Ukrainian and English names are read alike.
 *
 * A name the user said and a product name from the database go through the same
 * rules, which is what lets them be compared without a model: "Нога куриная
 * запечённая" and "Куриная ножка в духовке" both come out as the stems
 * "кур ножк", cooked "baked". Words are cut to a stem by their endings, and
 * synonyms share one stem. The search index keeps the stems and the cooking
 * method of every product (ProductTranslation::toSearchableArray), so changing
 * the rules here means importing the index again.
 */
class FoodName
{
    public const AS_SOLD = 'none';

    public const RAW = 'raw';

    public const BOILED = 'boiled';

    public const STEAMED = 'steamed';

    public const FRIED = 'fried';

    public const DEEP_FRIED = 'deep_fried';

    public const BAKED = 'baked';

    public const GRILLED = 'grilled';

    public const STEWED = 'stewed';

    public const SMOKED = 'smoked';

    public const DRIED = 'dried';

    public const SALTED = 'salted';

    public const CANNED = 'canned';

    /**
     * Cooked some way the name does not tell: a dish of a food eaten only cooked.
     */
    public const COOKED = 'cooked';

    /**
     * Adjective and participle endings of both languages, "вареный", "варёная", "варена".
     */
    private const ENDING = '(?:ый|ий|ая|яя|ое|ее|ые|ие|ой|ого|его|ому|ему|ую|юю|ых|их|ым|им|ыми|ими|ом|ем|ій|ої|ою|а|е|і|у)';

    /**
     * Endings of the cases other than the nominative: "с жареным луком".
     */
    private const OTHER_CASE = '(?:ым|им|ыми|ими|ой|ою|ей|ею|ом|ем|ого|его|ому|ему|ых|их|ої)';

    /**
     * Words that name a cooking method; {E} is an adjective ending.
     */
    private const COOKING_WORDS = [
        self::BOILED => [
            '/^(?:от|с|раз|при|від|з|роз)?вар(?:ен|ін)?н?{E}$/u',
            '/^(?:отв|вар|вкрутую|всмятку|boiled|cooked|poached)$/u',
        ],
        self::STEAMED => [
            '/^(?:за)?парен{1,2}{E}$/u',
            '/^паров(?:ой|ая|ое|ые|ого|ую|ых|ий|а|е|і)$/u',
            '/^steamed$/u',
        ],
        self::FRIED => [
            '/^(?:об|под|за|пере|під)?жар(?:ен)?н?{E}$/u',
            '/^(?:за|під|об|пере)?смажен{E}$/u',
            '/^(?:жар|fried)$/u',
        ],
        self::DEEP_FRIED => [
            '/^(?:фри|fries)$/u',
        ],
        self::BAKED => [
            '/^(?:за)?печен{1,2}{E}$/u',
            '/^(?:baked|roasted|roast)$/u',
        ],
        self::GRILLED => [
            '/^(?:гриль|гриле|грилі|grill|grilled)$/u',
        ],
        self::STEWED => [
            '/^(?:за|по|вы)?тушен{1,2}{E}$/u',
            '/^(?:за|по)?тушкован{E}$/u',
            '/^(?:stewed|braised)$/u',
        ],
        self::SMOKED => [
            '/^(?:сыро|сиро|горяче|холодно)?копчен{1,2}{E}$/u',
            '/^(?:копчения|копчення|smoked)$/u',
        ],
        self::DRIED => [
            '/^(?:вы|за|под)?сушен{1,2}{E}$/u',
            '/^(?:сыро|сиро)?вялен{1,2}{E}$/u',
            '/^сух(?:ой|ая|ое|ие|ого|ую|их|им|ий|а|е|і|ої)$/u',
            '/^(?:dried|dry|jerky|instant)$/u',
        ],
        self::SALTED => [
            '/^(?:слабо|мало)?солен{1,2}{E}$/u',
            '/^(?:слабо|мало)?сольн(?:ый|ая|ое|ые|ого|ую|ий|а|е|і)$/u',
            '/^(?:слабо|мало)?солон{E}$/u',
            '/^маринован{1,2}{E}$/u',
            '/^квашен{1,2}{E}$/u',
            '/^(?:salted|pickled|cured|marinated)$/u',
        ],
        self::CANNED => [
            '/^консервирован{1,2}{E}$/u',
            '/^консервован{E}$/u',
            '/^(?:консервы|canned|tinned)$/u',
        ],
        self::RAW => [
            '/^сыр(?:ой|ая|ое|ые|ого|ую|ых|ым|ыми)$/u',
            '/^сир(?:ий|е|і|ого|ої|ою|им|их)$/u',
            '/^свеж(?:ий|ая|ее|ие|его|ую|их|ем)$/u',
            '/^свіж(?:ий|а|е|і|ого|ої|у|ою|их)$/u',
            '/^(?:raw|uncooked|fresh)$/u',
        ],
    ];

    /**
     * Cooking methods said in several words.
     */
    private const COOKING_PHRASES = [
        'на пару' => self::STEAMED,
        'на парі' => self::STEAMED,
        'на гриле' => self::GRILLED,
        'на грилі' => self::GRILLED,
        'на углях' => self::GRILLED,
        'на вугіллі' => self::GRILLED,
        'на мангале' => self::GRILLED,
        'на мангалі' => self::GRILLED,
        'на сковороде' => self::FRIED,
        'на сковородке' => self::FRIED,
        'на сковороді' => self::FRIED,
        'во фритюре' => self::DEEP_FRIED,
        'в фритюре' => self::DEEP_FRIED,
        'у фритюрі' => self::DEEP_FRIED,
        'в фритюрі' => self::DEEP_FRIED,
        'в духовке' => self::BAKED,
        'в духовці' => self::BAKED,
        'у духовці' => self::BAKED,
        'в фольге' => self::BAKED,
        'в фользі' => self::BAKED,
        'у фользі' => self::BAKED,
        'быстрого приготовления' => self::DRIED,
        'моментального приготовления' => self::DRIED,
        'швидкого приготування' => self::DRIED,
        'для варки' => self::DRIED,
        'для варіння' => self::DRIED,
        'без варки' => self::DRIED,
        'без варіння' => self::DRIED,
        'холодного копчения' => self::SMOKED,
        'горячего копчения' => self::SMOKED,
        'холодного копчення' => self::SMOKED,
        'гарячого копчення' => self::SMOKED,
        'в собственном соку' => self::CANNED,
        'у власному соку' => self::CANNED,
        'в власному соку' => self::CANNED,
    ];

    /**
     * Words after which the rest of the name is something added to the food, or
     * what it is made of: "Казинак из семечек" is not seeds.
     */
    private const CONNECTORS = ['с', 'со', 'и', 'з', 'із', 'зі', 'та', 'й', 'with', 'and', 'in', 'в', 'во', 'у', 'на', 'по', 'из', 'from', '(', '+', '&'];

    /**
     * Words that carry nothing to compare.
     */
    private const SKIPPED = ['для', 'от', 'of', 'the', 'an'];

    /**
     * What a food is cooked in: "на воде", "на молоке" mean the food was boiled.
     */
    private const LIQUIDS = ['вод', 'молок', 'молоц', 'сливк', 'вершк', 'бульон', 'бульйон', 'кефир', 'water', 'milk'];

    /**
     * Words that mean a boiled grain when no method is named: "Каша гречневая".
     */
    private const PORRIDGE = ['каш', 'porridge'];

    /**
     * Endings cut from Russian and Ukrainian words, longest first.
     */
    private const ENDINGS = [
        'иями', 'ями', 'ами', 'ого', 'его', 'ому', 'ему', 'ыми', 'ими', 'иях', 'ией',
        'ая', 'яя', 'ое', 'ее', 'ые', 'ие', 'ый', 'ий', 'ой', 'ей', 'ую', 'юю', 'ом', 'ем', 'ым', 'им', 'ых', 'их',
        'ам', 'ям', 'ах', 'ях', 'ов', 'ев', 'ью', 'ья', 'ье', 'ьи', 'ия', 'ій', 'ої', 'ою',
        'ею', 'ів', 'їв', 'а', 'я', 'ы', 'и', 'о', 'е', 'у', 'ю', 'й', 'ь', 'і', 'ї', 'є',
    ];

    /**
     * Whole words with a stem of their own, read before the endings are cut.
     */
    private const WORD_STEMS = [
        'печень' => 'печенк', 'печени' => 'печенк', 'печенью' => 'печенк', 'печенка' => 'печенк',
        'печенки' => 'печенк', 'печінка' => 'печенк', 'печінки' => 'печенк', 'liver' => 'печенк',
        'печенье' => 'печенье', 'печенья' => 'печенье', 'печеньем' => 'печенье', 'печиво' => 'печенье',
        'печива' => 'печенье', 'хлопья' => 'хлоп', 'хлопьев' => 'хлоп', 'пластівці' => 'хлоп',
    ];

    /**
     * Synonyms: the stem on the left is read as the stem on the right.
     */
    private const SYNONYMS = [
        'ног' => 'ножк', 'окорочок' => 'ножк', 'окорочк' => 'ножк', 'голен' => 'ножк', 'ніжк' => 'ножк',
        'гомілк' => 'ножк', 'leg' => 'ножк', 'drumstick' => 'ножк',
        'курин' => 'кур', 'куриц' => 'кур', 'курк' => 'кур', 'куряч' => 'кур', 'курятин' => 'кур',
        'курочк' => 'кур', 'chicken' => 'кур',
        'картошк' => 'картофел', 'картопл' => 'картофел', 'картофельн' => 'картофел', 'картоплян' => 'картофел',
        'potato' => 'картофел',
        'гречнев' => 'гречк', 'гречишн' => 'гречк', 'гречан' => 'гречк', 'buckwheat' => 'гречк',
        'рисов' => 'рис', 'rice' => 'рис',
        'макаронн' => 'макарон', 'pasta' => 'макарон', 'macaroni' => 'макарон',
        'спагет' => 'спагетт', 'spaghetti' => 'спагетт',
        'овсянк' => 'овсян', 'вівсянк' => 'овсян', 'вівсян' => 'овсян', 'oatmeal' => 'овсян', 'oat' => 'овсян',
        'пшенн' => 'пшен', 'пшенк' => 'пшен', 'пшон' => 'пшен', 'пшонян' => 'пшен', 'millet' => 'пшен',
        'перловк' => 'перлов', 'манн' => 'манк',
        'говяж' => 'говядин', 'ялович' => 'говядин', 'яловичин' => 'говядин', 'beef' => 'говядин',
        'свин' => 'свинин', 'свиняч' => 'свинин', 'pork' => 'свинин',
        'индюшк' => 'индейк', 'индюшатин' => 'индейк', 'індичк' => 'индейк', 'індичин' => 'индейк', 'turkey' => 'индейк',
        'теляч' => 'телятин', 'veal' => 'телятин', 'баран' => 'баранин', 'lamb' => 'баранин',
        'філ' => 'фил', 'fillet' => 'фил', 'filet' => 'фил', 'breast' => 'грудк',
        'крылышк' => 'крыл', 'крильц' => 'крыл', 'крил' => 'крыл', 'wing' => 'крыл',
        'бедрышк' => 'бедр', 'стегн' => 'бедр', 'thigh' => 'бедр',
        'egg' => 'яйц', 'мясн' => 'мяс', 'мясц' => 'мяс', 'meat' => 'мяс',
        'риб' => 'рыб', 'fish' => 'рыб', 'семг' => 'лосос', 'salmon' => 'лосос',
        'чечевичн' => 'чечевиц', 'lentil' => 'чечевиц', 'bean' => 'фасол', 'квасол' => 'фасол',
        'chickpea' => 'нут', 'noodle' => 'лапш', 'локшин' => 'лапш', 'steak' => 'стейк', 'shrimp' => 'креветк',
        'огурец' => 'огурц', 'огірок' => 'огірк', 'перец' => 'перц', 'пирожок' => 'пирожк', 'пиріжок' => 'пирожк',
        'фасолев' => 'фасол', 'квасолян' => 'фасол', 'горохов' => 'горох', 'кукурузн' => 'кукуруз', 'кукурудзян' => 'кукурудз',
        'сердечк' => 'сердц', 'серц' => 'сердц', 'серденьк' => 'сердц', 'шея' => 'шейк', 'шеи' => 'шейк',
        'картошечк' => 'картофел', 'макарошк' => 'макарон', 'огурчик' => 'огурц', 'огірочк' => 'огірк', 'огірочок' => 'огірк',
        'помидорк' => 'помидор', 'помидорчик' => 'помидор', 'яичк' => 'яйц', 'хлебушк' => 'хлеб', 'супчик' => 'суп',
        'кашк' => 'каш', 'сосисочк' => 'сосиск', 'колбаск' => 'колбас', 'грудочк' => 'грудк', 'рыбк' => 'рыб',
        'капустк' => 'капуст', 'морковк' => 'морков', 'лучок' => 'лук', 'лучк' => 'лук', 'чаек' => 'чай', 'кофеек' => 'коф',
        'кино' => 'киноа', 'quinoa' => 'киноа', 'кіноа' => 'киноа', 'couscous' => 'кускус', 'bulgur' => 'булгур',
    ];

    /**
     * Foods that are eaten only after cooking: grains, pasta, legumes, potatoes,
     * meat and fish. Said without a method they mean the cooked food, and a
     * product of theirs named without one is the dry or raw food.
     */
    private const COOKED_FOODS = [
        'гречк', 'рис', 'макарон', 'спагетт', 'лапш', 'вермишел', 'перлов', 'пшен', 'овсян', 'булгур',
        'киноа', 'кускус', 'ячк', 'ячнев', 'манк', 'полб', 'фасол', 'чечевиц', 'нут', 'горох', 'маш',
        'картофел', 'мяс', 'кур', 'ножк', 'грудк', 'бедр', 'крыл', 'фил', 'говядин', 'свинин', 'телятин',
        'баранин', 'конин', 'индейк', 'утк', 'качк', 'кролик', 'печенк', 'сердц', 'сердечк', 'желудк',
        'язык', 'шейк', 'ребр', 'ребрышк', 'вырезк', 'стейк', 'отбивн', 'рыб', 'лосос', 'форел', 'треск',
        'минта', 'хек', 'скумбр', 'тилап', 'карп', 'судак', 'щук', 'пангасиус', 'горбуш', 'кет', 'сибас',
        'дорад', 'камбал', 'окун', 'сом', 'толстолоб', 'кальмар', 'креветк', 'миди', 'осьминог',
    ];

    /**
     * Foods of the list above cooked in water: boiled, they hold under 200 kcal
     * in 100 g, the dry food over 300.
     */
    private const GRAINS = [
        'гречк', 'рис', 'макарон', 'спагетт', 'лапш', 'вермишел', 'перлов', 'пшен', 'овсян', 'булгур',
        'киноа', 'кускус', 'ячк', 'ячнев', 'манк', 'полб', 'фасол', 'чечевиц', 'нут', 'горох', 'маш', 'картофел',
        'кукуруз', 'кукурудз',
    ];

    /**
     * Words of a kind a product name may leave out when the sort is named:
     * "Кешью" for "Орехи кешью", "Хек" for "Рыба хек".
     */
    private const KIND_WORDS = ['орех', 'орешк', 'горіх', 'горішк', 'ягод', 'фрукт', 'сухофрукт', 'рыб', 'овощ', 'овоч', 'nut', 'berry', 'fish'];

    /**
     * Offal: a food of its own, not the meat it is named after ("Язык свиной").
     */
    private const OFFAL = ['язык', 'печенк', 'сердц', 'желудк', 'шлунк', 'почк', 'мозг', 'рубц', 'вым'];

    /**
     * Foods sold ready that take a word of a food above: "Колбаса куриная",
     * "Хлебцы рисовые", "Мука гречневая", "Яйцо куриное".
     */
    private const READY_FOODS = [
        'колбас', 'ковбас', 'сосиск', 'сардельк', 'ветчин', 'шинк', 'буженин', 'карбонад', 'балык', 'балик',
        'бекон', 'сал', 'паштет', 'тушенк', 'тушонк', 'консерв', 'фарш', 'котлет', 'тефтел', 'фрикадельк',
        'биточк', 'зраз', 'голубц', 'пельмен', 'вареник', 'мант', 'хинкал', 'чебурек', 'беляш', 'самс',
        'пирожк', 'пирог', 'пиріг', 'блин', 'блинчик', 'млинц', 'млинец', 'олад', 'оладк', 'сырник', 'сирник',
        'запеканк', 'омлет', 'яичниц', 'суп', 'бульон', 'бульйон', 'борщ', 'солянк', 'плов', 'ризотт', 'паэл',
        'лазан', 'пицц', 'бургер', 'гамбургер', 'чизбургер', 'сэндвич', 'сендвіч', 'бутерброд', 'шаурм',
        'шаверм', 'ролл', 'суш', 'салат', 'винегрет', 'олив', 'пюр', 'чипс', 'крекер', 'сухар', 'сухарик',
        'гренк', 'хлеб', 'хліб', 'хлебц', 'хлібц', 'лаваш', 'батон', 'булк', 'булочк', 'лепешк', 'наггетс',
        'нагетс', 'стрипс', 'соус', 'кетчуп', 'майонез', 'жел', 'холодец', 'холодц', 'студен', 'икр', 'ікр',
        'стручков', 'спаржев', 'топлен', 'фунчоз', 'латт', 'latte', 'капучин', 'cappuccino', 'мокк', 'фрапп', 'белок', 'белк', 'білок', 'білк', 'желток', 'жовток', 'заправк',
        'сельд', 'селедк', 'оселедц', 'шпрот', 'сардин', 'кильк', 'яйц', 'молок', 'кефир', 'йогурт', 'сметан',
        'сливк', 'масл', 'мук', 'борошн', 'крахмал', 'отруб', 'висівк', 'круп', 'хлоп', 'мюсл', 'гранол',
        'печенье', 'вафл', 'торт', 'пирожн', 'кекс', 'конфет', 'цукерк', 'шоколад', 'батончик', 'морожен',
        'морозив', 'протеин', 'протеїн', 'гейнер', 'смес', 'суміш', 'порошк', 'порошок', 'попкорн', 'сок', 'напит', 'чай', 'коф',
        'пив', 'квас', 'крабов', 'тунец', 'тунц', 'soup', 'salad', 'sandwich', 'burger', 'pizza', 'sausage',
        'ham', 'bacon', 'chip', 'bread', 'cracker', 'flour', 'flake', 'cereal', 'milk', 'yogurt', 'cheese',
        'sauce', 'nugget', 'pie', 'cooky', 'tuna',
    ];

    /**
     * Penalty for a product word the user did not say, a number such as the fat
     * content, and a word in Latin letters in a Russian or Ukrainian name: a brand.
     */
    public const UNSAID_WORD = 2;

    public const UNSAID_NUMBER = 1;

    public const UNSAID_BRAND = 3;

    /**
     * @param  string  $cooking  one of the constants; AS_SOLD when no method is named
     * @param  array<int, string>  $food  stems of the food itself
     * @param  array<int, array<int, string>>  $additions  what was added, a list of stems per part:
     *                                                     "с луком и морковью" is [["лук"], ["морков"]]
     * @param  array<int, string>  $numbers  fat content and the like, "5" of "Творог 5%"
     * @param  array<int, string>  $without  what the food is made without, "соль" of "без соли"
     */
    public function __construct(
        public string $cooking,
        public array $food,
        public array $additions,
        public array $numbers,
        public array $without = [],
    ) {}

    public static function parse(string $name): self
    {
        $text = mb_strtolower($name);
        $text = strtr($text, ['ё' => 'е', "'" => '', '’' => '', 'ʼ' => '', '`' => '']);
        $text = preg_replace('/(\d),(\d)/u', '$1.$2', $text);
        preg_match_all('/\d+(?:\.\d+)?|\p{L}+|[(+&]/u', $text, $matches);
        $words = $matches[0];

        $said = null;
        $implied = null;
        $food = [];
        $additions = [];
        $numbers = [];
        $without = [];
        $connector = null;

        for ($i = 0, $count = count($words); $i < $count; $i++) {
            $word = $words[$i];

            foreach ([3, 2] as $length) {
                $phrase = implode(' ', array_slice($words, $i, $length));
                if (isset(self::COOKING_PHRASES[$phrase])) {
                    $said ??= self::COOKING_PHRASES[$phrase];
                    $i += $length - 1;

                    continue 2;
                }
            }

            if ($word === 'без' || $word === 'without') {
                if (isset($words[$i + 1]) && preg_match('/^\p{L}{2,}$/u', $words[$i + 1])) {
                    $without[] = self::stem($words[++$i]);
                }

                continue;
            }

            if (is_numeric($word)) {
                $numbers[] = (string) (float) $word;

                continue;
            }

            if (in_array($word, self::CONNECTORS, true)) {
                $connector = $word;
                $additions[] = [];

                continue;
            }

            if (in_array($word, self::SKIPPED, true) || mb_strlen($word) < 2) {
                continue;
            }

            $method = self::cookingOf($word);
            if ($method !== null) {
                // In an added part only the nominative is the food's: "запечённая с сыром",
                // but "с жареным луком" is how the onion was cooked.
                if ($connector === null || ! preg_match('/'.self::OTHER_CASE.'$/u', $word)) {
                    $said ??= $method;
                }

                continue;
            }

            $stem = self::stem($word);

            if (in_array($stem, self::PORRIDGE, true)) {
                $implied ??= self::BOILED;
            }

            if ($connector === null) {
                $food[] = $stem;

                continue;
            }

            if ($connector === 'на' && in_array($stem, self::LIQUIDS, true)) {
                $implied ??= self::BOILED;

                // Water adds nothing: "на воде" is how the grain was boiled.
                if ($stem === 'вод' || $stem === 'water') {
                    continue;
                }
            }

            $additions[array_key_last($additions)][] = $stem;
        }

        // "Каша гречневая" is buckwheat; "Каша" alone stays a porridge.
        if (array_intersect($food, self::COOKED_FOODS) !== []) {
            $food = array_diff($food, self::PORRIDGE);
        }

        $food = array_values(array_unique($food));
        $parts = [];
        foreach ($additions as $part) {
            $part = array_values(array_unique(array_diff($part, $food)));
            if ($part !== []) {
                $parts[] = $part;
            }
        }

        // "Гречка с курицей" is a dish, so the buckwheat in it is cooked.
        if ($parts !== [] && array_intersect($food, self::COOKED_FOODS) !== [] && array_intersect($food, self::READY_FOODS) === []) {
            $implied ??= self::COOKED;
        }

        return new self(
            $said ?? $implied ?? self::AS_SOLD,
            $food,
            $parts,
            array_values(array_unique($numbers)),
            array_values(array_unique($without)),
        );
    }

    /**
     * Stems and numbers as one line, the form the search index keeps.
     */
    public function searchText(): string
    {
        return implode(' ', [...$this->stems(), ...$this->numbers]);
    }

    /**
     * The stems a product must have to be this food: all but a kind word the
     * product may leave out, "орехи" of "Орехи кешью".
     */
    public function queryText(): string
    {
        return implode(' ', [...array_diff($this->stems(), $this->optionalStems()), ...$this->numbers]);
    }

    /**
     * Kind words of the food a product may leave out: only next to a sort.
     *
     * @return array<int, string>
     */
    private function optionalStems(): array
    {
        $kinds = array_intersect($this->food, self::KIND_WORDS);

        return count($kinds) < count($this->food) ? array_values($kinds) : [];
    }

    /**
     * Stems of the food and of everything added to it.
     *
     * @return array<int, string>
     */
    public function stems(): array
    {
        return array_values(array_unique([...$this->food, ...array_merge(...$this->additions)]));
    }

    /**
     * Whether the food is eaten only after cooking: grains, meat, fish.
     */
    public function needsCooking(): bool
    {
        return array_intersect($this->food, self::COOKED_FOODS) !== []
            && array_intersect($this->food, self::READY_FOODS) === [];
    }

    /**
     * Whether the food is a grain, pasta, a legume or potatoes.
     */
    public function isGrain(): bool
    {
        return array_intersect($this->food, self::GRAINS) !== []
            && array_intersect($this->food, self::READY_FOODS) === [];
    }

    /**
     * Cooking methods a product may have to be this food, each with its tier:
     * 0 is the same, a higher tier is a close substitute.
     *
     * A method that was named allows itself and a method that leaves the food
     * much the same: boiled and steamed, baked and grilled. With no method named,
     * a food eaten only after cooking was cooked: boiled first, fried or smoked
     * last ("Стейк" is fried, "Скумбрия" often smoked); anything else is as sold.
     *
     * @return array<string, int>
     */
    public function acceptedCooking(): array
    {
        return match ($this->cooking) {
            self::BOILED => [self::BOILED => 0, self::STEAMED => 1, self::COOKED => 1],
            self::STEAMED => [self::STEAMED => 0, self::BOILED => 1, self::COOKED => 1],
            self::BAKED => [self::BAKED => 0, self::GRILLED => 1, self::COOKED => 1],
            self::GRILLED => [self::GRILLED => 0, self::BAKED => 1, self::COOKED => 1],
            self::STEWED => [self::STEWED => 0, self::COOKED => 1],
            self::FRIED => [self::FRIED => 0, self::DEEP_FRIED => 1, self::COOKED => 2],
            self::DEEP_FRIED => [self::DEEP_FRIED => 0, self::FRIED => 1],
            self::COOKED => [self::COOKED => 0, self::BOILED => 0, self::STEAMED => 0, self::STEWED => 0, self::BAKED => 1, self::GRILLED => 1],
            self::RAW => [self::RAW => 0, self::AS_SOLD => 0],
            self::DRIED => $this->needsCooking() ? [self::DRIED => 0, self::AS_SOLD => 0] : [self::DRIED => 0],
            self::AS_SOLD => $this->needsCooking()
                ? [self::BOILED => 0, self::STEAMED => 1, self::COOKED => 1, self::BAKED => 2, self::GRILLED => 2, self::STEWED => 2, self::CANNED => 3, self::FRIED => 3, self::SMOKED => 3]
                : [self::AS_SOLD => 0, self::RAW => 0],
            default => [$this->cooking => 0],
        };
    }

    /**
     * How far the product is from the food the user named, or null when it is a
     * different food: a word the user said is missing from it, it adds something
     * the user named nothing of, it has another fat content, or a word the user did
     * not say is a food of its own ("Хлебцы апельсин" for "Апельсин").
     *
     * Other words the user did not say leave it the same food, a brand or a sort,
     * and only count against it: "Макароны Barilla отварные" for "Макароны".
     *
     * @param  bool  $latinIsBrand  whether a word in Latin letters is a brand: true but in English
     * @param  array<string, true>  $foods  stems of the foods the database names in one word
     */
    public function penaltyFor(self $product, bool $latinIsBrand, array $foods = []): ?int
    {
        $said = $this->stems();
        $penalty = 0;
        $optional = $this->optionalStems();

        foreach ($said as $stem) {
            if (self::containsStem($product->stems(), $stem)) {
                continue;
            }

            // "Кешью" is the "Орехи кешью" the user said.
            if (in_array($stem, $optional, true)) {
                $penalty += self::UNSAID_NUMBER;

                continue;
            }

            return null;
        }

        if (array_diff($this->numbers, $product->numbers) !== []) {
            return null;
        }

        // The product itself must be something the user said, not only what it is made of or with.
        if ($product->food !== [] && array_filter($product->food, static fn (string $stem) => self::containsStem($said, $stem)) === []) {
            return null;
        }

        $unsaid = [];
        foreach ($product->food as $stem) {
            if (! self::containsStem($said, $stem)) {
                $unsaid[] = $stem;
            }
        }

        foreach ($product->additions as $part) {
            $unsaidOfPart = array_filter($part, static fn (string $stem) => ! self::containsStem($said, $stem));

            if (count($unsaidOfPart) === count($part)) {
                return null;
            }

            array_push($unsaid, ...$unsaidOfPart);
        }

        $penalty += self::UNSAID_NUMBER * count(array_diff($product->numbers, $this->numbers));

        foreach ($unsaid as $stem) {
            if (self::isFoodOfItsOwn($stem, $foods)) {
                return null;
            }

            $penalty += $latinIsBrand && preg_match('/^[a-z]/', $stem) ? self::UNSAID_BRAND : self::UNSAID_WORD;
        }

        foreach ($product->without as $stem) {
            if (! in_array($stem, $this->without, true)) {
                $penalty += self::UNSAID_WORD;
            }
        }

        return $penalty;
    }

    /**
     * The food stems of one-word product names: what the database knows as a
     * food of its own, "Грибы", "Фасоль".
     *
     * @param  iterable<int, string>  $names
     * @return array<string, true>
     */
    public static function foodWords(iterable $names): array
    {
        $foods = [];
        foreach ($names as $name) {
            if (preg_match('/^\p{L}{3,}$/u', trim((string) $name))) {
                foreach (self::parse((string) $name)->food as $stem) {
                    $foods[$stem] = true;
                }
            }
        }

        return $foods;
    }

    /**
     * A word that names a food, a dish or a drink rather than a sort of one, or
     * is made from one: "Суп грибной" is not a chicken soup.
     *
     * Meat and fish words stay sorts, "Яйцо куриное" is an egg.
     *
     * @param  array<string, true>  $foods  stems of the foods the database names in one word
     */
    private static function isFoodOfItsOwn(string $stem, array $foods): bool
    {
        if (self::containsStem(self::READY_FOODS, $stem) || self::containsStem(self::GRAINS, $stem) || in_array($stem, self::OFFAL, true)) {
            return true;
        }

        if (in_array($stem, self::COOKED_FOODS, true)) {
            return false;
        }

        $base = preg_replace('/(?:н|ов|ев|ян|ан|ин)$/u', '', $stem);

        return isset($foods[$stem]) || (mb_strlen($base) >= 3 && isset($foods[$base]));
    }

    private static function cookingOf(string $word): ?string
    {
        foreach (self::COOKING_WORDS as $method => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match(str_replace('{E}', self::ENDING, $pattern), $word)) {
                    return $method;
                }
            }
        }

        return null;
    }

    private static function stem(string $word): string
    {
        if (isset(self::WORD_STEMS[$word])) {
            return self::WORD_STEMS[$word];
        }

        $stem = preg_match('/^[a-z]+$/', $word) ? self::englishStem($word) : self::slavicStem($word);

        return self::SYNONYMS[$stem] ?? $stem;
    }

    private static function slavicStem(string $word): string
    {
        $length = mb_strlen($word);

        foreach (self::ENDINGS as $ending) {
            $cut = mb_strlen($ending);
            if ($length - $cut >= 3 && str_ends_with($word, $ending)) {
                return mb_substr($word, 0, $length - $cut);
            }
        }

        return $word;
    }

    private static function englishStem(string $word): string
    {
        return match (true) {
            strlen($word) > 4 && str_ends_with($word, 'ies') => substr($word, 0, -3).'y',
            (bool) preg_match('/(?:ss|us)$/', $word) => $word,
            (bool) preg_match('/(?:ch|sh|x|o)es$/', $word) => substr($word, 0, -2),
            strlen($word) > 3 && str_ends_with($word, 's') => substr($word, 0, -1),
            default => $word,
        };
    }

    /**
     * @param  array<int, string>  $stems
     */
    private static function containsStem(array $stems, string $stem): bool
    {
        foreach ($stems as $candidate) {
            if (self::sameStem($candidate, $stem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stems match when equal, or a letter apart in words of five letters or more:
     * the endings cut cover most forms, and one letter covers the rest, "огурец"
     * and "огурц", the way the search engine allows one typo.
     */
    private static function sameStem(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        if (min(mb_strlen($a), mb_strlen($b)) < 5) {
            return false;
        }

        $encoding = 'Windows-1251';

        return levenshtein(mb_convert_encoding($a, $encoding, 'UTF-8'), mb_convert_encoding($b, $encoding, 'UTF-8')) <= 1;
    }
}
