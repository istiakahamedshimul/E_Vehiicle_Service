<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['customer']);

$user_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [];

// Total service requests
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE customer_id = ?");
$stmt->execute([$user_id]);
$stats['total_requests'] = $stmt->fetch()['count'];

// Pending requests
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE customer_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$stats['pending_requests'] = $stmt->fetch()['count'];

// Completed requests
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE customer_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$stats['completed_requests'] = $stmt->fetch()['count'];

// Recent service requests
$stmt = $pdo->prepare("
    SELECT sr.*, s.name as service_name, sc.business_name, u.full_name as provider_name
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    LEFT JOIN service_centers sc ON sr.service_center_id = sc.id
    LEFT JOIN users u ON sc.user_id = u.id
    WHERE sr.customer_id = ?
    ORDER BY sr.requested_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_requests = $stmt->fetchAll();

$page_title = 'Customer Dashboard - ' . SITE_NAME;
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
                    <a class="nav-link active" href="/customer/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/customer/book-service.php">Book Service</a>
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

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
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
        <div class="col-lg-4 col-md-6 mb-3">
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
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card dashboard-card success">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1"><?= $stats['completed_requests'] ?></h5>
                        <p class="card-text text-muted">Completed Requests</p>
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
                        <a href="/customer/book-service.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Book New Service
                        </a>
                        <a href="/customer/vehicles.php" class="btn btn-outline-primary">
                            <i class="fas fa-car"></i> Manage Vehicles
                        </a>
                        <a href="/customer/my-requests.php" class="btn btn-outline-secondary">
                            <i class="fas fa-history"></i> View All Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Service Requests -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Service Requests</h5>
                    <a href="/customer/my-requests.php" class="text-decoration-none">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5>No Service Requests Yet</h5>
                            <p class="text-muted">Book your first service to get started!</p>
                            <a href="/customer/book-service.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book Service
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($request['service_name']) ?></strong>
                                            </td>
                                            <td>
                                                <?= $request['business_name'] ? htmlspecialchars($request['business_name']) : '<em class="text-muted">Not assigned</em>' ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= $request['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($request['requested_at'])) ?></td>
                                            <td>
                                                <a href="/customer/request-details.php?id=<?= $request['id'] ?>" 
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