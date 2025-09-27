<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $theme = $input['theme'] ?? 'light';
    
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['theme'] = $theme;
        
        // Update database if user preferences table exists
        try {
            $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
            $stmt->execute([$theme, $_SESSION['user_id']]);
        } catch (Exception $e) {
            // Theme preference column might not exist, ignore error
        }
        
        echo json_encode(['success' => true, 'theme' => $theme]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid theme']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>