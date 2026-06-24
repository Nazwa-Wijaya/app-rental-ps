<?php
/**
 * AJAX Endpoint - Calculate live total price & update PHP session booking details
 */
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

$duration = isset($_GET['duration']) ? intval($_GET['duration']) : 1;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$promo_code = isset($_GET['promo_code']) ? strtoupper(trim(sanitize($_GET['promo_code']))) : '';

// Ensure session booking data structure exists
if (!isset($_SESSION['booking_data'])) {
    $_SESSION['booking_data'] = [];
}
if (!isset($_SESSION['booking_data']['foods'])) {
    $_SESSION['booking_data']['foods'] = [];
}

$room_price = 0;
$room_total = 0;
$food_total = 0;
$discount = 0;
$service_fee = 0.00;
$foods_selected_details = [];

// 1. Calculate Room Cost
if ($room_id > 0) {
    try {
        $stmt = $db->prepare("SELECT price_per_hour FROM rooms WHERE id = :id");
        $stmt->execute([':id' => $room_id]);
        $room = $stmt->fetch();
        if ($room) {
            $room_price = floatval($room['price_per_hour']);
            $room_total = $room_price * $duration;
        }
    } catch (PDOException $e) {
        // Log or handle error silently for API stability
    }
}

// 2. Calculate Foods Cost
if (!empty($_SESSION['booking_data']['foods'])) {
    $food_ids = array_keys($_SESSION['booking_data']['foods']);
    if (count($food_ids) > 0) {
        try {
            // Prepared statement with multiple IDs
            $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
            $stmt = $db->prepare("SELECT id, name, price FROM foods WHERE id IN ($placeholders)");
            $stmt->execute(array_values($food_ids));
            $db_foods = $stmt->fetchAll();
            
            foreach ($db_foods as $df) {
                $qty = intval($_SESSION['booking_data']['foods'][$df['id']]);
                if ($qty > 0) {
                    $subtotal = floatval($df['price']) * $qty;
                    $food_total += $subtotal;
                    
                    $foods_selected_details[] = [
                        'id' => $df['id'],
                        'name' => $df['name'],
                        'qty' => $qty,
                        'price' => floatval($df['price']),
                        'subtotal' => $subtotal
                    ];
                }
            }
        } catch (PDOException $e) {
            // Handle error
        }
    }
}

// 3. Subtotal & Discount Calculation
$subtotal = $room_total + $food_total;

if ($promo_code === 'OPENINGYUK') {
    $discount = 0.10 * $subtotal; // 10% discount off subtotal
}

$grand_total = $subtotal - $discount + $service_fee;

// Save calculations back to PHP Session
$_SESSION['booking_data']['duration'] = $duration;
$_SESSION['booking_data']['room_id'] = $room_id;
$_SESSION['booking_data']['promo_code'] = $promo_code;
$_SESSION['booking_data']['room_total'] = $room_total;
$_SESSION['booking_data']['food_total'] = $food_total;
$_SESSION['booking_data']['discount'] = $discount;
$_SESSION['booking_data']['service_fee'] = $service_fee;
$_SESSION['booking_data']['grand_total'] = $grand_total;

// Return JSON Output
echo json_encode([
    'room_total'     => $room_total,
    'food_total'     => $food_total,
    'discount'       => $discount,
    'service_fee'    => $service_fee,
    'grand_total'    => $grand_total,
    'foods_selected' => $foods_selected_details
]);
?>
