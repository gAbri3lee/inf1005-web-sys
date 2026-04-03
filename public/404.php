<?php
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Not Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="light-navbar">

<?php include '../app/includes/navbar.php'; ?>

<section style="min-height: 80vh; display:flex; align-items:center; justify-content:center; text-align:center; padding:40px;">
    <div>
        <h1 style="font-size: 4rem; color:#D0A961; margin-bottom:10px;">404</h1>
        <h2 style="margin-bottom:15px;">Page Not Found</h2>
        <p style="margin-bottom:25px;">The page you are looking for does not exist.</p>

        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
</section>

<?php include '../app/includes/footer.php'; ?>

</body>
</html>