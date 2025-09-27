<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Add Student';
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
        $department = sanitize_input($_POST['department']);
        $year_of_study = (int)$_POST['year_of_study'];
        $status = sanitize_input($_POST['status']);
        
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
        
        if (empty($course)) {
            $errors[] = 'Course is required.';
        }
        
        if ($year_of_study < 1 || $year_of_study > 6) {
            $errors[] = 'Please select a valid year of study.';
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
        
        // If no errors, create the student
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, 'student', ?, 1)");
                $stmt->execute([$email, $password_hash, $status]);
                $user_id = $pdo->lastInsertId();
                
                // Insert student profile
                $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_id, first_name, last_name, phone, course, department, year_of_study) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_id, $first_name, $last_name, $phone, $course, $department, $year_of_study]);
                
                // Log activity
                log_activity($_SESSION['user_id'], 'Student Creation', 'Created new student: ' . $first_name . ' ' . $last_name . ' (' . $student_id . ')');
                
                $pdo->commit();
                
                $success = 'Student created successfully!';
                
                // Redirect after successful creation
                header('Location: students.php?success=Student created successfully');
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to create student. Please try again.';
                error_log('Student creation error: ' . $e->getMessage());
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Add New Student</h4>
                    <p class="text-muted mb-0">Create a new student account</p>
                </div>
                <a href="students.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                </a>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Student Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row g-4">
                            <!-- Personal Information -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a first name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a last name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>

                            <!-- Account Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-key me-2"></i>Account Information
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                <div class="form-text">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</div>
                                <div class="invalid-feedback">Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Please confirm your password.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">Account Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Academic Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-graduation-cap me-2"></i>Academic Information
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a student ID.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="course" class="form-label">Course *</label>
                                <input type="text" class="form-control" id="course" name="course" 
                                       value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>" 
                                       placeholder="e.g., Computer Science" required>
                                <div class="invalid-feedback">Please provide a course.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" 
                                       placeholder="e.g., Engineering">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="year_of_study" class="form-label">Year of Study *</label>
                                <select class="form-control" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($_POST['year_of_study'] ?? '') == $i ? 'selected' : ''; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="invalid-feedback">Please select a year of study.</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="students.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Student
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // Check password confirmation
                var password = document.getElementById('password').value;
                var confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
                } else {
                    document.getElementById('confirm_password').setCustomValidity('');
                }
                
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);
    // You can add visual password strength indicator here
});

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    return strength;
}
</script>

<?php include '../includes/footer.php'; ?>