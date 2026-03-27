<?php

declare(strict_types=1);

/**
 * Demo rooms catalog (DB-independent).
 *
 * NOTE: This is scaffolding for assignment UI/UX flows.
 */
function get_rooms_catalog(): array
{
    $img = static fn(string $path): string => 'assets/images/' . ltrim($path, '/');

    return [
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
            'images' => [$img('Suite1.png'), $img('HotelHomePage.png'), $img('AboutUs.png')],
            'benefits' => ['High-speed Wi‑Fi', 'Complimentary minibar (selected items)', 'Late checkout (subject to availability)'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Living/sitting area', 'Balcony (1)'],
            'bathroom' => ['Marble bathroom', 'Separate bathtub and shower', 'Hair dryer', 'Robe & slippers'],
            'furnishings' => ['Sofa', 'Work desk', 'In-room safe (fee may apply)', 'Iron & ironing board'],
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
            'images' => [$img('Suite2.png'), $img('SpaRoom.png'), $img('Sustainability.png')],
            'benefits' => ['Accessible-friendly layout', 'High-speed Wi‑Fi', 'Priority housekeeping'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Connecting rooms (some rooms)', 'Smart TV'],
            'bathroom' => ['Walk-in shower', 'Grab bars', 'Double vanity', 'Premium toiletries'],
            'furnishings' => ['Armchair', 'Desk & power outlet', 'Alarm clock', 'Wardrobe'],
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
            'images' => [$img('Suite3.png'), $img('dining/RestaurantSunsetLagoon.png'), $img('dining/Cafe.png')],
            'benefits' => ['Kid-friendly layout', 'High-speed Wi‑Fi', 'Complimentary welcome snack'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Living/sitting area', 'Mini fridge'],
            'bathroom' => ['Bathtub (oversized)', 'Separate shower', 'Hair dryer', 'Bath amenities'],
            'furnishings' => ['Sofa bed', 'Dining nook', 'Work desk', 'In-room safe'],
        ],
        [
            'id' => 104,
            'name' => 'Horizon Two-Bedroom Villa',
            'description' => 'A villa-style stay with more privacy — ideal for groups and longer visits.',
            'price_per_night' => 529.00,
            'occupancy' => 4,
            'view' => 'Garden',
            'accessible' => true,
            'size' => '92 sqm / 990 sqft',
            'bed' => '2 Bedrooms (1 King + 2 Singles)',
            'images' => [$img('AboutUsSpa.png'), $img('Suite2.png'), $img('Suite1.png')],
            'benefits' => ['Accessible-friendly layout', 'High-speed Wi‑Fi', 'Private lounge seating'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Separate living room', 'Kitchenette (light)'],
            'bathroom' => ['Walk-in shower', 'Grab bars', 'Double vanity', 'Premium toiletries'],
            'furnishings' => ['Dining table', 'Work desk', 'In-room safe', 'Wardrobe'],
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
            'images' => [$img('parking_and_transport/parking1.png'), $img('dining/RestaurantFieryBlaze.png'), $img('DiscoverMore.png')],
            'benefits' => ['High-speed Wi‑Fi', 'Dedicated workspace', 'Express check-in'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Smart TV', 'Mini fridge'],
            'bathroom' => ['Shower', 'Hair dryer', 'Bath amenities', 'Slippers'],
            'furnishings' => ['Work desk', 'Chair', 'Wardrobe', 'In-room safe'],
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
            'images' => [$img('Suite1.png'), $img('AboutUs.png'), $img('Sustainability.png')],
            'benefits' => ['Accessible-friendly layout', 'High-speed Wi‑Fi', 'Priority housekeeping'],
            'features' => ['Air-conditioned', 'Non-smoking', 'Connecting rooms (some rooms)', 'Smart TV'],
            'bathroom' => ['Roll-in shower', 'Grab bars', 'Low sink/vanity', 'Wide doorway'],
            'furnishings' => ['Work desk', 'Chair', 'Wardrobe', 'Alarm clock'],
        ],
    ];
}

function find_room_by_id(int $roomId): ?array
{
    foreach (get_rooms_catalog() as $room) {
        if ((int)($room['id'] ?? 0) === $roomId) {
            return $room;
        }
    }
    return null;
}
