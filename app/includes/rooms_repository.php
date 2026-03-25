<?php

declare(strict_types=1);

require_once __DIR__ . '/rooms_data.php';

/**
 * Rooms data access wrapper.
 *
 * Today: reads from hardcoded catalog (rooms_data.php).
 * Later: replace internals with DB queries without changing page code.
 */
function rooms_repo_all(): array
{
    return get_rooms_catalog();
}

function rooms_repo_find(int $roomId): ?array
{
    return find_room_by_id($roomId);
}
