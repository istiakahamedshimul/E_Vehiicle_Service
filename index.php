<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Welcome to ' . SITE_NAME;
?>

<?php include 'includes/header.php'; ?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="fas fa-car"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/services.php">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/about.php">About</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <?php 
                    $role = get_user_role();
                    $dashboard_url = $role === 'admin' ? '/admin/dashboard.php' : 
                                   ($role === 'provider' ? '/provider/dashboard.php' : '/customer/dashboard.php');
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $dashboard_url ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Need Vehicle Service?</h1>
                <p class="lead mb-4">
                    Find trusted mechanics, fuel delivery, towing services, and more near you. 
                    Get quality service with transparent pricing and real-time tracking.
                </p>
                <?php if (!is_logged_in()): ?>
                    <div class="d-flex gap-3">
                        <a href="/auth/register.php?role=customer" class="btn btn-primary btn-lg">
                            <i class="fas fa-user"></i> Customer Signup
                        </a>
                        <a href="/auth/register.php?role=provider" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-wrench"></i> Service Provider
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= $dashboard_url ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <img src="https://images.pexels.com/photos/3806288/pexels-photo-3806288.jpeg?auto=compress&cs=tinysrgb&w=600" 
                     alt="Vehicle Service" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Why Choose Our Platform?</h2>
                <p class="lead text-muted">Connecting vehicle owners with trusted service providers</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="stat-icon primary mx-auto mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5 class="card-title">Location-Based Service</h5>
                        <p class="card-text">Find service providers near your location with accurate distance calculation and real-time availability.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="stat-icon success mx-auto mb-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="card-title">Trusted Providers</h5>
                        <p class="card-text">All service providers are verified and rated by customers to ensure quality and reliability.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="stat-icon warning mx-auto mb-3">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h5 class="card-title">Secure Payments</h5>
                        <p class="card-text">Pay securely online or with cash. Transparent pricing with no hidden fees.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Available Services</h2>
                <p class="lead text-muted">Comprehensive vehicle services at your fingertips</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-wrench fa-3x text-primary mb-3"></i>
                        <h6>Mechanic</h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-gas-pump fa-3x text-primary mb-3"></i>
                        <h6>Fuel Delivery</h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h6>Towing</h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-car-battery fa-3x text-primary mb-3"></i>
                        <h6>Battery</h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-tire fa-3x text-primary mb-3"></i>
                        <h6>Tire Service</h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                        <h6>Other</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4">Join thousands of satisfied customers and service providers</p>
        <?php if (!is_logged_in()): ?>
            <a href="/auth/register.php" class="btn btn-light btn-lg">
                <i class="fas fa-rocket"></i> Get Started Today
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?= SITE_NAME ?></h5>
                <p class="mb-0">Connecting vehicle owners with trusted service providers.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>