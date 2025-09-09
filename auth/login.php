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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verify_password($password, $user['password'])) {
            if (!$user['email_verified']) {
                $error = 'Please verify your email address before logging in.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                log_activity($user['id'], 'login', 'User logged in successfully');
                
                $redirect_url = $user['role'] === 'admin' ? '/admin/dashboard.php' : 
                               ($user['role'] === 'provider' ? '/provider/dashboard.php' : '/customer/dashboard.php');
                redirect($redirect_url);
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Login - ' . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-lg-6 d-none d-lg-flex align-items-center bg-primary text-white">
            <div class="p-5">
                <h1 class="display-4 fw-bold mb-4"><?= SITE_NAME ?></h1>
                <p class="lead mb-4">Welcome back! Sign in to access your dashboard and manage your vehicle services.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Track your service requests</li>
                    <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> Manage your profile</li>
                    <li class="mb-2"><i class="fas fa-check text-warning me-2"></i> View payment history</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6 d-flex align-items-center">
            <div class="w-100 px-4 px-lg-5">
                <div class="card border-0 shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Sign In</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-decoration-none fw-bold">Sign up</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>