<?php
/**
 * MANON Setup Helper
 * Run once to verify the installation is correct.
 * Delete this file after setup is complete.
 */

$rootPath = __DIR__ . '/';
$dataPath = $rootPath . 'data/';

$checks = [];

// Check PHP version
$checks[] = [
    'label'  => 'PHP Version',
    'ok'     => version_compare(PHP_VERSION, '7.4.0', '>='),
    'detail' => 'PHP ' . PHP_VERSION . (version_compare(PHP_VERSION, '7.4.0', '>=') ? ' ✓' : ' ✗ يجب PHP 7.4+'),
];

// Check data directory exists and is writable
$checks[] = [
    'label'  => 'مجلد /data/ موجود وقابل للكتابة',
    'ok'     => is_dir($dataPath) && is_writable($dataPath),
    'detail' => is_dir($dataPath) ? (is_writable($dataPath) ? 'قابل للكتابة ✓' : 'غير قابل للكتابة ✗') : 'المجلد غير موجود ✗',
];

// Check JSON files exist
$requiredFiles = ['products.json', 'categories.json', 'colors.json', 'orders.json', 'reviews.json', 'special_orders.json', 'discount_codes.json', 'settings.json', 'admin.json'];
foreach ($requiredFiles as $f) {
    $exists = file_exists($dataPath . $f);
    $checks[] = ['label' => "data/{$f}", 'ok' => $exists, 'detail' => $exists ? 'موجود ✓' : 'مفقود ✗'];
}

// Check uploads directory writable
foreach (['products', 'categories', 'special-orders'] as $dir) {
    $path = $rootPath . 'uploads/' . $dir . '/';
    $ok   = is_dir($path) && is_writable($path);
    $checks[] = ['label' => "uploads/{$dir}/", 'ok' => $ok, 'detail' => $ok ? 'موجود وقابل للكتابة ✓' : 'يجب إنشاؤه وجعله قابلاً للكتابة ✗'];
}

// Check finfo extension
$checks[] = [
    'label'  => 'PHP finfo extension',
    'ok'     => extension_loaded('fileinfo'),
    'detail' => extension_loaded('fileinfo') ? 'محملة ✓' : 'مطلوبة لرفع الصور ✗',
];

$allOk = !in_array(false, array_column($checks, 'ok'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MANON Setup Check</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
<style>body{font-family:'Cairo',sans-serif;background:#FAF7F2;}</style>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="py-5">
<div class="container" style="max-width: 640px;">
    <div class="text-center mb-4">
        <img src="assets/images/logo.jpg" alt="MANON" style="max-height:80px;" class="rounded-3">
        <h2 class="fw-bold mt-3">MANON Setup Check</h2>
        <p class="text-muted">فحص التثبيت والإعداد</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <?php foreach ($checks as $check): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold small"><?= htmlspecialchars($check['label']) ?></span>
                    <span class="badge <?= $check['ok'] ? 'bg-success' : 'bg-danger' ?> px-3 py-2 rounded-pill small">
                        <?= htmlspecialchars($check['detail']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($allOk): ?>
        <div class="alert alert-success rounded-4 text-center fw-bold">
            🎉 كل شيء على ما يرام! المتجر جاهز للاستخدام.
        </div>
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-dark btn-lg rounded-3">فتح المتجر</a>
            <a href="admin/login.php" class="btn btn-outline-dark rounded-3">لوحة الإدارة</a>
        </div>
        <div class="alert alert-warning mt-3 small rounded-4">
            <strong>⚠️ أمان:</strong> احذف هذا الملف (setup.php) بعد التحقق من عمل الموقع!
        </div>
    <?php else: ?>
        <div class="alert alert-danger rounded-4">
            <strong>توجد مشاكل يجب حلها:</strong><br>
            تأكد من أن المجلدات المطلوبة موجودة وأن PHP لديها صلاحية الكتابة عليها.
        </div>
        <div class="card border-0 bg-dark text-light p-3 rounded-4 small">
            <strong>لإصلاح صلاحيات المجلدات على Linux/Apache:</strong><br>
            <code>chmod 755 data/ uploads/products/ uploads/categories/ uploads/special-orders/</code>
        </div>
    <?php endif; ?>

    <hr class="my-4">
    <div class="text-center small text-muted">
        <strong>بيانات تسجيل الدخول الافتراضية للمشرف:</strong><br>
        البريد الإلكتروني: <code>admin@manon.com</code> | كلمة المرور: <code>admin123456</code><br>
        <span class="text-danger">يرجى تغيير كلمة المرور بعد أول تسجيل دخول من إعدادات الموقع</span>
    </div>
</div>
</body>
</html>
