<?php
session_start();

require_once __DIR__ . '/rooms_catalog.php';

$rooms = rooms_catalog_all();
$roomGroups = rooms_catalog_group_by_occupancy($rooms);
$viewOptions = rooms_catalog_view_options($rooms);
$occupancyOptions = rooms_catalog_occupancy_options($rooms);
$pageStylesheets = ['assets/css/rooms_and_suites.css'];
$pageScripts = ['assets/js/rooms_and_suites.js'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="rooms-page">
    <section class="rooms-shell">
        <div class="container">
            <div class="content-card rooms-card">
                <header class="rooms-header text-center">
                    <h1 class="rooms-title">Rooms &amp; Suites</h1>
                    <p class="rooms-subtitle mb-0">Browse by occupancy, view, and accessibility, then pick your dates to see the total.</p>
                </header>

                <section class="rooms-filters" aria-label="Room filters">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-12 col-md-6 col-xl-5">
                            <div class="filter-block">
                                <div class="filter-title">Guests</div>
                                <div class="filter-chips" role="group" aria-label="Filter by guests">
                                    <?php foreach ($occupancyOptions as $occupancy): ?>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input js-filter-occupancy" type="checkbox" id="occ_<?php echo (int)$occupancy; ?>" value="<?php echo (int)$occupancy; ?>">
                                            <label class="form-check-label" for="occ_<?php echo (int)$occupancy; ?>"><?php echo (int)$occupancy; ?> pax</label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-5">
                            <div class="filter-block">
                                <div class="filter-title">View</div>
                                <div class="filter-chips" role="group" aria-label="Filter by view">
                                    <?php foreach ($viewOptions as $view): ?>
                                        <?php $viewId = strtolower(str_replace(' ', '_', $view)); ?>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input js-filter-view" type="checkbox" id="view_<?php echo htmlspecialchars($viewId, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                                            <label class="form-check-label" for="view_<?php echo htmlspecialchars($viewId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <div class="filter-block filter-block-accessibility">
                                <div class="filter-title">Accessibility</div>
                                <div class="form-check form-check-single m-0">
                                    <input class="form-check-input js-filter-accessible" type="checkbox" id="filter_accessible" value="1">
                                    <label class="form-check-label" for="filter_accessible">Wheelchair friendly</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="filters-actions">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-clear-filters">Clear filters</button>
                        <span class="filters-count" aria-live="polite"><span class="js-room-count">0</span> rooms shown</span>
                    </div>
                </section>

                <div class="rooms-groups">
                    <?php foreach ($roomGroups as $occupancy => $groupRooms): ?>
                        <section class="rooms-group js-room-group" data-group-occupancy="<?php echo (int)$occupancy; ?>" aria-label="Rooms for <?php echo (int)$occupancy; ?> guests">
                            <h2 class="rooms-group-title"><?php echo (int)$occupancy; ?> Guest<?php echo (int)$occupancy === 1 ? '' : 's'; ?> Rooms</h2>
                            <div class="row g-4">
                                <?php foreach ($groupRooms as $room): ?>
                                    <?php
                                        $accessible = !empty($room['accessible']);
                                        $cover = rooms_catalog_primary_image($room);
                                    ?>
                                    <div
                                        class="col-12 col-md-6 col-xl-4 js-room-card"
                                        data-room-id="<?php echo (int)($room['id'] ?? 0); ?>"
                                        data-occupancy="<?php echo (int)($room['occupancy'] ?? 0); ?>"
                                        data-view="<?php echo htmlspecialchars((string)($room['view'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-accessible="<?php echo $accessible ? '1' : '0'; ?>"
                                    >
                                        <article class="content-card room-card h-100">
                                            <div class="room-media">
                                                <img class="room-image" src="<?php echo htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)($room['name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                                            </div>
                                            <div class="room-body">
                                                <div class="room-top">
                                                    <h3 class="room-name"><?php echo htmlspecialchars((string)($room['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                                                    <div class="room-badges">
                                                        <span class="badge rounded-pill text-bg-light"><?php echo (int)($room['occupancy'] ?? 0); ?> pax</span>
                                                        <span class="badge rounded-pill text-bg-light"><?php echo htmlspecialchars((string)($room['view'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> view</span>
                                                        <?php if ($accessible): ?>
                                                            <span class="badge rounded-pill text-bg-light">Accessible</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="room-desc"><?php echo htmlspecialchars((string)($room['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                                <div class="room-bottom">
                                                    <div class="room-price">$<?php echo number_format((float)($room['price_per_night'] ?? 0), 2); ?> <span>/ night</span></div>
                                                    <button type="button" class="btn btn-gold btn-sm w-100 js-open-room" data-room-id="<?php echo (int)($room['id'] ?? 0); ?>">View details</button>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <div class="rooms-empty alert alert-warning mt-4 d-none js-empty" role="alert">
                    No rooms match your filters. Try clearing some selections.
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade room-details-modal" id="roomDetailsModal" tabindex="-1" aria-labelledby="roomDetailsTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0" id="roomDetailsTitle">Room details</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="roomCarousel" class="carousel slide" data-bs-ride="false" aria-label="Room images">
                        <div class="carousel-indicators js-room-carousel-indicators"></div>
                        <div class="carousel-inner js-room-carousel-inner"></div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <div class="p-4 p-md-5">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div>
                                <h3 class="h4 mb-1 js-room-name">Room name</h3>
                                <p class="text-muted mb-0 js-room-short">Room short description</p>
                            </div>
                            <div class="text-md-end">
                                <div class="room-modal-price js-room-price">$0.00 / night</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Room overview</h4>
                                <ul class="rooms-modal-list js-room-overview"></ul>
                            </div>
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Special benefits</h4>
                                <ul class="rooms-modal-list js-room-benefits"></ul>
                            </div>
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Beds &amp; bedding</h4>
                                <ul class="rooms-modal-list js-room-bedding"></ul>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Room features</h4>
                                <ul class="rooms-modal-list js-room-features"></ul>
                            </div>
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Bath &amp; bathroom</h4>
                                <ul class="rooms-modal-list js-room-bath"></ul>
                            </div>
                            <div class="col-md-4">
                                <h4 class="h6 text-uppercase rooms-modal-section">Furniture &amp; furnishings</h4>
                                <ul class="rooms-modal-list js-room-furnish"></ul>
                            </div>
                        </div>

                        <hr class="my-4">

                        <form class="room-dates" action="room_quote.php" method="GET" novalidate>
                            <input type="hidden" name="room_id" class="js-room-id" value="">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label for="check_in" class="form-label">Check-in</label>
                                    <input type="date" class="form-control js-check-in" id="check_in" name="check_in" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="check_out" class="form-label">Check-out</label>
                                    <input type="date" class="form-control js-check-out" id="check_out" name="check_out" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="room-dates-summary">
                                        <div class="text-muted">Estimated total</div>
                                        <div class="room-dates-total js-est-total">Select dates</div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid d-md-flex justify-content-md-end gap-2 mt-3">
                                <button type="submit" class="btn btn-gold js-view-total" disabled>View total</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script id="rooms-catalog-data" type="application/json"><?php echo rooms_catalog_json($rooms); ?></script>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
