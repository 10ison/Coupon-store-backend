<?php
$config = require 'config.php';
header("Content-Type: application/json");

$db = new PDO("sqlite:" . $config->DB_FILE);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? '');

if ($action == "list") {

    $stmt = $db->query("SELECT coupon_type, COUNT(*) AS available FROM coupons WHERE used=0 GROUP BY coupon_type");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action == "create") {

    $body = json_decode(file_get_contents("php://input"), true);

    $type = $body['coupon_type'];
    $qty = intval($body['quantity']);
    $price = $config->DEFAULT_UNIT_PRICE;

    $amount = $qty * $price * 100; // paise

    $payload = [
        "amount" => $amount,
        "currency" => "INR",
        "description" => "$type $qty coupons",
        "callback_url" => $config->HOST_URL . "/paid.php",
        "callback_method" => "get"
    ];

    $ch = curl_init("https://api.razorpay.com/v1/payment_links");
    curl_setopt($ch, CURLOPT_USERPWD, $config->RAZORPAY_KEY . ":" . $config->RAZORPAY_SECRET);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $obj = json_decode($response, true);

    // Save order
    $stmt = $db->prepare("INSERT INTO payments (payment_link_id, coupon_type, quantity, amount, status, created_at)
                          VALUES (?, ?, ?, ?, 'created', datetime('now'))");
    $stmt->execute([$obj['id'], $type, $qty, $amount]);

    echo json_encode([
        "payment_link" => $obj['short_url'],
        "id" => $obj['id']
    ]);
    exit;
}

if ($action == "status") {

    $id = $_GET['id'];

    $stmt = $db->prepare("SELECT status, coupon_codes FROM payments WHERE payment_link_id=?");
    $stmt->execute([$id]);

    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

echo json_encode(["error"=>"invalid action"]);