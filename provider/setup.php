<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_role(['provider']);

$user_id = $_SESSION['user_id'];

// Check if service center already exists
$stmt = $pdo->prepare("SELECT * FROM service_centers WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing_center = $stmt->fetch();

if ($existing_center) {
    redirect('/provider/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = sanitize_input($_POST['business_name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $state = sanitize_input($_POST['state'] ?? '');
    $zipcode = sanitize_input($_POST['zipcode'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    // Validation
    if (empty($business_name) || empty($address) || empty($city) || empty($state)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO service_centers (user_id, business_name, description, address, city, state, zipcode, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $business_name, $description, $address, $city, $state, $zipcode, $phone])) {
                $success = 'Service center created successfully! Your application is pending approval.';
                
                // Send notification to admin (simplified)
                log_activity($user_id, 'service_center_created', "New service center: $business_name");
                
                // Redirect after a delay
                header("refresh:3;url=/provider/dashboard.php");
            } else {
                $error = 'Failed to create service center. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Failed to create service center. Please try again.';
        }
    }
}

$page_title = 'Service Center Setup - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-store"></i> Setup Your Service Center
                    </h3>
                    <p class="text-muted mt-2">Complete your business profile to start receiving service requests</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                            <br><small>Redirecting to dashboard...</small>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="business_name" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" 
                                       value="<?= htmlspecialchars($business_name ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Business Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($phone ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Business Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Describe your services and specialties..."><?= htmlspecialchars($description ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Business Address *</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   placeholder="Street address" value="<?= htmlspecialchars($address ?? '') ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= htmlspecialchars($city ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State *</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?= htmlspecialchars($state ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="zipcode" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zipcode" name="zipcode" 
                                       value="<?= htmlspecialchars($zipcode ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> What happens next?</h6>
                            <ul class="mb-0">
                                <li>Your service center will be submitted for admin approval</li>
                                <li>You'll be notified once approved (usually within 24-48 hours)</li>
                                <li>After approval, you can add services and start receiving requests</li>
                                <li>Make sure all information is accurate as it will be visible to customers</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check"></i> Create Service Center
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>