<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['provider']);

$user_id = $_SESSION['user_id'];

// Get or create service center
$stmt = $pdo->prepare("SELECT * FROM service_centers WHERE user_id = ?");
$stmt->execute([$user_id]);
$service_center = $stmt->fetch();

if (!$service_center) {
    // Redirect to setup if no service center exists
    redirect('/provider/setup.php');
}

$service_center_id = $service_center['id'];

// Get dashboard statistics
$stats = [];

// Total service requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests sr 
    JOIN services s ON sr.service_id = s.id 
    WHERE s.service_center_id = ?
");
$stmt->execute([$service_center_id]);
$stats['total_requests'] = $stmt->fetch()['count'];

// Pending requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests sr 
    JOIN services s ON sr.service_id = s.id 
    WHERE s.service_center_id = ? AND sr.status = 'pending'
");
$stmt->execute([$service_center_id]);
$stats['pending_requests'] = $stmt->fetch()['count'];

// Active requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests sr 
    JOIN services s ON sr.service_id = s.id 
    WHERE s.service_center_id = ? AND sr.status IN ('accepted', 'in_progress')
");
$stmt->execute([$service_center_id]);
$stats['active_requests'] = $stmt->fetch()['count'];

// Unpaid dues
$stats['unpaid_dues'] = $service_center['unpaid_dues_count'];

// Recent service requests
$stmt = $pdo->prepare("
    SELECT sr.*, s.name as service_name, u.full_name as customer_name, u.phone as customer_phone,
           v.make, v.model, v.year
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN users u ON sr.customer_id = u.id
    JOIN vehicles v ON sr.vehicle_id = v.id
    WHERE s.service_center_id = ?
    ORDER BY sr.requested_at DESC
    LIMIT 5
");
$stmt->execute([$service_center_id]);
$recent_requests = $stmt->fetchAll();

$page_title = 'Provider Dashboard - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <i class="fas fa-wrench"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/provider/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/provider/requests.php">Service Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/provider/services.php">My Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/provider/invoices.php">Invoices</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/provider/dues.php">Commission Dues</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/provider/profile.php">Business Profile</a></li>
                        <li><a class="dropdown-item" href="/provider/reviews.php">Reviews</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/auth/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Welcome back, <?= htmlspecialchars($service_center['business_name']) ?>!</h1>
            
            <?php if ($service_center['status'] !== 'approved'): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Account Status: <?= ucfirst($service_center['status']) ?></h5>
                    <?php if ($service_center['status'] === 'pending'): ?>
                        <p class="mb-0">Your service center is pending approval. You will be notified once approved.</p>
                    <?php elseif ($service_center['status'] === 'rejected'): ?>
                        <p class="mb-0">Your service center application was rejected. Please contact support for more information.</p>
                    <?php elseif ($service_center['status'] === 'suspended'): ?>
                        <p class="mb-0">Your service center is suspended. Please contact support to resolve this issue.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$service_center['is_published'] && $service_center['status'] === 'approved'): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-ban"></i> Service Center Unpublished</h5>
                    <p class="mb-0">Your service center is not visible to customers. This may be due to unpaid commission dues.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1"><?= $stats['total_requests'] ?></h5>
                        <p class="card-text text-muted">Total Requests</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card warning">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1"><?= $stats['pending_requests'] ?></h5>
                        <p class="card-text text-muted">Pending Requests</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card success">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon success">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1"><?= $stats['active_requests'] ?></h5>
                        <p class="card-text text-muted">Active Services</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card <?= $stats['unpaid_dues'] > 0 ? 'danger' : 'success' ?>">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon <?= $stats['unpaid_dues'] > 0 ? 'danger' : 'success' ?>">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1"><?= $stats['unpaid_dues'] ?></h5>
                        <p class="card-text text-muted">Unpaid Dues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/provider/requests.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Service Requests
                        </a>
                        <a href="/provider/services.php" class="btn btn-outline-primary">
                            <i class="fas fa-cog"></i> Manage Services
                        </a>
                        <a href="/provider/invoices.php" class="btn btn-outline-secondary">
                            <i class="fas fa-file-invoice"></i> Create Invoice
                        </a>
                        <?php if ($stats['unpaid_dues'] > 0): ?>
                            <a href="/provider/dues.php" class="btn btn-warning">
                                <i class="fas fa-exclamation-triangle"></i> Pay Dues (<?= $stats['unpaid_dues'] ?>)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Service Center Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Center Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Rating:</strong> 
                        <span class="rating-stars">
                            <?php 
                            $rating = $service_center['rating_average'];
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fas fa-star <?= $i <= $rating ? '' : 'empty' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        (<?= $service_center['total_reviews'] ?> reviews)
                    </p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= $service_center['status'] === 'approved' ? 'success' : 'warning' ?>">
                            <?= ucfirst($service_center['status']) ?>
                        </span>
                    </p>
                    <p class="mb-0"><strong>Published:</strong> 
                        <span class="badge bg-<?= $service_center['is_published'] ? 'success' : 'danger' ?>">
                            <?= $service_center['is_published'] ? 'Yes' : 'No' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Recent Service Requests -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Service Requests</h5>
                    <a href="/provider/requests.php" class="text-decoration-none">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5>No Service Requests Yet</h5>
                            <p class="text-muted">Service requests will appear here once customers start booking your services.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Service</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_requests as $request): ?>
                                        <tr>
                                            <td><strong>#<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                            <td><?= htmlspecialchars($request['service_name']) ?></td>
                                            <td>
                                                <div>
                                                    <?= htmlspecialchars($request['customer_name']) ?>
                                                    <?php if ($request['customer_phone']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($request['customer_phone']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($request['year'] . ' ' . $request['make'] . ' ' . $request['model']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $request['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($request['requested_at'])) ?></td>
                                            <td>
                                                <a href="/provider/request-details.php?id=<?= $request['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
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
</div>

<?php include '../includes/footer.php'; ?>