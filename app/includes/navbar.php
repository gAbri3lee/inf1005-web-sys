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

    <?php
    // Optional per-page CSS injection (set $pageStylesheets = ['assets/css/page.css']; before including navbar.php)
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
<nav class="navbar navbar-expand-lg navbar-dark site-navbar fixed-top" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand navbar-logo-link" href="index.php" aria-label="Horizon Sands Bali home">
            <img
                src="assets/images/logo_updated.png"
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
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="rooms_and_suites.php">Suites & Villas</a></li>
                <li class="nav-item"><a class="nav-link" href="amenities.php">Amenities</a></li>
                <li class="nav-item"><a class="nav-link" href="Dining.php">Dining</a></li>
                <li class="nav-item"><a class="nav-link" href="reviews.php">Reviews</a></li>
                <li class="nav-item"><a class="nav-link" href="FAQs.php">FAQs</a></li>
                <li class="nav-item"><a class="nav-link" href="parking_and_transport.php">Parking & Transport</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-nav-action" href="profile.php">My Account</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-gold" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-nav-action" href="login.php">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-gold" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>