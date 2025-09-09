<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['admin']);

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['center_id'])) {
        $center_id = (int)$_POST['center_id'];
        $action = $_POST['action'];
        
        if (in_array($action, ['approve', 'reject', 'suspend', 'publish', 'unpublish'])) {
            try {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE service_centers SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$center_id]);
                    $success = 'Service center approved successfully.';
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE service_centers SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$center_id]);
                    $success = 'Service center rejected.';
                } elseif ($action === 'suspend') {
                    $stmt = $pdo->prepare("UPDATE service_centers SET status = 'suspended', is_published = FALSE WHERE id = ?");
                    $stmt->execute([$center_id]);
                    $success = 'Service center suspended.';
                } elseif ($action === 'publish') {
                    $stmt = $pdo->prepare("UPDATE service_centers SET is_published = TRUE WHERE id = ?");
                    $stmt->execute([$center_id]);
                    $success = 'Service center published.';
                } elseif ($action === 'unpublish') {
                    $stmt = $pdo->prepare("UPDATE service_centers SET is_published = FALSE WHERE id = ?");
                    $stmt->execute([$center_id]);
                    $success = 'Service center unpublished.';
                }
            } catch (Exception $e) {
                $error = 'Failed to update service center.';
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected', 'suspended'])) {
    $where_conditions[] = "sc.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(sc.business_name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get service centers
$stmt = $pdo->prepare("
    SELECT sc.*, u.full_name, u.email, u.phone as user_phone
    FROM service_centers sc
    JOIN users u ON sc.user_id = u.id
    $where_clause
    ORDER BY sc.created_at DESC
");
$stmt->execute($params);
$service_centers = $stmt->fetchAll();

$page_title = 'Service Centers Management - ' . SITE_NAME;
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
                    <a class="nav-link" href="/admin/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/service-centers.php">Service Centers</a>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Service Centers Management</h1>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by business name, owner name, or email" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Service Centers Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($service_centers)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-store fa-3x text-muted mb-3"></i>
                    <h5>No Service Centers Found</h5>
                    <p class="text-muted">No service centers match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th>Unpaid Dues</th>
                                <th>Rating</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_centers as $center): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($center['business_name']) ?></strong>
                                        <?php if ($center['description']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($center['description'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?= htmlspecialchars($center['full_name']) ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($center['email']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($center['city'] . ', ' . $center['state']) ?>
                                        <?php if ($center['phone']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($center['phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $center['status'] === 'approved' ? 'success' : 
                                            ($center['status'] === 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= ucfirst($center['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $center['is_published'] ? 'success' : 'danger' ?>">
                                            <?= $center['is_published'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $center['unpaid_dues_count'] > 0 ? 'danger' : 'success' ?>">
                                            <?= $center['unpaid_dues_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = $center['rating_average'];
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <i class="fas fa-star <?= $i <= $rating ? '' : 'empty' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">(<?= $center['total_reviews'] ?>)</small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($center['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                    data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($center['status'] === 'pending'): ?>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($center['status'] === 'approved'): ?>
                                                    <?php if ($center['is_published']): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                                                                <input type="hidden" name="action" value="unpublish">
                                                                <button type="submit" class="dropdown-item text-warning">
                                                                    <i class="fas fa-eye-slash"></i> Unpublish
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                                                                <input type="hidden" name="action" value="publish">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="fas fa-eye"></i> Publish
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="center_id" value="<?= $center['id'] ?>">
                                                            <input type="hidden" name="action" value="suspend">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-ban"></i> Suspend
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a href="/admin/service-center-details.php?id=<?= $center['id'] ?>" 
                                                       class="dropdown-item">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
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

<?php include '../includes/footer.php'; ?>