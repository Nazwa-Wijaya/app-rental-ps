<?php
/**
 * AJAX Endpoint - Check room and console slot availability
 */
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$time = isset($_GET['time']) ? sanitize($_GET['time']) : '';
$duration = isset($_GET['duration']) ? intval($_GET['duration']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$console_id = isset($_GET['console_id']) ? intval($_GET['console_id']) : 0;

if (empty($date) || empty($time) || $duration <= 0 || $room_id <= 0 || $console_id <= 0) {
    echo json_encode(['available' => false, 'error' => 'Missing parameter values']);
    exit;
}

try {
    // 1. Calculate end time
    $start_timestamp = strtotime($time);
    $end_timestamp = $start_timestamp + ($duration * 3600);
    $start_time_str = date('H:i:s', $start_timestamp);
    $end_time_str = date('H:i:s', $end_timestamp);

    // 2. Query conflict bookings
    // Overlapping condition: start_time < :end_time AND end_time > :start_time
    // We block if the same room OR the same console is occupied.
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE booking_date = :booking_date 
          AND booking_status != 'cancelled'
          AND (room_id = :room_id OR console_id = :console_id)
          AND (start_time < :end_time AND end_time > :start_time)
    ");
    
    $stmt->execute([
        ':booking_date' => $date,
        ':room_id'      => $room_id,
        ':console_id'   => $console_id,
        ':end_time'     => $end_time_str,
        ':start_time'   => $start_time_str
    ]);
    
    $result = $stmt->fetch();
    $is_available = (intval($result['count']) === 0);

    echo json_encode(['available' => $is_available]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['available' => false, 'error' => 'Database query failure']);
}
?>
