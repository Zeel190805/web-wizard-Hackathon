<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Dashboard';

// Handle theme toggle
if ($_POST['action'] ?? '' === 'toggle_theme') {
    $new_theme = $_SESSION['theme'] === 'dark' ? 'light' : 'dark';
    $_SESSION['theme'] = $new_theme;
    
    // Update in database
    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$new_theme, $_SESSION['user_id']]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $new_theme]);
    exit;
}

// Get system statistics
$stats = [];

try {
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch()['total'] ?? 0;

    // Active students
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM users WHERE role = 'student' AND status = 'active'");
    $stmt->execute();
    $stats['active_students'] = $stmt->fetch()['active'] ?? 0;

    // New registrations this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as new_this_month FROM users WHERE role = 'student' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");
    $stmt->execute();
    $stats['new_this_month'] = $stmt->fetch()['new_this_month'] ?? 0;

    // Average GPA
    $stmt = $pdo->prepare("SELECT AVG(gpa) as avg_gpa FROM student_profiles WHERE gpa IS NOT NULL AND gpa > 0");
    $stmt->execute();
    $stats['avg_gpa'] = $stmt->fetch()['avg_gpa'] ?? 0;

    // Recent activities (limited)
    $stmt = $pdo->prepare("SELECT al.action, al.created_at, u.email, sp.first_name, sp.last_name 
                          FROM activity_logs al 
                          JOIN users u ON al.user_id = u.id 
                          LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                          ORDER BY al.created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();

    // Course distribution (fixed)
    $stmt = $pdo->prepare("SELECT 
                            CASE 
                                WHEN course IS NULL OR course = '' THEN 'Not Specified'
                                ELSE course 
                            END as course_name,
                            COUNT(*) as student_count 
                          FROM student_profiles 
                          GROUP BY course_name 
                          HAVING student_count > 0
                          ORDER BY student_count DESC 
                          LIMIT 5");
    $stmt->execute();
    $course_distribution = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $stats = ['total_students' => 0, 'active_students' => 0, 'new_this_month' => 0, 'avg_gpa' => 0];
    $recent_activities = [];
    $course_distribution = [];
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark"><?php echo number_format($stats['total_students']); ?></h3>
                    <p class="text-muted mb-0">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark"><?php echo number_format($stats['active_students']); ?></h3>
                    <p class="text-muted mb-0">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark"><?php echo number_format($stats['new_this_month']); ?></h3>
                    <p class="text-muted mb-0">This Month</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark"><?php echo number_format($stats['avg_gpa'], 1); ?></h3>
                    <p class="text-muted mb-0">Avg GPA</p>
                </div>
            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Quick Actions & Recent Activity -->
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="students.php" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-users me-2"></i>Manage Students
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="export.php" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-download me-2"></i>Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">Recent Activity</h6>
                        <small class="text-muted">Last 5 actions</small>
                    </div>
                    <?php if (!empty($recent_activities)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="fw-medium"><?php echo htmlspecialchars(trim($activity['first_name'] . ' ' . $activity['last_name']) ?: 'User'); ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($activity['action']); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Course Distribution -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">Course Distribution</h6>
                    <?php if (!empty($course_distribution)): ?>
                        <div class="mb-3">
                            <canvas id="courseChart" height="200"></canvas>
                        </div>
                        <div class="course-list">
                            <?php 
                            $colors = ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'];
                            foreach ($course_distribution as $index => $course): 
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2" style="width: 12px; height: 12px; background-color: <?php echo $colors[$index % 5]; ?>; border-radius: 50%;"></div>
                                        <span class="small"><?php echo htmlspecialchars(substr($course['course_name'], 0, 20)); ?></span>
                                    </div>
                                    <span class="badge bg-light text-dark"><?php echo $course['student_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-pie fa-2x mb-2"></i>
                            <p>No course data</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">System Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Database</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Active Courses</span>
                        <span class="badge bg-info"><?php echo count($course_distribution); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Last Updated</span>
                        <span class="badge bg-secondary"><?php echo date('M j'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Course Distribution Chart
    const courseChartElement = document.getElementById('courseChart');
    if (courseChartElement) {
        const ctx = courseChartElement.getContext('2d');
        const courseData = <?php echo json_encode($course_distribution); ?>;
        
        if (courseData && courseData.length > 0) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: courseData.map(item => item.course_name || 'Unknown'),
                    datasets: [{
                        data: courseData.map(item => item.student_count || 0),
                        backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
