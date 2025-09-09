<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    $role = get_user_role();
    $redirect_url = $role === 'admin' ? '/admin/dashboard.php' : 
                   ($role === 'provider' ? '/provider/dashboard.php' : '/customer/dashboard.php');
    redirect($redirect_url);
}

$error = '';
$success = '';
$role = sanitize_input($_GET['role'] ?? 'customer');

if (!in_array($role, ['customer', 'provider'])) {
    $role = 'customer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize_input($_POST['role'] ?? 'customer');
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Create user account
            $hashed_password = hash_password($password);
            $verification_token = generate_token();
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password, role, verification_token) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$full_name, $email, $phone, $hashed_password, $role, $verification_token])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Send verification email (simplified for demo)
                    // In production, implement proper email sending
                    log_activity($user_id, 'register', "New $role account created");
                    
                    $success = 'Account created successfully! Please check your email to verify your account.';
                    
                    // For demo purposes, auto-verify the account
                    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Register - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 d-none d-lg-flex align-items-center bg-primary text-white">
            <div class="p-5">
                <h1 class="display-4 fw-bold mb-4"><?= SITE_NAME ?></h1>
                <p class="lead mb-4">Join our platform and connect with thousands of vehicle service providers or offer your services to customers.</p>
                
                <?php if ($role === 'customer'): ?>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Find nearby service providers</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Track service requests</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Secure payment options</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Rate and review services</li>
                    </ul>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Manage your service offerings</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Accept service requests</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Generate custom invoices</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Track payments and commissions</li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 d-flex align-items-center">
            <div class="w-100 px-4 px-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Create Account</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Account Type</label>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="role" id="customer" 
                                                   value="customer" <?= $role === 'customer' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="customer">
                                                <i class="fas fa-user"></i> Customer
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="role" id="provider" 
                                                   value="provider" <?= $role === 'provider' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="provider">
                                                <i class="fas fa-wrench"></i> Service Provider
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control form-control-lg" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($phone ?? '') ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                    <small class="text-muted">At least 8 characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-bold">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>