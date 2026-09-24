<?php
/**
 * Customer Reviews API - MANON
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

$name    = trim($_POST['customer_name'] ?? '');
$city    = trim($_POST['customer_city'] ?? '');
$rating  = isset($_POST['rating']) ? max(1, min(5, (int)$_POST['rating'])) : 5;
$comment = trim($_POST['comment'] ?? '');

if (empty($name) || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'يرجى كتابة الاسم والتعليق']);
    exit;
}

try {
    $reviews = readJsonFile('reviews.json');
    $newId   = generateNumericId($reviews);

    $reviews[] = [
        'id'            => $newId,
        'customer_name' => $name,
        'customer_city' => $city,
        'rating'        => $rating,
        'review_text'   => $comment,
        'status'        => 'pending',
        'created_at'    => date('Y-m-d H:i:s'),
    ];

    writeJsonFile('reviews.json', $reviews);

    echo json_encode([
        'success' => true,
        'message' => 'شكراً لمشاركتكِ رأيكِ 🤍 سيظهر تقييمكِ بعد مراجعته واعتماده من الإدارة.',
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ التقييم']);
}
