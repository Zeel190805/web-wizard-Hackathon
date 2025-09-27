<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Students';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'delete' && isset($_POST['student_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$_POST['student_id']]);
            
            log_activity($_SESSION['user_id'], 'Student Deletion', 'Deleted student ID: ' . $_POST['student_id']);
            
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'bulk_delete' && isset($_POST['student_ids'])) {
        try {
            $ids = json_decode($_POST['student_ids'], true);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders) AND role = 'student'");
            $stmt->execute($ids);
            
            log_activity($_SESSION['user_id'], 'Bulk Student Deletion', 'Deleted ' . count($ids) . ' students');
            
            echo json_encode(['success' => true, 'message' => count($ids) . ' students deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete students']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'toggle_status' && isset($_POST['student_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_POST['student_id']]);
            $current_status = $stmt->fetch()['status'];
            
            $new_status = $current_status === 'active' ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $_POST['student_id']]);
            
            log_activity($_SESSION['user_id'], 'Status Change', "Changed student status to $new_status");
            
            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }
}

// Pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';
$year_filter = $_GET['year'] ?? '';

// Build WHERE clause
$where_conditions = ["u.role = 'student'"];
$params = [];

if ($search) {
    $where_conditions[] = "(sp.first_name LIKE ? OR sp.last_name LIKE ? OR u.email LIKE ? OR sp.student_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($course_filter) {
    $where_conditions[] = "sp.course = ?";
    $params[] = $course_filter;
}

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if ($year_filter) {
    $where_conditions[] = "sp.year_of_study = ?";
    $params[] = $year_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users u 
              LEFT JOIN student_profiles sp ON u.id = sp.user_id 
              WHERE $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_students = $stmt->fetch()['total'];
$total_pages = ceil($total_students / $per_page);

// Get students with pagination
$sql = "SELECT u.*, sp.first_name, sp.last_name, sp.student_id, sp.course, sp.year_of_study, sp.gpa, sp.phone 
        FROM users u 
        LEFT JOIN student_profiles sp ON u.id = sp.user_id 
        WHERE $where_clause 
        ORDER BY sp.first_name ASC, sp.last_name ASC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get filter options
$stmt = $pdo->prepare("SELECT DISTINCT course FROM student_profiles WHERE course IS NOT NULL AND course != '' ORDER BY course");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="container-fluid">
    <?php if (isset($_GET['success'])): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-users text-primary"></i> Manage Students
                            </h4>
                            <p class="text-muted mb-0">Total: <?php echo number_format($total_students); ?> students</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger" id="bulk-delete-btn" style="display: none;">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                            <button class="btn btn-outline-info" onclick="exportStudents()">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <a href="add-student.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add Student
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, email, student ID...">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Course</label>
                            <select name="course" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>" 
                                            <?php echo $course_filter === $course ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-control">
                                <option value="">All Years</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $year_filter == $i ? 'selected' : ''; ?>>
                                        Year <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="students.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all" class="form-check-input">
                                        </th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>GPA</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input student-checkbox" 
                                                       value="<?php echo $student['id']; ?>">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 35px; height: 35px;">
                                                        <span class="text-white fw-bold">
                                                            <?php echo strtoupper(substr($student['first_name'] ?: 'U', 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars(($student['first_name'] ?: '') . ' ' . ($student['last_name'] ?: '')); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($student['phone'] ?: 'No phone'); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($student['student_id'] ?: 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['course'] ?: 'Not set'); ?></td>
                                            <td>
                                                <?php if ($student['year_of_study']): ?>
                                                    <span class="badge bg-info">Year <?php echo $student['year_of_study']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['gpa']): ?>
                                                    <span class="fw-bold text-<?php echo $student['gpa'] >= 3.5 ? 'success' : ($student['gpa'] >= 3.0 ? 'warning' : 'danger'); ?>">
                                                        <?php echo number_format($student['gpa'], 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-<?php echo $student['status'] === 'active' ? 'success' : 'warning'; ?>" 
                                                        onclick="toggleStatus(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-<?php echo $student['status'] === 'active' ? 'check' : 'pause'; ?>"></i>
                                                    <?php echo ucfirst($student['status']); ?>
                                                </button>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="edit-student.php?id=<?php echo $student['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="viewStudent(<?php echo $student['id']; ?>)" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteStudent(<?php echo $student['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Students pagination">
                                <ul class="pagination justify-content-center mt-4">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No students found</h5>
                            <p class="text-muted">Try adjusting your search criteria or add a new student.</p>
                            <a href="add-student.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add First Student
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Select all functionality
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleBulkActions();
    });
    
    // Individual checkbox handling
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.addEventListener('change', toggleBulkActions);
    });
    
    function toggleBulkActions() {
        const checked = document.querySelectorAll('.student-checkbox:checked');
        const bulkBtn = document.getElementById('bulk-delete-btn');
        
        if (checked.length > 0) {
            bulkBtn.style.display = 'inline-block';
            bulkBtn.textContent = `Delete Selected (${checked.length})`;
        } else {
            bulkBtn.style.display = 'none';
        }
    }
    
    // Bulk delete
    document.getElementById('bulk-delete-btn').addEventListener('click', function() {
        const checked = document.querySelectorAll('.student-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        
        confirmAction(
            'Delete Students',
            `Are you sure you want to delete ${ids.length} student(s)? This action cannot be undone.`,
            'Delete'
        ).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                
                fetch('students.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=bulk_delete&student_ids=${JSON.stringify(ids)}`
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showToast('Success', data.message, 'success');
                        location.reload();
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                });
            }
        });
    });
    
    // Delete single student
    function deleteStudent(id) {
        confirmAction(
            'Delete Student',
            'Are you sure you want to delete this student? This action cannot be undone.',
            'Delete'
        ).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                
                fetch('students.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&student_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showToast('Success', data.message, 'success');
                        location.reload();
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                });
            }
        });
    }
    
    // Toggle student status
    function toggleStatus(id) {
        fetch('students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_status&student_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'Status updated successfully', 'success');
                location.reload();
            } else {
                showToast('Error', data.message, 'error');
            }
        });
    }
    
    // View student details
    function viewStudent(id) {
        window.open(`view-student.php?id=${id}`, '_blank');
    }
    
    // Export students
    function exportStudents() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = 'export.php?' + params.toString();
    }
    
    // Real-time search
    setupRealTimeSearch('search', 'search-results', '<?php echo BASE_URL; ?>api/search-students.php');
</script>

<?php include '../includes/footer.php'; ?>
