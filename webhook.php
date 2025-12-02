<?php
$config = require 'config.php';

$payload = file_get_contents("php://input");
$sig = $_SERVER["HTTP_X_RAZORPAY_SIGNATURE"];

$expected = hash_hmac("sha256", $payload, $config->WEBHOOK_SECRET);

if (!hash_equals($expected, $sig)) {
    http_response_code(400);
    exit("Invalid signature");
}

$data = json_decode($payload, true);

if ($data['event'] == "payment_link.paid") {

    $link = $data['payload']['payment_link']['entity'];
    $link_id = $link['id'];
    $payment_id = $link['payments'][0]['id'];

    $db = new PDO("sqlite:" . $config->DB_FILE);

    $stmt = $db->prepare("SELECT coupon_type, quantity FROM payments WHERE payment_link_id=?");
    $stmt->execute([$link_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    $type = $p['coupon_type'];
    $qty = intval($p['quantity']);

    // get coupons
    $stmt = $db->prepare("SELECT code FROM coupons WHERE coupon_type=? AND used=0 LIMIT $qty");
    $stmt->execute([$type]);
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // mark used
    foreach ($codes as $c) {
        $db->prepare("UPDATE coupons SET used=1 WHERE code=?")->execute([$c]);
    }

    // update payment
    $db->prepare("UPDATE payments SET status='paid', razorpay_payment_id=?, coupon_codes=? WHERE payment_link_id=?")
       ->execute([$payment_id, json_encode($codes), $link_id]);
}

echo "OK";