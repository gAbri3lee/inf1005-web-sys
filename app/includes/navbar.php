<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horizon Sands Bali</title>
    <meta name="description" content="Horizon Sands Bali - Dive Into Bliss with sunset-facing suites, villas, dining, and coastal luxury experiences.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navbar.css">

    <?php
    if (isset($pageStylesheets)) {
        $styles = is_array($pageStylesheets) ? $pageStylesheets : [$pageStylesheets];
        foreach ($styles as $href) {
            $href = (string)$href;
            if ($href !== '') {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }
    }
    ?>
</head>
<body>
<?php
require_once __DIR__ . '/auth.php';

$currentUser = auth_current_user();
$authReturnPath = auth_current_relative_url();
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
?>
<nav class="navbar navbar-dark site-navbar fixed-top navbar-expand-custom" aria-label="Main navigation">
    <div class="container-fluid navbar-shell">
        <a class="navbar-brand navbar-logo-link" href="index.php" aria-label="Horizon Sands Bali home">
            <img
                    src="assets/images/logo_updated.webp"
                alt="Horizon Sands Bali Beach Resort and Hotel logo"
                class="navbar-logo"
                height="46"
            >
            <span class="navbar-brand-text">
                <span class="navbar-brand-title">Horizon Sands</span>
                <span class="navbar-brand-subtitle">Bali</span>
            </span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav navbar-main-nav">
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'index.php' ? ' active' : ''; ?>" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'about.php' ? ' active' : ''; ?>" href="about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'rooms_and_suites.php' ? ' active nav-link-active-dark-gold' : ''; ?>" href="rooms_and_suites.php">Suites & Villas</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'amenities.php' ? ' active' : ''; ?>" href="amenities.php">Amenities</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'Dining.php' ? ' active' : ''; ?>" href="Dining.php">Dining</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'reviews.php' ? ' active nav-link-active-dark-gold' : ''; ?>" href="reviews.php">Reviews</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'FAQs.php' ? ' active' : ''; ?>" href="FAQs.php">FAQs</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'parking_and_transport.php' ? ' active nav-link-active-black' : ''; ?>" href="parking_and_transport.php">Parking & Transport</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'contact.php' ? ' active' : ''; ?>" href="contact.php">Contact</a></li>
            </ul>

            <div class="navbar-actions">
                <?php if ($currentUser): ?>
                    <a class="btn btn-outline-light btn-nav-action" href="dashboard.php">Dashboard</a>
                    <form class="navbar-logout-form" action="logout.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('logout_form'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-gold">Logout</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-nav-action" href="login.php?next=<?php echo rawurlencode($authReturnPath); ?>">Login</a>
                    <a class="btn btn-gold" href="register.php?next=<?php echo rawurlencode($authReturnPath); ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
