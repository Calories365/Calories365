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
        'exercise' => 'moving before: insulin :percent',
        'walk' => 'moving after the meal :percent',
    ],
    'tips' => [
        'no_food' => 'Log food and movement: the chart will show what lifts sugar and insulin.',
        'walk' => 'Sugar rose most after :meal. A walk begun right after a meal lowers sugar more than the same effort before it (Engeroff, 2023). A 10 minute walk after each meal lowered sugar by 12%, after dinner by 22%, in people with type 2 diabetes (Reynolds, 2016).',
        'dip' => 'After :meal sugar will drop below its usual level around :time. Such dips predict hunger and a snack in the next hours (Wyatt, 2021).',
        'fast_meal' => 'The carbohydrates of :meal are fast and there is little protein. Protein and vegetables eaten first lower the sugar peak: by 29–37% in people with type 2 diabetes (Shukla, 2015).',
        'protein_insulin' => 'Part of the insulin of the day comes from protein and fat, grey on the chart. Only the part from carbohydrates is colored: protein raises insulin along with glucagon, and sugar does not fall (Nuttall & Gannon, 2011).',
        'evening' => 'Late food lifts sugar more: the same meal at 8 pm gives 17% more than at 8 am (Morris, 2015).',
        'sleep' => ':hours h of sleep. After a short night the same food lifts sugar higher: after 4 hours of sleep insulin sensitivity is about 25% lower (Donga, 2010).',
        'fibre' => 'Fibre :grams g of :target g a day (EFSA): vegetables, legumes, whole grains, berries.',
        'protein' => 'Protein :grams g, :per_kg g per kg of body weight; adults need from 0.83 g/kg (EFSA).',
        'load' => 'The glycemic load of the day is :load, which is high (over 120). Swap some fast carbohydrates for slow ones: buckwheat, legumes, wholegrain bread.',
    ],
];
