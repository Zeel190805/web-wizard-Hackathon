<?php
require_once '../config/config.php';
require_login();

header('Content-Type: application/json');

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$upload_dir = '../assets/uploads/';

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Validate file type
$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

// Generate unique filename
$filename = 'upload_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Log the upload
    log_activity($_SESSION['user_id'], 'File Upload', 'Uploaded file: ' . $filename);
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'filename' => $filename,
        'path' => 'assets/uploads/' . $filename,
        'size' => $file['size']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
?>
