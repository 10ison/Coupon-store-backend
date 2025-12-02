<?php
$config = require 'config.php';

$db = new PDO("sqlite:" . $config->DB_FILE);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// coupon table
$db->exec("
CREATE TABLE IF NOT EXISTS coupons(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coupon_type TEXT,
    code TEXT,
    used INTEGER DEFAULT 0
);
");

// payment table
$db->exec("
CREATE TABLE IF NOT EXISTS payments(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_link_id TEXT,
    coupon_type TEXT,
    quantity INTEGER,
    amount INTEGER,
    status TEXT,
    created_at TEXT,
    razorpay_payment_id TEXT,
    coupon_codes TEXT
);
");

// seed coupons
$db->exec("
INSERT INTO coupons (coupon_type, code) VALUES
('Flipkart','FLIP-1111'),
('Flipkart','FLIP-2222'),
('Amazon','AMZ-3333'),
('Amazon','AMZ-4444'),
('Zomato','ZOM-5555'),
('Swiggy','SWG-6666');
");

echo "DB READY";