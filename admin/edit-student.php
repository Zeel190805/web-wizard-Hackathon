<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Edit Student';
$errors = [];
$success = '';

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    header('Location: students.php?error=Invalid student ID');
    exit();
}

// Get student data
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                      LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                      WHERE u.id = ? AND u.role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php?error=Student not found');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate input
        $email = sanitize_input($_POST['email']);
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $course = sanitize_input($_POST['course']);
        $department = sanitize_input($_POST['department']);
        $year_of_study = (int)$_POST['year_of_study'];
        $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : null;
        $status = sanitize_input($_POST['status']);
        $address = sanitize_input($_POST['address']);
        
        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($first_name) || empty($last_name)) {
            $errors[] = 'First name and last name are required.';
        }
        
        if (empty($course)) {
            $errors[] = 'Course is required.';
        }
        
        if ($year_of_study < 1 || $year_of_study > 6) {
            $errors[] = 'Please select a valid year of study.';
        }
        
        if ($gpa !== null && ($gpa < 0 || $gpa > 4.0)) {
            $errors[] = 'GPA must be between 0.0 and 4.0.';
        }
        
        // Check if email already exists (but not for current student)
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $student_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email address is already registered by another user.';
            }
        }
        
        // If no errors, update the student
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Update user table
                $stmt = $pdo->prepare("UPDATE users SET email = ?, status = ? WHERE id = ?");
                $stmt->execute([$email, $status, $student_id]);
                
                // Update student profile
                $stmt = $pdo->prepare("UPDATE student_profiles SET 
                                     first_name = ?, last_name = ?, phone = ?, course = ?, 
                                     department = ?, year_of_study = ?, gpa = ?, address = ?
                                     WHERE user_id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $course, $department, $year_of_study, $gpa, $address, $student_id]);
                
                // Log activity
                log_activity($_SESSION['user_id'], 'Student Update', 'Updated student: ' . $first_name . ' ' . $last_name . ' (ID: ' . $student_id . ')');
                
                $pdo->commit();
                
                $success = 'Student updated successfully!';
                
                // Refresh student data
                $stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                                      LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                                      WHERE u.id = ? AND u.role = 'student'");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to update student. Please try again.';
                error_log('Student update error: ' . $e->getMessage());
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
                    <h4 class="mb-1">Edit Student</h4>
                    <p class="text-muted mb-0">Update student information</p>
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

            <!-- Edit Student Form -->
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
                                       value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a first name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a last name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                            </div>

                            <!-- Account Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-key me-2"></i>Account Information
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>" readonly>
                                <div class="form-text">Student ID cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">Account Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" <?php echo ($student['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($student['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($student['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>

                            <!-- Academic Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-graduation-cap me-2"></i>Academic Information
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="course" class="form-label">Course *</label>
                                <input type="text" class="form-control" id="course" name="course" 
                                       value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>" 
                                       placeholder="e.g., Computer Science" required>
                                <div class="invalid-feedback">Please provide a course.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($student['department'] ?? ''); ?>" 
                                       placeholder="e.g., Engineering">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="year_of_study" class="form-label">Year of Study *</label>
                                <select class="form-control" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($student['year_of_study'] ?? '') == $i ? 'selected' : ''; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="invalid-feedback">Please select a year of study.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="gpa" class="form-label">GPA</label>
                                <input type="number" class="form-control" id="gpa" name="gpa" 
                                       value="<?php echo $student['gpa'] ?? ''; ?>" 
                                       min="0" max="4.0" step="0.01" placeholder="e.g., 3.75">
                                <div class="form-text">GPA scale: 0.0 - 4.0</div>
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
                                        <i class="fas fa-save me-2"></i>Update Student
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
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include '../includes/footer.php'; ?>