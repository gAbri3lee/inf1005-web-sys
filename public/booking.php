<?php
session_start();
require_once __DIR__ . '/../app/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) {
    header('Location: rooms.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: rooms.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    if (empty($check_in) || empty($check_out)) {
        $error = 'Please select check-in and check-out dates.';
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $error = 'Check-out date must be after check-in date.';
    } else {
        $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_price = $days * $room['price'];

        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total_price])) {
            $success = 'Booking successful! You can view your bookings in your profile.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

include __DIR__ . '/../app/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    <div class="card-body p-4">
                        <h2 class="card-title h3"><?php echo htmlspecialchars($room['name']); ?></h2>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($row['category']); ?></p>
                        <p class="card-text"><?php echo htmlspecialchars($room['description']); ?></p>
                        <p class="fw-bold text-primary fs-4">Price: $<?php echo number_format($room['price'], 2); ?> / night</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow border-0 p-4">
                    <h2 class="text-center mb-4">Book Your Stay</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form action="booking.php?room_id=<?php echo $room_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="check_in" class="form-label">Check-in Date</label>
                            <input type="date" class="form-control" id="check_in" name="check_in" required>
                        </div>
                        <div class="mb-3">
                            <label for="check_out" class="form-label">Check-out Date</label>
                            <input type="date" class="form-control" id="check_out" name="check_out" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3">Confirm Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
