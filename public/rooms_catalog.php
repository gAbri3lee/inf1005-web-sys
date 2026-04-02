<?php

declare(strict_types=1);

function rooms_catalog_image(string $path): string
{
    return 'assets/images/' . ltrim($path, '/');
}

function rooms_catalog_tour_image(string $path): string
{
    return rooms_catalog_image('room_tours/' . ltrim($path, '/'));
}

function rooms_catalog_all(): array
{
    static $rooms = null;

    if ($rooms !== null) {
        return $rooms;
    }

    $rooms = [
        [
            'id' => 101,
            'name' => 'Superior Garden View',
            'description' => 'A calm garden-facing room with bright natural light and a private balcony.',
            'price_per_night' => 219.00,
            'occupancy' => 2,
            'view' => 'Garden',
            'accessible' => false,
            'size' => '42 sqm / 689 sqft',
            'bed' => '1 King',
            'images' => [
                rooms_catalog_image('Suite1.webp'),
                rooms_catalog_image('HotelHomePage.webp'),
                rooms_catalog_image('AboutUs.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('Suite1_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Room overview',
                    'description' => 'Click the bathroom, bed, or lounge area to jump to that point of view.',
                    'image' => rooms_catalog_image('Suite1.webp'),
                    'hotspots' => [
                        [
                            'id' => 'bathroom',
                            'label' => 'Bathroom',
                            'x' => 73,
                            'y' => 43,
                            'target' => 'bathroom',
                        ],
                        [
                            'id' => 'bed',
                            'label' => 'Bed',
                            'x' => 77,
                            'y' => 67,
                            'target' => 'bed',
                        ],
                        [
                            'id' => 'balcony',
                            'label' => 'Lounge',
                            'x' => 39,
                            'y' => 43,
                            'target' => 'balcony',
                        ],
                    ],
                ],
                [
                    'id' => 'bathroom',
                    'title' => 'Bathroom POV',
                    'description' => 'A closer angle facing the bath and vanity zone.',
                    'image' => rooms_catalog_tour_image('Suite1_bathroom_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bed',
                    'title' => 'Bed POV',
                    'description' => 'The view from directly in front of the bed and seating area.',
                    'image' => rooms_catalog_tour_image('Suite1_bed_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'balcony',
                    'title' => 'Lounge POV',
                    'description' => 'A closer perspective looking toward the lounge and balcony side.',
                    'image' => rooms_catalog_tour_image('Suite1_balcony_pov.webp'),
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'High-speed Wi-Fi',
                'Complimentary minibar (selected items)',
                'Late checkout (subject to availability)',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Living/sitting area',
                'Balcony (1)',
            ],
            'bathroom' => [
                'Marble bathroom',
                'Separate bathtub and shower',
                'Hair dryer',
                'Robe and slippers',
            ],
            'furnishings' => [
                'Sofa',
                'Work desk',
                'In-room safe (fee may apply)',
                'Iron and ironing board',
            ],
        ],
        [
            'id' => 102,
            'name' => 'Deluxe Ocean View',
            'description' => 'Wake up to ocean vistas, designed for couples or solo travellers seeking serenity.',
            'price_per_night' => 289.00,
            'occupancy' => 2,
            'view' => 'Ocean',
            'accessible' => true,
            'size' => '46 sqm / 495 sqft',
            'bed' => '1 King',
            'images' => [
                rooms_catalog_image('Suite2.webp'),
                rooms_catalog_image('SpaRoom.webp'),
                rooms_catalog_image('Sustainability.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('Suite2_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Room overview',
                    'description' => 'Click the bathroom, bed, or balcony area to jump to that point of view.',
                    'image' => rooms_catalog_image('Suite2.webp'),
                    'hotspots' => [
                        [
                            'id' => 'balcony',
                            'label' => 'Balcony',
                            'x' => 44,
                            'y' => 58,
                            'target' => 'balcony',
                        ],
                        [
                            'id' => 'bathroom',
                            'label' => 'Bathroom',
                            'x' => 74,
                            'y' => 52,
                            'target' => 'bathroom',
                        ],
                        [
                            'id' => 'bed',
                            'label' => 'Bed',
                            'x' => 86,
                            'y' => 72,
                            'target' => 'bed',
                        ],
                    ],
                ],
                [
                    'id' => 'balcony',
                    'title' => 'Balcony POV',
                    'description' => 'The outdoor lounge view facing the ocean.',
                    'image' => rooms_catalog_tour_image('Suite2_balcony_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bathroom',
                    'title' => 'Bathroom POV',
                    'description' => 'A closer angle of the tub, vanity, and marble bath area.',
                    'image' => rooms_catalog_tour_image('Suite2_bathroom_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bed',
                    'title' => 'Bed POV',
                    'description' => 'A closer perspective focused on the bed and main sleeping area.',
                    'image' => rooms_catalog_tour_image('Suite2_bed_pov.webp'),
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'Accessible-friendly layout',
                'High-speed Wi-Fi',
                'Priority housekeeping',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Connecting rooms (some rooms)',
                'Smart TV',
            ],
            'bathroom' => [
                'Walk-in shower',
                'Grab bars',
                'Double vanity',
                'Premium toiletries',
            ],
            'furnishings' => [
                'Armchair',
                'Desk and power outlet',
                'Alarm clock',
                'Wardrobe',
            ],
        ],
        [
            'id' => 103,
            'name' => 'Family Lagoon Suite',
            'description' => 'Extra space for families with a relaxed living area and resort-lagoon ambiance.',
            'price_per_night' => 349.00,
            'occupancy' => 3,
            'view' => 'Lagoon',
            'accessible' => false,
            'size' => '58 sqm / 624 sqft',
            'bed' => '1 King + Sofa Bed',
            'images' => [
                rooms_catalog_image('Suite3.webp'),
                rooms_catalog_image('dining/RestaurantSunsetLagoon.webp'),
                rooms_catalog_image('dining/Cafe.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('Suite3_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Suite overview',
                    'description' => 'Click the lounge, sleeping area, or stair landing to inspect that part of the suite.',
                    'image' => rooms_catalog_image('Suite3.webp'),
                    'hotspots' => [
                        [
                            'id' => 'lounge',
                            'label' => 'Lounge',
                            'x' => 28,
                            'y' => 74,
                            'target' => 'lounge',
                        ],
                        [
                            'id' => 'sleep',
                            'label' => 'Sleeping area',
                            'x' => 41,
                            'y' => 57,
                            'target' => 'sleep',
                        ],
                        [
                            'id' => 'stair',
                            'label' => 'Stair landing',
                            'x' => 80,
                            'y' => 41,
                            'target' => 'stair',
                        ],
                    ],
                ],
                [
                    'id' => 'lounge',
                    'title' => 'Lounge POV',
                    'description' => 'A closer look at the suite lounge and worktable area.',
                    'image' => rooms_catalog_tour_image('Suite3_lounge_pov.webp'),
                    'fit' => 'contain',
                    'background' => '#ece5d8',
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'sleep',
                    'title' => 'Sleeping area POV',
                    'description' => 'A focused view toward the sleeping zone and courtyard-facing curtains.',
                    'image' => rooms_catalog_tour_image('Suite3_sleep_pov.webp'),
                    'fit' => 'contain',
                    'background' => '#efe8dd',
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'stair',
                    'title' => 'Stair landing POV',
                    'description' => 'A closer view of the upper landing and staircase detailing.',
                    'image' => rooms_catalog_tour_image('Suite3_stair_pov.webp'),
                    'fit' => 'contain',
                    'background' => '#ece6da',
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'Kid-friendly layout',
                'High-speed Wi-Fi',
                'Complimentary welcome snack',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Living/sitting area',
                'Mini fridge',
            ],
            'bathroom' => [
                'Bathtub (oversized)',
                'Separate shower',
                'Hair dryer',
                'Bath amenities',
            ],
            'furnishings' => [
                'Sofa bed',
                'Dining nook',
                'Work desk',
                'In-room safe',
            ],
        ],
        [
            'id' => 104,
            'name' => 'Horizon Family Villa',
            'description' => 'A villa-style stay with more privacy - ideal for groups and longer visits.',
            'price_per_night' => 529.00,
            'occupancy' => 4,
            'view' => 'Garden',
            'accessible' => true,
            'size' => '92 sqm / 990 sqft',
            'bed' => '2 Beds (1 King + 2 Singles)',
            'images' => [
                rooms_catalog_image('horizon_villa_main.webp'),
                rooms_catalog_tour_image('horizon_villa_balcony_pov.webp'),
                rooms_catalog_tour_image('horizon_villa_bathroom_pov.webp'),
                rooms_catalog_tour_image('horizon_villa_bed_pov.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('AboutUsSpa_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Villa overview',
                    'description' => 'Click the balcony, bathroom, or bed area to step into that part of the villa.',
                    'image' => rooms_catalog_image('horizon_villa_main.webp'),
                    'hotspots' => [
                        [
                            'id' => 'balcony',
                            'label' => 'Balcony',
                            'x' => 50,
                            'y' => 56,
                            'target' => 'balcony',
                        ],
                        [
                            'id' => 'bathroom',
                            'label' => 'Bathroom',
                            'x' => 79,
                            'y' => 48,
                            'target' => 'bathroom',
                        ],
                        [
                            'id' => 'bed',
                            'label' => 'Bed',
                            'x' => 83,
                            'y' => 72,
                            'target' => 'bed',
                        ],
                    ],
                ],
                [
                    'id' => 'balcony',
                    'title' => 'Balcony POV',
                    'description' => 'The outdoor lounge view facing the villa grounds.',
                    'image' => rooms_catalog_tour_image('horizon_villa_balcony_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bathroom',
                    'title' => 'Bathroom POV',
                    'description' => 'A closer angle of the tub, vanity, and marble bath area.',
                    'image' => rooms_catalog_tour_image('horizon_villa_bathroom_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bed',
                    'title' => 'Bed POV',
                    'description' => 'A closer perspective focused on the villa bed and sleeping area.',
                    'image' => rooms_catalog_tour_image('horizon_villa_bed_pov.webp'),
                    'fit' => 'contain',
                    'background' => '#eee6da',
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'Accessible-friendly layout',
                'High-speed Wi-Fi',
                'Private lounge seating',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Separate living room',
                'Kitchenette (light)',
            ],
            'bathroom' => [
                'Walk-in shower',
                'Grab bars',
                'Double vanity',
                'Premium toiletries',
            ],
            'furnishings' => [
                'Dining table',
                'Work desk',
                'In-room safe',
                'Wardrobe',
            ],
        ],
        [
            'id' => 105,
            'name' => 'Grand Ocean View',
            'description' => 'A serene coastal room with a tranquil sea view, and breezy shoreline mood to relax.',
            'price_per_night' => 259.00,
            'occupancy' => 2,
            'view' => 'Ocean',
            'accessible' => false,
            'size' => '40 sqm / 430 sqft',
            'bed' => '1 Queen',
            'images' => [
                rooms_catalog_image('city_lights_main.webp'),
                rooms_catalog_tour_image('city_lights_lounge_pov.webp'),
                rooms_catalog_tour_image('city_lights_bathroom_pov.webp'),
                rooms_catalog_tour_image('city_lights_bed_pov.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('parking1_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Room overview',
                    'description' => 'Click the lounge, bathroom, or bed area to jump to that point of view.',
                    'image' => rooms_catalog_image('city_lights_main.webp'),
                    'hotspots' => [
                        [
                            'id' => 'balcony',
                            'label' => 'Lounge',
                            'x' => 39,
                            'y' => 52,
                            'target' => 'balcony',
                        ],
                        [
                            'id' => 'bathroom',
                            'label' => 'Bathroom',
                            'x' => 17,
                            'y' => 57,
                            'target' => 'bathroom',
                        ],
                        [
                            'id' => 'bed',
                            'label' => 'Bed',
                            'x' => 73,
                            'y' => 60,
                            'target' => 'bed',
                        ],
                    ],
                ],
                [
                    'id' => 'bathroom',
                    'title' => 'Bathroom POV',
                    'description' => 'A closer angle of the warm stone bathroom and vanity zone.',
                    'image' => rooms_catalog_tour_image('city_lights_bathroom_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bed',
                    'title' => 'Bed POV',
                    'description' => 'A closer perspective focused on the city-facing bed area.',
                    'image' => rooms_catalog_tour_image('city_lights_bed_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'balcony',
                    'title' => 'Lounge POV',
                    'description' => 'A closer perspective looking toward the lounge and skyline view.',
                    'image' => rooms_catalog_tour_image('city_lights_lounge_pov.webp'),
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'High-speed Wi-Fi',
                'Dedicated workspace',
                'Express check-in',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Smart TV',
                'Mini fridge',
            ],
            'bathroom' => [
                'Shower',
                'Hair dryer',
                'Bath amenities',
                'Slippers',
            ],
            'furnishings' => [
                'Work desk',
                'Chair',
                'Wardrobe',
                'In-room safe',
            ],
        ],
        [
            'id' => 106,
            'name' => 'Accessible Garden Twin',
            'description' => 'Wheelchair-friendly twin room with an easy-access bathroom and calm garden view.',
            'price_per_night' => 239.00,
            'occupancy' => 2,
            'view' => 'Garden',
            'accessible' => true,
            'size' => '44 sqm / 474 sqft',
            'bed' => '2 Singles',
            'images' => [
                rooms_catalog_image('Suite1.webp'),
                rooms_catalog_image('AboutUs.webp'),
                rooms_catalog_image('Sustainability.webp'),
            ],
            'tour_image' => rooms_catalog_tour_image('Suite1_tour.webp'),
            'tour_scenes' => [
                [
                    'id' => 'overview',
                    'title' => 'Accessible room overview',
                    'description' => 'Click the bathroom, bed, or lounge area to jump to that point of view.',
                    'image' => rooms_catalog_image('Suite1.webp'),
                    'hotspots' => [
                        [
                            'id' => 'bathroom',
                            'label' => 'Bathroom',
                            'x' => 73,
                            'y' => 43,
                            'target' => 'bathroom',
                        ],
                        [
                            'id' => 'bed',
                            'label' => 'Bed',
                            'x' => 77,
                            'y' => 67,
                            'target' => 'bed',
                        ],
                        [
                            'id' => 'balcony',
                            'label' => 'Lounge',
                            'x' => 39,
                            'y' => 43,
                            'target' => 'balcony',
                        ],
                    ],
                ],
                [
                    'id' => 'bathroom',
                    'title' => 'Bathroom POV',
                    'description' => 'A closer angle facing the accessible bath and vanity zone.',
                    'image' => rooms_catalog_tour_image('Suite1_bathroom_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'bed',
                    'title' => 'Bed POV',
                    'description' => 'The view from directly in front of the twin sleeping area.',
                    'image' => rooms_catalog_tour_image('Suite1_bed_pov.webp'),
                    'back_target' => 'overview',
                ],
                [
                    'id' => 'balcony',
                    'title' => 'Lounge POV',
                    'description' => 'A closer perspective looking toward the lounge and window side.',
                    'image' => rooms_catalog_tour_image('Suite1_balcony_pov.webp'),
                    'back_target' => 'overview',
                ],
            ],
            'benefits' => [
                'Accessible-friendly layout',
                'High-speed Wi-Fi',
                'Priority housekeeping',
            ],
            'features' => [
                'Air-conditioned',
                'Non-smoking',
                'Connecting rooms (some rooms)',
                'Smart TV',
            ],
            'bathroom' => [
                'Roll-in shower',
                'Grab bars',
                'Low sink/vanity',
                'Wide doorway',
            ],
            'furnishings' => [
                'Work desk',
                'Chair',
                'Wardrobe',
                'Alarm clock',
            ],
        ],
    ];

    return $rooms;
}

function rooms_catalog_find(int $roomId): ?array
{
    foreach (rooms_catalog_all() as $room) {
        if ((int)($room['id'] ?? 0) === $roomId) {
            return $room;
        }
    }

    return null;
}

function rooms_catalog_occupancy_options(?array $rooms = null): array
{
    $rooms ??= rooms_catalog_all();
    $options = array_values(array_unique(array_map(
        static fn(array $room): int => (int)($room['occupancy'] ?? 0),
        $rooms
    )));
    sort($options);

    return $options;
}

function rooms_catalog_view_options(?array $rooms = null): array
{
    $rooms ??= rooms_catalog_all();
    $preferredOrder = ['Garden', 'Ocean', 'Lagoon'];
    $available = [];

    foreach ($rooms as $room) {
        $view = trim((string)($room['view'] ?? ''));
        if ($view !== '') {
            $available[$view] = true;
        }
    }

    $options = [];
    foreach ($preferredOrder as $view) {
        if (isset($available[$view])) {
            $options[] = $view;
            unset($available[$view]);
        }
    }

    foreach (array_keys($available) as $view) {
        $options[] = $view;
    }

    return $options;
}

function rooms_catalog_group_by_occupancy(?array $rooms = null): array
{
    $rooms ??= rooms_catalog_all();
    $groups = [];

    foreach ($rooms as $room) {
        $occupancy = (int)($room['occupancy'] ?? 0);
        if (!isset($groups[$occupancy])) {
            $groups[$occupancy] = [];
        }
        $groups[$occupancy][] = $room;
    }

    ksort($groups);

    return $groups;
}

function rooms_catalog_primary_image(array $room): string
{
    $images = $room['images'] ?? [];

    if (is_array($images) && $images) {
        return (string)$images[0];
    }

    return rooms_catalog_image('HotelHomePage.webp');
}

function rooms_catalog_json(?array $rooms = null): string
{
    $rooms ??= rooms_catalog_all();
    $json = json_encode(
        array_values($rooms),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return $json === false ? '[]' : $json;
}
