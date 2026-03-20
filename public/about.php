<?php
session_start();
include __DIR__ . '/../app/includes/header.php';
?>

<section class="bg-dark text-white py-5 text-center">
    <div class="container">
        <h1 class="display-4">About Us</h1>
        <p class="lead">Experience the ultimate luxury at our beachfront resort.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="display-4 mb-4">Our Story</h2>
                <p class="lead">Azure Horizon Resort & Spa was founded with a simple vision: to create a sanctuary where luxury meets nature.</p>
                <p>Located in the heart of Seminyak, Bali, our resort offers a unique blend of modern luxury and traditional Balinese hospitality. From our world-class spa to our award-winning restaurants, every detail is designed to provide you with an unforgettable experience.</p>
                <p>Our team of dedicated professionals is committed to providing you with the highest level of service and attention to detail. Whether you're looking for a romantic getaway, a family vacation, or a business retreat, we have everything you need to make your stay unforgettable.</p>
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Resort View" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<section class="bg-light py-5">
    <div class="container text-center">
        <h2 class="display-4 mb-5">Our Values</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="card-body">
                        <h3 class="card-title h4">Excellence</h3>
                        <p class="card-text">We strive for excellence in everything we do, from the quality of our accommodations to the level of service we provide.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="card-body">
                        <h3 class="card-title h4">Hospitality</h3>
                        <p class="card-text">We believe in the power of hospitality to create meaningful connections and unforgettable experiences.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="card-body">
                        <h3 class="card-title h4">Sustainability</h3>
                        <p class="card-text">We are committed to protecting the environment and supporting the local community through sustainable practices.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
