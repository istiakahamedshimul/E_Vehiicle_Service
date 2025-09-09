<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['customer']);

$user_id = $_SESSION['user_id'];
$request_id = (int)($_GET['id'] ?? 0);

// Get service request details
$stmt = $pdo->prepare("
    SELECT sr.*, s.name as service_name, s.category, s.description as service_description,
           sc.business_name, sc.phone as provider_phone, sc.address as provider_address,
           u.full_name as provider_name, u.phone as provider_contact,
           v.make, v.model, v.year, v.license_plate, v.color,
           i.invoice_number, i.total_amount, i.payment_status, i.payment_method
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    LEFT JOIN service_centers sc ON sr.service_center_id = sc.id
    LEFT JOIN users u ON sc.user_id = u.id
    JOIN vehicles v ON sr.vehicle_id = v.id
    LEFT JOIN invoices i ON sr.id = i.service_request_id
    WHERE sr.id = ? AND sr.customer_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    set_flash_message('error', 'Service request not found.');
    redirect('/customer/my-requests.php');
}

// Handle review submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($request['status'] === 'completed' && $request['service_center_id']) {
        $rating = (int)($_POST['rating'] ?? 0);
        $review_text = sanitize_input($_POST['review_text'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a valid rating.';
        } else {
            // Check if review already exists
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE service_request_id = ?");
            $stmt->execute([$request_id]);
            
            if ($stmt->fetch()) {
                $error = 'You have already reviewed this service.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO reviews (customer_id, service_center_id, service_request_id, rating, review_text) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$user_id, $request['service_center_id'], $request_id, $rating, $review_text])) {
                        // Update service center rating
                        update_service_center_rating($request['service_center_id']);
                        $success = 'Thank you for your review!';
                    } else {
                        $error = 'Failed to submit review. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to submit review. Please try again.';
                }
            }
        }
    }
}

// Check if review exists
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE service_request_id = ?");
$stmt->execute([$request_id]);
$existing_review = $stmt->fetch();

$page_title = 'Service Request Details - ' . SITE_NAME;
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
                    <a class="nav-link active" href="/customer/my-requests.php">My Requests</a>
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
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Service Request #<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                <a href="/customer/my-requests.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Requests
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Request Details -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Request Details</h5>
                    <span class="status-badge status-<?= $request['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Service Information</h6>
                            <p><strong>Service:</strong> <?= htmlspecialchars($request['service_name']) ?></p>
                            <p><strong>Category:</strong> <?= ucfirst(str_replace('_', ' ', $request['category'])) ?></p>
                            <p><strong>Requested:</strong> <?= date('M d, Y h:i A', strtotime($request['requested_at'])) ?></p>
                            
                            <?php if ($request['accepted_at']): ?>
                                <p><strong>Accepted:</strong> <?= date('M d, Y h:i A', strtotime($request['accepted_at'])) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($request['completed_at']): ?>
                                <p><strong>Completed:</strong> <?= date('M d, Y h:i A', strtotime($request['completed_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Vehicle Information</h6>
                            <p><strong>Vehicle:</strong> <?= htmlspecialchars($request['year'] . ' ' . $request['make'] . ' ' . $request['model']) ?></p>
                            <?php if ($request['license_plate']): ?>
                                <p><strong>License Plate:</strong> <?= htmlspecialchars($request['license_plate']) ?></p>
                            <?php endif; ?>
                            <?php if ($request['color']): ?>
                                <p><strong>Color:</strong> <?= htmlspecialchars($request['color']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Service Location</h6>
                    <p><?= htmlspecialchars($request['location_address']) ?></p>
                    
                    <?php if ($request['description']): ?>
                        <h6>Problem Description</h6>
                        <p><?= nl2br(htmlspecialchars($request['description'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($request['notes']): ?>
                        <h6>Provider Notes</h6>
                        <p><?= nl2br(htmlspecialchars($request['notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Review Section -->
            <?php if ($request['status'] === 'completed' && $request['service_center_id']): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Service Review</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($existing_review): ?>
                            <div class="alert alert-success">
                                <h6>Your Review</h6>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $existing_review['rating'] ? '' : 'empty' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($existing_review['review_text']): ?>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($existing_review['review_text'])) ?></p>
                                <?php endif; ?>
                                <small class="text-muted">Reviewed on <?= date('M d, Y', strtotime($existing_review['created_at'])) ?></small>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input">
                                        <input type="hidden" id="rating-input" name="rating" value="">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="far fa-star rating-star" data-rating="<?= $i ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Review (Optional)</label>
                                    <textarea class="form-control" id="review_text" name="review_text" rows="3" 
                                              placeholder="Share your experience with this service provider..."></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="btn btn-primary">
                                    <i class="fas fa-star"></i> Submit Review
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Provider & Payment Info -->
        <div class="col-lg-4">
            <!-- Provider Information -->
            <?php if ($request['business_name']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Service Provider</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($request['business_name']) ?></h6>
                        <?php if ($request['provider_name']): ?>
                            <p class="mb-2"><strong>Contact:</strong> <?= htmlspecialchars($request['provider_name']) ?></p>
                        <?php endif; ?>
                        <?php if ($request['provider_phone']): ?>
                            <p class="mb-2"><strong>Phone:</strong> 
                                <a href="tel:<?= htmlspecialchars($request['provider_phone']) ?>">
                                    <?= htmlspecialchars($request['provider_phone']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($request['provider_address']): ?>
                            <p class="mb-0"><strong>Address:</strong> <?= htmlspecialchars($request['provider_address']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Payment Information -->
            <?php if ($request['invoice_number']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Invoice:</strong> <?= htmlspecialchars($request['invoice_number']) ?></p>
                        <p><strong>Amount:</strong> <?= format_currency($request['total_amount']) ?></p>
                        <p><strong>Method:</strong> <?= ucfirst($request['payment_method']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $request['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= ucfirst($request['payment_status']) ?>
                            </span>
                        </p>
                        
                        <?php if ($request['payment_status'] === 'pending'): ?>
                            <div class="alert alert-info">
                                <small>Payment is pending. You will be notified once processed.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>