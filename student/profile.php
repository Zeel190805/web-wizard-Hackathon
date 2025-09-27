<?php
require_once '../config/config.php';
require_login();

$page_title = 'My Profile';
$errors = [];
$success = '';

// Get current student data
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                      JOIN student_profiles sp ON u.id = sp.user_id 
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_info = pathinfo($_FILES['profile_picture']['name']);
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_info['extension']), $allowed_types)) {
                if ($_FILES['profile_picture']['size'] <= MAX_FILE_SIZE) {
                    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_info['extension'];
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        // Delete old profile picture
                        if ($student['profile_picture'] && file_exists('../' . $student['profile_picture'])) {
                            unlink('../' . $student['profile_picture']);
                        }
                        
                        $profile_picture = 'assets/uploads/profiles/' . $filename;
                        
                        // Update profile picture in database
                        $stmt = $pdo->prepare("UPDATE student_profiles SET profile_picture = ? WHERE user_id = ?");
                        $stmt->execute([$profile_picture, $_SESSION['user_id']]);
                        
                        $_SESSION['profile_picture'] = $profile_picture;
                    } else {
                        $errors[] = 'Failed to upload profile picture.';
                    }
                } else {
                    $errors[] = 'Profile picture is too large. Maximum size is 5MB.';
                }
            } else {
                $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            }
        }
        
        // Update profile information
        if (empty($errors)) {
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $course = sanitize_input($_POST['course']);
            $department = sanitize_input($_POST['department']);
            $year_of_study = (int)$_POST['year_of_study'];
            $bio = sanitize_input($_POST['bio']);
            $emergency_contact_name = sanitize_input($_POST['emergency_contact_name']);
            $emergency_contact_phone = sanitize_input($_POST['emergency_contact_phone']);
            
            // Validation
            if (empty($first_name) || empty($last_name)) {
                $errors[] = 'First name and last name are required.';
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE student_profiles SET 
                                         first_name = ?, last_name = ?, phone = ?, address = ?, 
                                         course = ?, department = ?, year_of_study = ?, bio = ?,
                                         emergency_contact_name = ?, emergency_contact_phone = ?
                                         WHERE user_id = ?");
                    $stmt->execute([$first_name, $last_name, $phone, $address, $course, $department, 
                                   $year_of_study, $bio, $emergency_contact_name, $emergency_contact_phone, 
                                   $_SESSION['user_id']]);
                    
                    // Update session name
                    $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
                    
                    log_activity($_SESSION['user_id'], 'Profile Update', 'Student profile updated');
                    
                    $success = 'Profile updated successfully!';
                    
                    // Refresh student data
                    $stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                                          JOIN student_profiles sp ON u.id = sp.user_id 
                                          WHERE u.id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $student = $stmt->fetch();
                    
                } catch (Exception $e) {
                    $errors[] = 'Failed to update profile. Please try again.';
                    error_log('Profile update error: ' . $e->getMessage());
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Picture Section -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block">
                        <?php if ($student['profile_picture']): ?>
                            <img src="<?php echo BASE_URL . $student['profile_picture']; ?>" 
                                 alt="Profile Picture" class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle" 
                                style="width: 40px; height: 40px;" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    
                    <h4 class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p class="text-primary"><?php echo htmlspecialchars($student['course'] ?: 'Course not set'); ?></p>
                    
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <strong class="d-block"><?php echo $student['year_of_study'] ?: 'N/A'; ?></strong>
                            <small class="text-muted">Year</small>
                        </div>
                        <div class="col-4">
                            <strong class="d-block"><?php echo $student['gpa'] ? number_format($student['gpa'], 2) : 'N/A'; ?></strong>
                            <small class="text-muted">GPA</small>
                        </div>
                        <div class="col-4">
                            <strong class="d-block"><?php echo date('Y', strtotime($student['created_at'])); ?></strong>
                            <small class="text-muted">Joined</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie text-info"></i> Quick Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Profile Completion</span>
                        <span class="fw-bold">85%</span>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" style="width: 85%"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Last Login</span>
                        <span class="fw-bold"><?php echo $student['last_login'] ? date('M j', strtotime($student['last_login'])) : 'Never'; ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Member Since</span>
                        <span class="fw-bold"><?php echo date('M Y', strtotime($student['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit text-primary"></i> Edit Profile
                    </h5>
                </div>
                <div class="card-body">
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
                    
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Personal Information -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-user"></i> Personal Information
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($student['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-control" id="department" name="department">
                                        <option value="">Select Department</option>
                                        <option value="Engineering" <?php echo $student['department'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Management" <?php echo $student['department'] === 'Management' ? 'selected' : ''; ?>>Management</option>
                                        <option value="Sciences" <?php echo $student['department'] === 'Sciences' ? 'selected' : ''; ?>>Sciences</option>
                                        <option value="Arts" <?php echo $student['department'] === 'Arts' ? 'selected' : ''; ?>>Arts</option>
                                        <option value="Other" <?php echo $student['department'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                        
                        <!-- Academic Information -->
                        <h6 class="text-primary mb-3 mt-4">
                            <i class="fas fa-graduation-cap"></i> Academic Information
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="course" class="form-label">Course/Program</label>
                                    <input type="text" class="form-control" id="course" name="course" 
                                           value="<?php echo htmlspecialchars($student['course']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="year_of_study" class="form-label">Year of Study</label>
                                    <select class="form-control" id="year_of_study" name="year_of_study">
                                        <option value="">Select Year</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $student['year_of_study'] == $i ? 'selected' : ''; ?>>
                                                Year <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($student['bio']); ?></textarea>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <h6 class="text-primary mb-3 mt-4">
                            <i class="fas fa-phone-alt"></i> Emergency Contact
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                           value="<?php echo htmlspecialchars($student['emergency_contact_name']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                           value="<?php echo htmlspecialchars($student['emergency_contact_phone']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera text-primary"></i> Update Profile Picture
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Choose Image</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                               accept="image/*" required>
                        <small class="text-muted">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <div id="imagePreview" class="text-center" style="display: none;">
                        <img id="previewImg" src="" alt="Preview" class="rounded" style="max-width: 200px; max-height: 200px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Image preview
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Auto-save functionality
    autoSaveForm('profileForm', '<?php echo BASE_URL; ?>api/auto-save-profile.php');
</script>

<?php include '../includes/footer.php'; ?>
