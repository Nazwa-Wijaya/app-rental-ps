<?php
/**
 * AJAX Endpoint - Save Wizard parameters to PHP session
 */
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

// Ensure session booking data structure exists
if (!isset($_SESSION['booking_data'])) {
    $_SESSION['booking_data'] = [];
}
if (!isset($_SESSION['booking_data']['foods'])) {
    $_SESSION['booking_data']['foods'] = [];
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($action === 'update_food') {
    // Handle food quantity updating
    $food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
    
    if ($food_id > 0) {
        if ($qty > 0) {
            $_SESSION['booking_data']['foods'][$food_id] = $qty;
        } else {
            unset($_SESSION['booking_data']['foods'][$food_id]);
        }
        echo json_encode(['status' => 'success', 'msg' => 'Food updated']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid food ID']);
    }
    exit;
}

// Default action: save standard form data
$params = ['console_id', 'room_id', 'game_id', 'people_count', 'booking_date', 'start_time', 'duration'];

foreach ($params as $param) {
    if (isset($_POST[$param])) {
        // Cast numerical parameters where appropriate
        if (in_array($param, ['console_id', 'room_id', 'game_id', 'people_count', 'duration'])) {
            $_SESSION['booking_data'][$param] = intval($_POST[$param]);
        } else {
            $_SESSION['booking_data'][$param] = sanitize($_POST[$param]);
        }
    }
}

echo json_encode(['status' => 'success', 'booking_data' => $_SESSION['booking_data']]);
?>
