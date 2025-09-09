<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['customer']);

$user_id = $_SESSION['user_id'];

// Get all service requests for this customer
$stmt = $pdo->prepare("
    SELECT sr.*, s.name as service_name, s.category, sc.business_name, u.full_name as provider_name,
           v.make, v.model, v.year, v.license_plate
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    LEFT JOIN service_centers sc ON sr.service_center_id = sc.id
    LEFT JOIN users u ON sc.user_id = u.id
    JOIN vehicles v ON sr.vehicle_id = v.id
    WHERE sr.customer_id = ?
    ORDER BY sr.requested_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

$page_title = 'My Service Requests - ' . SITE_NAME;
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
                <h1 class="h3 mb-0">My Service Requests</h1>
                <a href="/customer/book-service.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Book New Service
                </a>
            </div>
        </div>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
                        <h4>No Service Requests Yet</h4>
                        <p class="text-muted mb-4">You haven't made any service requests yet. Book your first service to get started!</p>
                        <a href="/customer/book-service.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i> Book Your First Service
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Service</th>
                                        <th>Vehicle</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($request['service_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $request['category'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?= htmlspecialchars($request['year'] . ' ' . $request['make'] . ' ' . $request['model']) ?>
                                                    <?php if ($request['license_plate']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($request['license_plate']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $request['business_name'] ? htmlspecialchars($request['business_name']) : '<em class="text-muted">Not assigned</em>' ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= $request['status'] ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <?= date('M d, Y', strtotime($request['requested_at'])) ?>
                                                    <br>
                                                    <small class="text-muted"><?= date('h:i A', strtotime($request['requested_at'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/customer/request-details.php?id=<?= $request['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <a href="/customer/cancel-request.php?id=<?= $request['id'] ?>" 
                                                           class="btn btn-sm btn-outline-danger btn-delete"
                                                           data-message="Are you sure you want to cancel this service request?">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>