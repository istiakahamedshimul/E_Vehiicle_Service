<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['customer']);

$user_id = $_SESSION['user_id'];

// Get user's vehicles
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll();

// Get available service categories
$service_categories = [
    'mechanic' => 'Mechanic',
    'fuel_delivery' => 'Fuel Delivery',
    'towing' => 'Towing',
    'battery' => 'Battery Service',
    'tire' => 'Tire Service',
    'other' => 'Other'
];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_category = sanitize_input($_POST['service_category'] ?? '');
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $location_address = sanitize_input($_POST['location_address'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    
    // Validation
    if (empty($service_category) || empty($vehicle_id) || empty($location_address)) {
        $error = 'Please fill in all required fields.';
    } elseif (!array_key_exists($service_category, $service_categories)) {
        $error = 'Please select a valid service category.';
    } else {
        // Verify vehicle belongs to user
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND customer_id = ?");
        $stmt->execute([$vehicle_id, $user_id]);
        
        if (!$stmt->fetch()) {
            $error = 'Invalid vehicle selected.';
        } else {
            // Find nearby service providers for this category
            $stmt = $pdo->prepare("
                SELECT s.id 
                FROM services s
                JOIN service_centers sc ON s.service_center_id = sc.id
                WHERE s.category = ? 
                AND s.is_active = TRUE 
                AND sc.status = 'approved' 
                AND sc.is_published = TRUE
                ORDER BY RAND()
                LIMIT 1
            ");
            $stmt->execute([$service_category]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $error = 'No service providers available for this category in your area.';
            } else {
                // Create service request
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO service_requests 
                        (customer_id, service_id, vehicle_id, description, location_address, location_latitude, location_longitude) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$user_id, $service['id'], $vehicle_id, $description, $location_address, $latitude, $longitude])) {
                        $request_id = $pdo->lastInsertId();
                        
                        // Send notification to service providers (simplified)
                        log_activity($user_id, 'service_request', "New service request created: $request_id");
                        
                        set_flash_message('success', 'Your service request has been submitted successfully! You will be notified when a provider accepts your request.');
                        redirect('/customer/my-requests.php');
                    } else {
                        $error = 'Failed to submit service request. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to submit service request. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Book Service - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <i class="fas fa-car"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/customer/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/customer/book-service.php">Book Service</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/customer/my-requests.php">My Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/customer/vehicles.php">My Vehicles</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/customer/profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="/customer/payment-methods.php">Payment Methods</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/auth/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-plus"></i> Book a New Service
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($vehicles)): ?>
                        <div class="alert alert-info">
                            <h5>No Vehicles Found</h5>
                            <p class="mb-2">You need to add a vehicle before booking a service.</p>
                            <a href="/customer/vehicles.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-car"></i> Add Vehicle
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <form method="POST" id="booking-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="service_category" class="form-label">Service Type</label>
                                    <select class="form-select" id="service_category" name="service_category" required>
                                        <option value="">Select Service Type</option>
                                        <?php foreach ($service_categories as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= ($service_category ?? '') === $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Vehicle</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Choose Vehicle</option>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?= $vehicle['id'] ?>" <?= ($vehicle_id ?? '') == $vehicle['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?>
                                                <?= $vehicle['license_plate'] ? ' (' . htmlspecialchars($vehicle['license_plate']) . ')' : '' ?>
                                                <?= $vehicle['is_default'] ? ' (Default)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <a href="/customer/vehicles.php" class="text-decoration-none">Manage vehicles</a>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location_address" class="form-label">Service Location</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="location_address" name="location_address" 
                                           placeholder="Enter full address where service is needed" 
                                           value="<?= htmlspecialchars($location_address ?? '') ?>" required>
                                    <button type="button" class="btn btn-outline-secondary" id="detect-location">
                                        <i class="fas fa-map-marker-alt"></i> Detect Location
                                    </button>
                                </div>
                                <input type="hidden" id="latitude" name="latitude" value="<?= $latitude ?? '' ?>">
                                <input type="hidden" id="longitude" name="longitude" value="<?= $longitude ?? '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Problem Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Describe the issue or service needed..."><?= htmlspecialchars($description ?? '') ?></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> What happens next?</h6>
                                <ul class="mb-0">
                                    <li>Your request will be sent to nearby service providers</li>
                                    <li>You'll be notified when a provider accepts your request</li>
                                    <li>The provider will contact you to confirm details</li>
                                    <li>You can track the service progress in real-time</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane"></i> Submit Service Request
                            </button>
                        </form>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$extra_scripts = '<script>
    // Form validation and location detection handled by main.js
</script>';
?>

<?php include '../includes/footer.php'; ?>