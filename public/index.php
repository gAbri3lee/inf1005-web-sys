<?php
session_start();
require_once __DIR__ . '/../app/includes/db.php';
include __DIR__ . '/../app/includes/header.php';
?>

<header class="hero-section text-center text-white d-flex align-items-center justify-content-center">
    <div class="container">
        <h1 class="display-1 fw-bold">Escape to Paradise</h1>
        <p class="lead fs-3">Luxury Beachfront Resort in the Heart of Seminyak</p>
        <a href="rooms.php" class="btn btn-primary btn-lg mt-4 px-5 py-3">Book Your Stay</a>
    </div>
</header>

<section class="py-5 my-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="display-4 mb-4">Welcome to Azure Horizon</h2>
                <p class="lead">Experience the ultimate luxury at our beachfront resort. Where the ocean meets the sky, and every moment is a memory in the making.</p>
                <p>Our resort offers a unique blend of modern luxury and traditional Balinese hospitality. From our world-class spa to our award-winning restaurants, every detail is designed to provide you with an unforgettable experience.</p>
                <a href="about.php" class="btn btn-outline-dark mt-3">Learn More About Us</a>
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Resort View" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center display-4 mb-5">Our Amenities</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM amenities");
            while ($row = $stmt->fetch()) {
                echo '<div class="col-md-4 mb-4">';
                echo '  <div class="card h-100 border-0 shadow-sm text-center p-4">';
                echo '    <div class="card-body">';
                echo '      <h3 class="card-title h4">' . htmlspecialchars($row['name']) . '</h3>';
                echo '      <p class="card-text">' . htmlspecialchars($row['description']) . '</p>';
                echo '    </div>';
                echo '  </div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container text-center">
        <h2 class="display-4 mb-5">Featured Rooms</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM rooms LIMIT 3");
            while ($row = $stmt->fetch()) {
                echo '<div class="col-md-4 mb-4">';
                echo '  <div class="card h-100 border-0 shadow">';
                echo '    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '">';
                echo '    <div class="card-body">';
                echo '      <h3 class="card-title h5">' . htmlspecialchars($row['name']) . '</h3>';
                echo '      <p class="card-text text-muted">' . htmlspecialchars($row['category']) . '</p>';
                echo '      <p class="fw-bold text-primary">From $' . number_format($row['price'], 2) . ' / night</p>';
                echo '      <a href="rooms.php" class="btn btn-outline-primary">View Details</a>';
                echo '    </div>';
                echo '  </div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
