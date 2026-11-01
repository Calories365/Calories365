<?php

return [
    'parts' => [
        'morning' => 'breakfast',
        'dinner' => 'lunch',
        'supper' => 'dinner',
    ],
    'factors' => [
        'proteins' => 'protein :percent',
        'fats' => 'fat :percent',
        'fibers' => 'vegetables, fibre :percent',
        'evening' => 'time of day :percent',
        'sleep' => 'short sleep :percent',
        'exercise' => 'exercise before :percent',
        'walk' => 'moving after the meal :percent',
    ],
    'tips' => [
        'no_food' => 'Log food and movement: the chart will show what lifts sugar and insulin.',
        'walk' => 'Sugar rose most after :meal. A 10 minute walk after each meal lowered sugar after meals by 12%, after dinner by 22% (Reynolds, 2016).',
        'dip' => 'After :meal sugar will drop below its usual level around :time. Such dips predict hunger and a snack in the next hours (Wyatt, 2021).',
        'fast_meal' => 'The carbohydrates of :meal are fast and there is little protein. Protein and vegetables eaten first lower the sugar peak: by 29–37% in people with type 2 diabetes (Shukla, 2015).',
        'evening' => 'Late food lifts sugar more: the same meal at 8 pm gives 17% more than at 8 am (Morris, 2015).',
        'sleep' => ':hours h of sleep. After a short night the same food lifts sugar higher: after 4 hours of sleep insulin sensitivity is about 25% lower (Donga, 2010).',
        'fibre' => 'Fibre :grams g of :target g a day (EFSA): vegetables, legumes, whole grains, berries.',
        'protein' => 'Protein :grams g, :per_kg g per kg of body weight; adults need from 0.83 g/kg (EFSA).',
        'load' => 'The glycemic load of the day is :load, which is high (over 120). Swap some fast carbohydrates for slow ones: buckwheat, legumes, wholegrain bread.',
    ],
];
