<?php

declare(strict_types=1);

function rooms_catalog_image(string $path): string
{
    return 'assets/images/' . ltrim($path, '/');
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
            'name' => 'Horizon Two-Bedroom Villa',
            'description' => 'A villa-style stay with more privacy - ideal for groups and longer visits.',
            'price_per_night' => 529.00,
            'occupancy' => 4,
            'view' => 'Garden',
            'accessible' => true,
            'size' => '92 sqm / 990 sqft',
            'bed' => '2 Bedrooms (1 King + 2 Singles)',
            'images' => [
                rooms_catalog_image('AboutUsSpa.webp'),
                rooms_catalog_image('Suite2.webp'),
                rooms_catalog_image('Suite1.webp'),
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
            'name' => 'City Lights Executive Room',
            'description' => 'A refined room with city skyline views and a comfortable workspace.',
            'price_per_night' => 259.00,
            'occupancy' => 2,
            'view' => 'City',
            'accessible' => false,
            'size' => '40 sqm / 430 sqft',
            'bed' => '1 Queen',
            'images' => [
                rooms_catalog_image('parking_and_transport/parking1.webp'),
                rooms_catalog_image('dining/RestaurantFieryBlaze.webp'),
                rooms_catalog_image('DiscoverMore.webp'),
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

function rooms_catalog_occupancy_options(array $rooms = null): array
{
    $rooms ??= rooms_catalog_all();
    $options = array_values(array_unique(array_map(
        static fn(array $room): int => (int)($room['occupancy'] ?? 0),
        $rooms
    )));
    sort($options);

    return $options;
}

function rooms_catalog_view_options(array $rooms = null): array
{
    $rooms ??= rooms_catalog_all();
    $preferredOrder = ['Garden', 'Ocean', 'City', 'Lagoon'];
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

function rooms_catalog_group_by_occupancy(array $rooms = null): array
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

function rooms_catalog_json(array $rooms = null): string
{
    $rooms ??= rooms_catalog_all();
    $json = json_encode(
        array_values($rooms),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return $json === false ? '[]' : $json;
}
