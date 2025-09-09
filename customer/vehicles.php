<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['customer']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_vehicle') {
            $make = sanitize_input($_POST['make'] ?? '');
            $model = sanitize_input($_POST['model'] ?? '');
            $year = (int)($_POST['year'] ?? 0);
            $license_plate = sanitize_input($_POST['license_plate'] ?? '');
            $color = sanitize_input($_POST['color'] ?? '');
            $is_default = isset($_POST['is_default']) ? 1 : 0;
            
            if (empty($make) || empty($model) || $year < 1900 || $year > date('Y') + 1) {
                $error = 'Please fill in all required fields with valid information.';
            } else {
                try {
                    // If this is set as default, unset other defaults
                    if ($is_default) {
                        $stmt = $pdo->prepare("UPDATE vehicles SET is_default = FALSE WHERE customer_id = ?");
                        $stmt->execute([$user_id]);
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO vehicles (customer_id, make, model, year, license_plate, color, is_default) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$user_id, $make, $model, $year, $license_plate, $color, $is_default])) {
                        $success = 'Vehicle added successfully!';
                    } else {
                        $error = 'Failed to add vehicle. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to add vehicle. Please try again.';
                }
            }
        } elseif ($_POST['action'] === 'set_default') {
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            
            // Verify vehicle belongs to user
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND customer_id = ?");
            $stmt->execute([$vehicle_id, $user_id]);
            
            if ($stmt->fetch()) {
                // Unset all defaults first
                $stmt = $pdo->prepare("UPDATE vehicles SET is_default = FALSE WHERE customer_id = ?");
                $stmt->execute([$user_id]);
                
                // Set new default
                $stmt = $pdo->prepare("UPDATE vehicles SET is_default = TRUE WHERE id = ? AND customer_id = ?");
                if ($stmt->execute([$vehicle_id, $user_id])) {
                    $success = 'Default vehicle updated successfully!';
                } else {
                    $error = 'Failed to update default vehicle.';
                }
            } else {
                $error = 'Invalid vehicle selected.';
            }
        }
    }
}

// Handle vehicle deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $vehicle_id = (int)$_GET['delete'];
    
    // Check if vehicle is used in any service requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE vehicle_id = ?");
    $stmt->execute([$vehicle_id]);
    $usage_count = $stmt->fetch()['count'];
    
    if ($usage_count > 0) {
        $error = 'Cannot delete vehicle as it has been used in service requests.';
    } else {
        // Verify vehicle belongs to user and delete
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND customer_id = ?");
        if ($stmt->execute([$vehicle_id, $user_id])) {
            $success = 'Vehicle deleted successfully!';
        } else {
            $error = 'Failed to delete vehicle.';
        }
    }
}

// Get user's vehicles
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll();

$page_title = 'My Vehicles - ' . SITE_NAME;
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
                    <a class="nav-link" href="/customer/book-service.php">Book Service</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/customer/my-requests.php">My Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/customer/vehicles.php">My Vehicles</a>
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
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">My Vehicles</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="fas fa-plus"></i> Add Vehicle
                </button>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (empty($vehicles)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-car fa-4x text-muted mb-4"></i>
                        <h4>No Vehicles Added</h4>
                        <p class="text-muted mb-4">Add your vehicles to make booking services easier and faster.</p>
                        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                            <i class="fas fa-plus"></i> Add Your First Vehicle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <?= htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?>
                                </h5>
                                <?php if ($vehicle['is_default']): ?>
                                    <span class="badge bg-primary">Default</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vehicle-details">
                                <?php if ($vehicle['license_plate']): ?>
                                    <p class="mb-2">
                                        <i class="fas fa-id-card text-muted me-2"></i>
                                        <strong>License Plate:</strong> <?= htmlspecialchars($vehicle['license_plate']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($vehicle['color']): ?>
                                    <p class="mb-2">
                                        <i class="fas fa-palette text-muted me-2"></i>
                                        <strong>Color:</strong> <?= htmlspecialchars($vehicle['color']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    Added <?= date('M d, Y', strtotime($vehicle['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100" role="group">
                                <?php if (!$vehicle['is_default']): ?>
                                    <form method="POST" class="flex-fill">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-star"></i> Set Default
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="?delete=<?= $vehicle['id'] ?>" 
                                   class="btn btn-outline-danger btn-sm btn-delete <?= !$vehicle['is_default'] ? 'ms-2' : '' ?>"
                                   data-message="Are you sure you want to delete this vehicle?">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_vehicle">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="make" class="form-label">Make</label>
                            <input type="text" class="form-control" id="make" name="make" placeholder="e.g., Toyota" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" placeholder="e.g., Camry" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" 
                                   min="1900" max="<?= date('Y') + 1 ?>" placeholder="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" placeholder="e.g., Red">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">License Plate</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" 
                               placeholder="e.g., ABC-1234">
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">
                            Set as default vehicle
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>