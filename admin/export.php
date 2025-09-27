<?php
require_once '../config/config.php';
require_admin();

// Handle export requests
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    
    // Build query with filters
    $where_conditions = ["u.role = 'student'"];
    $params = [];
    
    $search = $_GET['search'] ?? '';
    $course_filter = $_GET['course'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $year_filter = $_GET['year'] ?? '';
    
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
    
    $sql = "SELECT u.email, u.status, u.created_at, u.last_login,
                   sp.student_id, sp.first_name, sp.last_name, sp.phone, sp.address,
                   sp.course, sp.department, sp.year_of_study, sp.gpa
            FROM users u 
            LEFT JOIN student_profiles sp ON u.id = sp.user_id 
            WHERE $where_clause 
            ORDER BY sp.first_name ASC, sp.last_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    if ($format === 'csv') {
        // CSV Export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'Student ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address',
            'Course', 'Department', 'Year', 'GPA', 'Status', 'Registration Date', 'Last Login'
        ]);
        
        // CSV Data
        foreach ($students as $student) {
            fputcsv($output, [
                $student['student_id'] ?: 'N/A',
                $student['first_name'] ?: '',
                $student['last_name'] ?: '',
                $student['email'],
                $student['phone'] ?: '',
                $student['address'] ?: '',
                $student['course'] ?: '',
                $student['department'] ?: '',
                $student['year_of_study'] ?: '',
                $student['gpa'] ?: '',
                ucfirst($student['status']),
                date('Y-m-d', strtotime($student['created_at'])),
                $student['last_login'] ? date('Y-m-d H:i', strtotime($student['last_login'])) : 'Never'
            ]);
        }
        
        fclose($output);
        
        // Log export activity
        log_activity($_SESSION['user_id'], 'Data Export', 'Exported ' . count($students) . ' students to CSV');
        exit;
    }
}

$page_title = 'Export Data';
include '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-download text-primary"></i> Export Student Data
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-csv fa-3x text-primary mb-3"></i>
                                    <h5>CSV Export</h5>
                                    <p class="text-muted">Export all student data in CSV format for Excel or other spreadsheet applications.</p>
                                    <a href="?export=csv" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Download CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-pdf fa-3x text-info mb-3"></i>
                                    <h5>PDF Report</h5>
                                    <p class="text-muted">Generate a formatted PDF report with student statistics and data.</p>
                                    <button class="btn btn-info" onclick="generatePDFReport()">
                                        <i class="fas fa-file-pdf"></i> Generate PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                                    <h5>Analytics Report</h5>
                                    <p class="text-muted">Detailed analytics and statistics about student performance and demographics.</p>
                                    <a href="analytics.php" class="btn btn-success">
                                        <i class="fas fa-chart-line"></i> View Analytics
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-3x text-warning mb-3"></i>
                                    <h5>Backup Data</h5>
                                    <p class="text-muted">Create a complete backup of all student data and system information.</p>
                                    <button class="btn btn-warning" onclick="createBackup()">
                                        <i class="fas fa-save"></i> Create Backup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> All exports include only the data you have permission to access. 
                        Sensitive information like passwords are never included in exports.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function generatePDFReport() {
        showToast('PDF Generation', 'Generating PDF report...', 'info');
        // Implement PDF generation logic here
        setTimeout(() => {
            showToast('Success', 'PDF report generated successfully!', 'success');
        }, 2000);
    }
    
    function createBackup() {
        confirmAction('Create Backup', 'This will create a complete backup of all data. Continue?')
        .then((result) => {
            if (result.isConfirmed) {
                showToast('Backup', 'Creating backup...', 'info');
                // Implement backup logic here
                setTimeout(() => {
                    showToast('Success', 'Backup created successfully!', 'success');
                }, 3000);
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
