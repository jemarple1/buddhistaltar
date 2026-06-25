<?php

return [
    'avalokiteshvara' => [
        'slug' => 'avalokiteshvara',
        'route_prefix' => '',
        'title' => 'Namo Avalokiteshvaraya!',
        'subtitle' => 'Homage to The One Who Looks Upon Beings with Compassion!',
        'meta_description' => 'Offer a butter lamp and recite the dhāraṇī of Noble Avalokiteśvara.',
        'page_title' => 'Namo Avalokiteshvaraya — Shrine Offerings',
        'deity_image' => 'images/avalokiteshvara.webp',
        'deity_image_class' => 'deity-image deity-image--avalokiteshvara',
        'deity_alt' => 'Avalokiteshvara, homage to the one who looks upon beings with compassion',
        'body_class' => 'shrine-bg',
        'show_sky_clouds' => true,
        'prayer_partial' => 'partials.dharani-sutra',
        'prayer_modal_title' => 'The Dhāraṇī of Noble Avalokiteśvara',
        'read_prayer_label' => 'Read the Sutra',
        'crosslink' => [
            'url' => '/amitabha',
            'image' => 'images/amitabha.webp',
            'label' => 'Visit the Amitābha shrine',
        ],
    ],

    'amitabha' => [
        'slug' => 'amitabha',
        'route_prefix' => 'amitabha',
        'title' => 'Namo Amitabhaya!',
        'subtitle' => 'Homage to the Buddha of Boundless Light!',
        'meta_description' => 'Offer before Amitābha and recite the prayer to the Buddha of Boundless Light.',
        'page_title' => 'Namo Amitabhaya — Shrine Offerings',
        'deity_image' => 'images/amitabha.webp',
        'deity_image_class' => 'deity-image deity-image--amitabha',
        'deity_alt' => 'Amitābha, the Buddha of Boundless Light',
        'body_class' => 'shrine-bg',
        'show_sky_clouds' => true,
        'prayer_partial' => 'partials.amitabha-prayer',
        'prayer_modal_title' => 'Prayer to Buddha Amitābha',
        'read_prayer_label' => 'Read the Prayer',
        'crosslink' => [
            'url' => '/',
            'image' => 'images/avalokiteshvara.webp',
            'label' => 'Visit the Avalokiteśvara shrine',
        ],
    ],
];
