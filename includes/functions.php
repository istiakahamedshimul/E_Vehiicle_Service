<?php
// Utility Functions

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function require_login($redirect = true) {
    if (!is_logged_in()) {
        if ($redirect) {
            header('Location: /auth/login.php');
            exit();
        }
        return false;
    }
    return true;
}

function require_role($roles = []) {
    require_login();
    $user_role = get_user_role();
    if (!in_array($user_role, $roles)) {
        header('Location: /index.php');
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_messages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function generate_invoice_number() {
    return 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 3959; // Miles
    
    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);
    
    $dlat = $lat2_rad - $lat1_rad;
    $dlon = $lon2_rad - $lon1_rad;
    
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1_rad) * cos($lat2_rad) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

function send_notification($user_id, $title, $message, $type = 'info') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

function update_service_center_rating($service_center_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE service_centers 
        SET rating_average = (
            SELECT AVG(rating) FROM reviews 
            WHERE service_center_id = ? AND status = 'active'
        ),
        total_reviews = (
            SELECT COUNT(*) FROM reviews 
            WHERE service_center_id = ? AND status = 'active'
        )
        WHERE id = ?
    ");
    
    return $stmt->execute([$service_center_id, $service_center_id, $service_center_id]);
}

function check_and_suspend_service_centers() {
    global $pdo;
    
    $max_dues = 5; // This should come from settings
    
    $stmt = $pdo->prepare("
        UPDATE service_centers 
        SET is_published = FALSE 
        WHERE unpaid_dues_count >= ? AND is_published = TRUE
    ");
    
    return $stmt->execute([$max_dues]);
}

function log_activity($user_id, $action, $description = '') {
    // This can be implemented for audit trails
    error_log("User $user_id: $action - $description");
}
?>