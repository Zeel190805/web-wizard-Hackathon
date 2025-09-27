<?php
require_once '../config/config.php';

$page_title = 'Login';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter both email and password.';
        } else {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT u.*, sp.first_name, sp.last_name, sp.profile_picture FROM users u 
                                  LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                                  WHERE u.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $errors[] = 'Your account is inactive. Please contact administrator.';
                } else {
                    // Check for account lockout
                    if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
                        $errors[] = 'Account is temporarily locked due to too many failed login attempts.';
                    } else {
                        // Successful login
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: 'User';
                        $_SESSION['profile_picture'] = $user['profile_picture'];
                        $_SESSION['theme'] = $user['theme_preference'];
                        
                        // Reset login attempts
                        $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Handle "Remember Me"
                        if ($remember_me) {
                            $remember_token = bin2hex(random_bytes(32));
                            $expires = time() + REMEMBER_ME_DURATION;
                            
                            // Store token in database
                            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $stmt->execute([$remember_token, $user['id']]);
                            
                            // Set cookie
                            setcookie('remember_token', $remember_token, $expires, '/', '', true, true);
                        }
                        
                        // Log activity
                        log_activity($user['id'], 'Login', 'User logged in successfully');
                        
                        // Redirect based on role
                        $redirect = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php';
                        header('Location: ' . BASE_URL . $redirect);
                        exit();
                    }
                }
            } else {
                // Invalid credentials - increment login attempts
                if ($user) {
                    $attempts = $user['login_attempts'] + 1;
                    $locked_until = null;
                    
                    // Lock account after 5 failed attempts
                    if ($attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', time() + 900); // 15 minutes
                        $errors[] = 'Too many failed attempts. Account locked for 15 minutes.';
                    } else {
                        $errors[] = 'Invalid email or password. Attempt ' . $attempts . ' of 5.';
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
                    $stmt->execute([$attempts, $locked_until, $user['id']]);
                } else {
                    $errors[] = 'Invalid email or password.';
                }
                
                // Log failed attempt
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Failed Login', ?, ?)");
                $stmt->execute([$user['id'] ?? 0, 'Failed login attempt for email: ' . $email, $_SERVER['REMOTE_ADDR']]);
            }
        }
    }
}

// Check for "Remember Me" cookie
if (isset($_COOKIE['remember_token']) && !is_logged_in()) {
    $stmt = $pdo->prepare("SELECT u.*, sp.first_name, sp.last_name, sp.profile_picture FROM users u 
                          LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                          WHERE u.remember_token = ? AND u.status = 'active'");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    
    if ($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: 'User';
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['theme'] = $user['theme_preference'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        log_activity($user['id'], 'Auto Login', 'User logged in via remember token');
        
        $redirect = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php';
        header('Location: ' . BASE_URL . $redirect);
        exit();
    } else {
        // Invalid token, remove cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
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
                        <i class="fas fa-graduation-cap fa-4x text-primary mb-3"></i>
                        <h2 class="card-title">Welcome Back</h2>
                        <p class="text-muted">Sign in to your student portal</p>
                    </div>
                    
                    <?php if (isset($_GET['timeout'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i>
                            Your session has expired. Please log in again.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email" required autofocus>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        <i class="fas fa-heart"></i> Remember me
                                    </label>
                                </div>
                            </div>
                            <div class="col text-end">
                                <a href="forgot-password.php" class="text-primary">
                                    <i class="fas fa-question-circle"></i> Forgot Password?
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-primary fw-bold">Create Account</a>
                            </p>
                        </div>
                    </form>
                    
                    <!-- Demo Accounts Info -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle text-info"></i> Demo Accounts
                        </h6>
                        <small class="text-muted">
                            <strong>Admin:</strong> admin@portal.com / password<br>
                            <strong>Student:</strong> john.doe@student.com / password
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Password visibility toggle
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const eye = document.getElementById(fieldId + '-eye');
        
        if (field.type === 'password') {
            field.type = 'text';
            eye.classList.remove('fa-eye');
            eye.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            eye.classList.remove('fa-eye-slash');
            eye.classList.add('fa-eye');
        }
    }
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        if (!email || !password) {
            e.preventDefault();
            showToast('Missing Information', 'Please enter both email and password.', 'error');
            return;
        }
        
        if (!email.includes('@') || !email.includes('.')) {
            e.preventDefault();
            document.getElementById('email').classList.add('is-invalid');
            showToast('Invalid Email', 'Please enter a valid email address.', 'error');
            return;
        }
        
        // Show loading
        showLoading();
    });
    
    // Auto-fill demo credentials
    function fillDemo(type) {
        if (type === 'admin') {
            document.getElementById('email').value = 'admin@portal.com';
            document.getElementById('password').value = 'password';
        } else if (type === 'student') {
            document.getElementById('email').value = 'john.doe@student.com';
            document.getElementById('password').value = 'password';
        }
    }
    
    // Add click handlers for demo accounts
    document.addEventListener('DOMContentLoaded', function() {
        const demoText = document.querySelector('.bg-light small');
        if (demoText) {
            demoText.innerHTML = demoText.innerHTML.replace(
                'admin@portal.com / password',
                '<a href="#" onclick="fillDemo(\'admin\')" class="text-primary">admin@portal.com / password</a>'
            ).replace(
                'john.doe@student.com / password',
                '<a href="#" onclick="fillDemo(\'student\')" class="text-primary">john.doe@student.com / password</a>'
            );
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
