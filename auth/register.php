<?php
require_once '../config/config.php';

$page_title = 'Register';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate input
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $student_id = sanitize_input($_POST['student_id']);
        $phone = sanitize_input($_POST['phone']);
        $course = sanitize_input($_POST['course']);
        $year_of_study = (int)$_POST['year_of_study'];
        
        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($first_name) || empty($last_name)) {
            $errors[] = 'First name and last name are required.';
        }
        
        if (empty($student_id)) {
            $errors[] = 'Student ID is required.';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already registered.';
            }
        }
        
        // Check if student ID already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_id = ?");
            $stmt->execute([$student_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Student ID is already registered.';
            }
        }
        
        // If no errors, create the user
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, 'student', 'active')");
                $stmt->execute([$email, $password_hash]);
                $user_id = $pdo->lastInsertId();
                
                // Insert student profile
                $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_id, first_name, last_name, phone, course, year_of_study) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_id, $first_name, $last_name, $phone, $course, $year_of_study]);
                
                // Log activity
                log_activity($user_id, 'Registration', 'New student account created');
                
                $pdo->commit();
                
                $_SESSION['flash_message'] = 'Registration successful! Please log in to continue.';
                $_SESSION['flash_type'] = 'success';
                header('Location: login.php');
                exit();
                
            } catch (Exception $e) {
                $pdo->rollback();
                $errors[] = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card fade-in">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Create Student Account</h2>
                        <p class="text-muted">Join our student portal today</p>
                    </div>
                    
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
                    
                    <form method="POST" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user"></i> First Name *
                                    </label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user"></i> Last Name *
                                    </label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address *
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">
                                        <i class="fas fa-id-card"></i> Student ID *
                                    </label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" 
                                           placeholder="e.g., STU001" required>
                                    <div class="invalid-feedback">Please enter your student ID.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="course" class="form-label">
                                        <i class="fas fa-book"></i> Course/Program
                                    </label>
                                    <select class="form-control" id="course" name="course">
                                        <option value="">Select your course</option>
                                        <option value="Computer Science" <?php echo ($_POST['course'] ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Business Administration" <?php echo ($_POST['course'] ?? '') === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Mechanical Engineering" <?php echo ($_POST['course'] ?? '') === 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                        <option value="Electrical Engineering" <?php echo ($_POST['course'] ?? '') === 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                                        <option value="Mathematics" <?php echo ($_POST['course'] ?? '') === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                        <option value="Physics" <?php echo ($_POST['course'] ?? '') === 'Physics' ? 'selected' : ''; ?>>Physics</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="year_of_study" class="form-label">
                                        <i class="fas fa-calendar"></i> Year
                                    </label>
                                    <select class="form-control" id="year_of_study" name="year_of_study">
                                        <option value="">Year</option>
                                        <option value="1" <?php echo ($_POST['year_of_study'] ?? '') === '1' ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2" <?php echo ($_POST['year_of_study'] ?? '') === '2' ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3" <?php echo ($_POST['year_of_study'] ?? '') === '3' ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4" <?php echo ($_POST['year_of_study'] ?? '') === '4' ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="5" <?php echo ($_POST['year_of_study'] ?? '') === '5' ? 'selected' : ''; ?>>5th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               onkeyup="updatePasswordStrength(this.value, 'password-strength')" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="password-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength" id="password-strength"></div>
                                    <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                                    <div class="invalid-feedback">Password is required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> Confirm Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please confirm your password.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                                    <a href="#" class="text-primary">Privacy Policy</a> *
                                </label>
                                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-primary fw-bold">Login here</a>
                        </p>
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
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (!validateForm('registerForm')) {
            e.preventDefault();
            showToast('Validation Error', 'Please fill in all required fields correctly.', 'error');
        }
        
        // Check password match
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            document.getElementById('confirm_password').classList.add('is-invalid');
            showToast('Password Mismatch', 'Passwords do not match.', 'error');
        }
    });
    
    // Real-time password confirmation check
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            if (confirmPassword) this.classList.add('is-valid');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
