<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['admin']);

// Get dashboard statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$stats['total_users'] = $stmt->fetch()['count'];

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetch()['count'];

// Total providers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'");
$stats['total_providers'] = $stmt->fetch()['count'];

// Pending service centers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM service_centers WHERE status = 'pending'");
$stats['pending_centers'] = $stmt->fetch()['count'];

// Total service requests
$stmt = $pdo->query("SELECT COUNT(*) as count FROM service_requests");
$stats['total_requests'] = $stmt->fetch()['count'];

// Unpaid dues count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM commission_dues WHERE status = 'pending'");
$stats['unpaid_dues'] = $stmt->fetch()['count'];

// Recent activities
$recent_users = $pdo->query("
    SELECT * FROM users 
    WHERE role != 'admin' 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

$recent_centers = $pdo->query("
    SELECT sc.*, u.full_name, u.email 
    FROM service_centers sc
    JOIN users u ON sc.user_id = u.id
    ORDER BY sc.created_at DESC 
    LIMIT 5
")->fetchAll();

$page_title = 'Admin Dashboard - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <i class="fas fa-shield-alt"></i> <?= SITE_NAME ?> Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/service-centers.php">Service Centers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/transactions.php">Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/reports.php">Reports</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/admin/settings.php">Settings</a></li>
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
            <h1 class="h3 mb-4">Admin Dashboard</h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <div class="stat-icon primary mx-auto mb-2">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['total_users'] ?></h4>
                    <p class="card-text text-muted">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card success">
                <div class="card-body text-center">
                    <div class="stat-icon success mx-auto mb-2">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['total_customers'] ?></h4>
                    <p class="card-text text-muted">Customers</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card warning">
                <div class="card-body text-center">
                    <div class="stat-icon warning mx-auto mb-2">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['total_providers'] ?></h4>
                    <p class="card-text text-muted">Providers</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card danger">
                <div class="card-body text-center">
                    <div class="stat-icon danger mx-auto mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['pending_centers'] ?></h4>
                    <p class="card-text text-muted">Pending Approvals</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card primary">
                <div class="card-body text-center">
                    <div class="stat-icon primary mx-auto mb-2">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['total_requests'] ?></h4>
                    <p class="card-text text-muted">Service Requests</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card dashboard-card <?= $stats['unpaid_dues'] > 0 ? 'danger' : 'success' ?>">
                <div class="card-body text-center">
                    <div class="stat-icon <?= $stats['unpaid_dues'] > 0 ? 'danger' : 'success' ?> mx-auto mb-2">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h4 class="card-title"><?= $stats['unpaid_dues'] ?></h4>
                    <p class="card-text text-muted">Unpaid Dues</p>
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
                        <?php if ($stats['pending_centers'] > 0): ?>
                            <a href="/admin/service-centers.php?status=pending" class="btn btn-warning">
                                <i class="fas fa-clock"></i> Review Pending Centers (<?= $stats['pending_centers'] ?>)
                            </a>
                        <?php endif; ?>
                        <a href="/admin/users.php" class="btn btn-primary">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="/admin/service-centers.php" class="btn btn-outline-primary">
                            <i class="fas fa-store"></i> Manage Service Centers
                        </a>
                        <a href="/admin/transactions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-chart-line"></i> View Transactions
                        </a>
                        <a href="/admin/reports.php" class="btn btn-outline-info">
                            <i class="fas fa-file-alt"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Users</h5>
                    <a href="/admin/users.php" class="text-decoration-none">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_users)): ?>
                        <p class="text-muted text-center">No users yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_users as $user): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="provider-avatar me-3">
                                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= ucfirst($user['role']) ?> • <?= time_ago($user['created_at']) ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Service Centers -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Service Centers</h5>
                    <a href="/admin/service-centers.php" class="text-decoration-none">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_centers)): ?>
                        <p class="text-muted text-center">No service centers yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_centers as $center): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="provider-avatar me-3">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($center['business_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($center['full_name']) ?> • <?= time_ago($center['created_at']) ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?= 
                                    $center['status'] === 'approved' ? 'success' : 
                                    ($center['status'] === 'pending' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst($center['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>