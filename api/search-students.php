<?php
require_once '../config/config.php';
require_admin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode(['html' => '']);
    exit;
}

$search_param = "%$query%";
$stmt = $pdo->prepare("SELECT u.id, u.email, u.status, sp.first_name, sp.last_name, sp.student_id, sp.course 
                      FROM users u 
                      LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                      WHERE u.role = 'student' 
                      AND (sp.first_name LIKE ? OR sp.last_name LIKE ? OR u.email LIKE ? OR sp.student_id LIKE ?)
                      ORDER BY sp.first_name ASC 
                      LIMIT 10");
$stmt->execute([$search_param, $search_param, $search_param, $search_param]);
$students = $stmt->fetchAll();

$html = '';
foreach ($students as $student) {
    $name = trim($student['first_name'] . ' ' . $student['last_name']) ?: 'Unnamed';
    $status_badge = $student['status'] === 'active' ? 'bg-success' : 'bg-warning';
    
    $html .= "
    <div class='d-flex align-items-center p-2 border-bottom'>
        <div class='bg-primary rounded-circle d-flex align-items-center justify-content-center me-2' style='width: 30px; height: 30px;'>
            <span class='text-white fw-bold'>" . strtoupper(substr($name, 0, 1)) . "</span>
        </div>
        <div class='flex-grow-1'>
            <div class='fw-bold'>" . htmlspecialchars($name) . "</div>
            <small class='text-muted'>" . htmlspecialchars($student['email']) . "</small>
        </div>
        <div class='text-end'>
            <span class='badge $status_badge'>" . ucfirst($student['status']) . "</span>
        </div>
    </div>";
}

echo json_encode(['html' => $html]);
?>
