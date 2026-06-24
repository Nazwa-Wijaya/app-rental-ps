<?php
/**
 * AJAX Endpoint - Load games based on console_id
 */
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$console_id = isset($_GET['console_id']) ? intval($_GET['console_id']) : 0;

if ($console_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, name FROM games WHERE console_id = :console_id ORDER BY name ASC");
    $stmt->execute([':console_id' => $console_id]);
    $games = $stmt->fetchAll();
    
    echo json_encode($games);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
}
?>
