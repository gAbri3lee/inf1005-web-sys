<?php
session_start();
require_once __DIR__ . '/../app/includes/db.php';
include __DIR__ . '/../app/includes/header.php';
?>

<section class="bg-dark text-white py-5 text-center">
    <div class="container">
        <h1 class="display-4">Rooms & Suites</h1>
        <p class="lead">Discover our collection of luxurious accommodations.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM rooms");
            while ($row = $stmt->fetch()) {
                echo '<div class="col-md-4 mb-4">';
                echo '  <div class="card h-100 border-0 shadow">';
                echo '    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '">';
                echo '    <div class="card-body">';
                echo '      <h3 class="card-title h5">' . htmlspecialchars($row['name']) . '</h3>';
                echo '      <p class="card-text text-muted">' . htmlspecialchars($row['category']) . '</p>';
                echo '      <p class="card-text">' . htmlspecialchars($row['description']) . '</p>';
                echo '      <p class="fw-bold text-primary fs-4">$' . number_format($row['price'], 2) . ' / night</p>';
                echo '      <a href="booking.php?room_id=' . $row['id'] . '" class="btn btn-primary w-100">Book Now</a>';
                echo '    </div>';
                echo '  </div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
