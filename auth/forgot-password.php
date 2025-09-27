<?php
require_once '../config/config.php';

$page_title = 'Forgot Password';

if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $email = sanitize_input($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                // Store reset token
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                $stmt->execute([$reset_token, $reset_expires, $email]);
                
                // In a real application, you would send an email here
                // For demo purposes, we'll just show the reset link
                $reset_link = BASE_URL . "auth/reset-password.php?token=$reset_token";
                
                $success = "Password reset instructions would be sent to your email. For demo purposes, use this link: <a href='$reset_link' target='_blank'>Reset Password</a>";
                
                log_activity($user['id'], 'Password Reset Request', 'Password reset requested');
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = "If an account with that email exists, you will receive password reset instructions.";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card fade-in">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Forgot Password?</h2>
                        <p class="text-muted">Enter your email address and we'll send you instructions to reset your password.</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address" required autofocus>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Reset Instructions
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Remember your password? 
                                <a href="login.php" class="text-primary fw-bold">Back to Login</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
