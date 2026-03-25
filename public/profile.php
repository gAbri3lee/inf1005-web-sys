<?php
session_start();
require_once __DIR__ . '/../app/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT b.*, r.name as room_name FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

include __DIR__ . '/../app/includes/navbar.php';
?>

<section class="bg-dark text-white py-5 text-center">
    <div class="container">
        <h1 class="display-4">My Bookings</h1>
        <p class="lead">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow border-0 p-4">
                    <h2 class="mb-4">Your Booking History</h2>
                    <?php if (empty($bookings)): ?>
                        <p class="text-center">You have no bookings yet. <a href="rooms_and_suites.php">Book a room now!</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Date Booked</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                                            <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td><span class="badge bg-success"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
