<?php
require_once '../config/config.php';
require_login();

$page_title = 'Dashboard';

// Handle theme toggle
if ($_POST['action'] ?? '' === 'toggle_theme') {
    $new_theme = $_SESSION['theme'] === 'dark' ? 'light' : 'dark';
    $_SESSION['theme'] = $new_theme;
    
    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$new_theme, $_SESSION['user_id']]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $new_theme]);
    exit;
}

// Get student profile information
$stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                      JOIN student_profiles sp ON u.id = sp.user_id 
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    // Create basic profile if doesn't exist
    $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, student_id, first_name, last_name) 
                          VALUES (?, ?, 'Student', 'User')");
    $student_id = 'STU' . str_pad($_SESSION['user_id'], 6, '0', STR_PAD_LEFT);
    $stmt->execute([$_SESSION['user_id'], $student_id]);
    
    // Reload student data
    $stmt = $pdo->prepare("SELECT u.*, sp.* FROM users u 
                          JOIN student_profiles sp ON u.id = sp.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1">Welcome back, <?php echo htmlspecialchars($student['first_name'] ?? 'Student'); ?>!</h4>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($student['course'] ?: 'Course not specified'); ?> • 
                                ID: <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($student['profile_picture'] ?? null): ?>
                                <img src="<?php echo BASE_URL . $student['profile_picture']; ?>" 
                                     alt="Profile" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-graduation-cap fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark"><?php echo $student['gpa'] ? number_format($student['gpa'], 1) : 'N/A'; ?></h5>
                    <p class="text-muted mb-0">Current GPA</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Year <?php echo $student['year_of_study'] ?? 'N/A'; ?></h5>
                    <p class="text-muted mb-0">Academic Year</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($student['department'] ?: 'Not set'); ?></h6>
                    <p class="text-muted mb-0">Department</p>
                </div>
            </div>
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-2x mb-2"></i>
                            <h4 class="fw-bold"><?php echo $notifications['unread_count']; ?></h4>
                            <p class="mb-0">Notifications</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h4 class="fw-bold"><?php echo date('Y'); ?></h4>
                            <p class="mb-0">Academic Year</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Quick Actions -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt text-warning"></i> Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="profile.php" class="btn btn-outline-primary w-100 p-3">
                                        <i class="fas fa-user fa-2x d-block mb-2"></i>
                                        <strong>View Profile</strong><br>
                                        <small>Update your information</small>
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="academic-records.php" class="btn btn-outline-success w-100 p-3">
                                        <i class="fas fa-graduation-cap fa-2x d-block mb-2"></i>
                                        <strong>Academic Records</strong><br>
                                        <small>View grades & courses</small>
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="documents.php" class="btn btn-outline-info w-100 p-3">
                                        <i class="fas fa-file-alt fa-2x d-block mb-2"></i>
                                        <strong>Documents</strong><br>
                                        <small>Manage your files</small>
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="settings.php" class="btn btn-outline-secondary w-100 p-3">
                                        <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                        <strong>Settings</strong><br>
                                        <small>Account preferences</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history text-info"></i> Recent Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($activities)): ?>
                                <div class="timeline">
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($activity['details']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">
                                    <i class="fas fa-info-circle"></i> No recent activity
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Summary & Notifications -->
                <div class="col-md-4">
                    <!-- Profile Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary"></i> Profile Summary
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($student['profile_picture']): ?>
                                <img src="<?php echo BASE_URL . $student['profile_picture']; ?>" 
                                     alt="Profile" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                     style="width: 100px; height: 100px;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h5 class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($student['student_id']); ?></p>
                            
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">Course</small>
                                    <strong><?php echo htmlspecialchars(substr($student['course'] ?: 'Not Set', 0, 10)); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Year</small>
                                    <strong><?php echo $student['year_of_study'] ?: 'N/A'; ?></strong>
                                </div>
                            </div>
                            
                            <?php if ($student['gpa']): ?>
                                <div class="mt-3">
                                    <small class="text-muted d-block">Current GPA</small>
                                    <h4 class="text-primary fw-bold"><?php echo number_format($student['gpa'], 2); ?></h4>
                                </div>
                            <?php endif; ?>
                            
                            <a href="profile.php" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                    
                    <!-- Progress & Goals -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-target text-success"></i> Academic Progress
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Semester Progress</small>
                                    <small>75%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 75%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Course Completion</small>
                                    <small>60%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 60%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Assignment Submissions</small>
                                    <small>90%</small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 90%"></div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">Keep up the great work! 🎯</small>
                            </div>
                        </div>
                    </div>
                </div>
    
    <!-- Quick Actions -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="profile.php" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-user me-2"></i>Update Profile
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="#" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-download me-2"></i>Download Records
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Profile Status</h6>
                    <?php
                    $required_fields = ['first_name', 'last_name', 'phone', 'course'];
                    $completed = 0;
                    foreach ($required_fields as $field) {
                        if (!empty($student[$field])) $completed++;
                    }
                    $percentage = ($completed / count($required_fields)) * 100;
                    ?>
                    <div class="text-center mb-3">
                        <h4 class="text-primary"><?php echo round($percentage); ?>%</h4>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php if ($percentage < 100): ?>
                        <a href="profile.php" class="btn btn-primary btn-sm w-100">Complete Profile</a>
                    <?php else: ?>
                        <div class="text-center text-success">
                            <i class="fas fa-check-circle"></i> Profile Complete
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid var(--primary-color);
    }
</style>

<?php include '../includes/footer.php'; ?>
