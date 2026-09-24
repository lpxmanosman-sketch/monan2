<?php
/**
 * Core Helper Functions for MANON
 * No database required — uses JSON flat files.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json-storage.php';

// =========================================================
// OUTPUT ESCAPING
// =========================================================

function e($string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

// =========================================================
// SETTINGS (from settings.json)
// =========================================================

function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = readJsonFile('settings.json');
    }
    return (string)($settingsCache[$key] ?? $default);
}

function formatPrice($amount): string {
    $currency = getSetting('currency', DEFAULT_CURRENCY);
    return number_format((float)$amount, 0) . ' ' . $currency;
}

// =========================================================
// CSRF PROTECTION
// =========================================================

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// =========================================================
// FLASH MESSAGES
// =========================================================

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// =========================================================
// AUTHENTICATION
// =========================================================

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['email']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

// =========================================================
// CART (Session-based)
// =========================================================

function &getCart(): array {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function getCartCount(): int {
    $cart  = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

function getCartSubtotal(): float {
    $cart     = getCart();
    $subtotal = 0.0;
    foreach ($cart as $item) {
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }
    return $subtotal;
}

/**
 * Add a product to cart — reads from products.json (no DB)
 */
function addToCart($productId, int $quantity = 1, string $size = '54', string $color = 'أسود'): array {
    $product = getProductById($productId);

    if (!$product || empty($product['is_active'])) {
        return ['success' => false, 'message' => 'المنتج غير متوفر حالياً'];
    }

    $color    = !empty($color) ? trim($color) : 'أسود';
    $size     = !empty($size)  ? trim($size)  : '54';
    $price    = ($product['sale_price'] !== null && $product['sale_price'] > 0)
                ? (float)$product['sale_price']
                : (float)$product['price'];
    $cartKey  = $product['id'] . '_' . $size . '_' . $color;
    $cart     = &getCart();
    $stock    = (int)($product['stock_quantity'] ?? 99);

    if (isset($cart[$cartKey])) {
        $newQty = $cart[$cartKey]['quantity'] + $quantity;
        if ($newQty > $stock) {
            return ['success' => false, 'message' => 'الكمية المطلوبة تتجاوز المخزون المتاح'];
        }
        $cart[$cartKey]['quantity'] = $newQty;
    } else {
        if ($quantity > $stock) {
            return ['success' => false, 'message' => 'الكمية المطلوبة تتجاوز المخزون المتاح'];
        }
        $cart[$cartKey] = [
            'product_id' => $product['id'],
            'name'       => $product['name'],
            'price'      => $price,
            'image'      => $product['main_image'],
            'size'       => $size,
            'color'      => $color,
            'quantity'   => (int)$quantity,
        ];
    }

    return [
        'success'   => true,
        'message'   => 'تمت إضافة العباية إلى سلتك بنجاح',
        'cartCount' => getCartCount(),
        'subtotal'  => formatPrice(getCartSubtotal()),
    ];
}

function updateCartItem(string $cartKey, int $quantity): bool {
    $cart = &getCart();
    if (!isset($cart[$cartKey])) return false;
    if ($quantity <= 0) {
        unset($cart[$cartKey]);
    } else {
        $cart[$cartKey]['quantity'] = $quantity;
    }
    return true;
}

function removeCartItem(string $cartKey): bool {
    $cart = &getCart();
    if (!isset($cart[$cartKey])) return false;
    unset($cart[$cartKey]);
    return true;
}

function clearCart(): void {
    $_SESSION['cart'] = [];
}

// =========================================================
// COLORS (from colors.json)
// =========================================================

function getActiveColors(): array {
    $colors = readJsonFile('colors.json');
    $active = array_filter($colors, fn($c) => !empty($c['is_active']));
    usort($active, fn($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));
    // Normalize field names to match legacy code: hex_code
    return array_map(function($c) {
        $c['hex_code'] = $c['hex'] ?? '#000000';
        return $c;
    }, array_values($active));
}

// =========================================================
// DISCOUNT CODE VALIDATION (from discount_codes.json)
// =========================================================

function validateDiscountCode(string $code, float $subtotal): array {
    $code = trim($code);
    if (empty($code)) {
        return ['valid' => false, 'message' => 'يرجى إدخال كود الخصم'];
    }

    $codes = readJsonFile('discount_codes.json');
    $disc  = null;
    foreach ($codes as $c) {
        if (strtoupper($c['code'] ?? '') === strtoupper($code)) {
            $disc = $c;
            break;
        }
    }

    if (!$disc) {
        return ['valid' => false, 'message' => 'كود الخصم غير صحيح'];
    }
    if (!empty($disc['is_used'])) {
        return ['valid' => false, 'message' => 'تم استخدام كود الخصم هذا من قبل'];
    }
    if (!empty($disc['expires_at']) && strtotime($disc['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'انتهت صلاحية كود الخصم'];
    }

    $percent        = (int)($disc['discount_percent'] ?? 0);
    $discountAmount = round(($subtotal * $percent) / 100, 2);

    return [
        'valid'           => true,
        'code'            => $disc['code'],
        'percent'         => $percent,
        'discount_amount' => $discountAmount,
        'message'         => "تم تطبيق خصم {$percent}% بنجاح!",
    ];
}

// =========================================================
// WHATSAPP URL BUILDER
// =========================================================

function buildWhatsAppUrl(array $orderData, array $items, float $total, float $discountAmount = 0, string $paymentMethod = 'cod'): string {
    $orderNum          = $orderData['order_number'] ?? ('#MANON-' . rand(1000, 9999));
    $paymentMethodText = ($paymentMethod === 'electronic') ? 'دفع إلكتروني' : 'الدفع عند الاستلام';

    $msg  = "MANON ORDER\n\n";
    $msg .= "رقم الطلب: {$orderNum}\n";
    $msg .= "اسم العميلة: " . ($orderData['customer_name'] ?? '') . "\n";
    $msg .= "رقم الهاتف: "  . ($orderData['phone'] ?? '') . "\n";
    $msg .= "المحافظة: "     . ($orderData['city'] ?? '') . "\n";
    $msg .= "العنوان: "      . ($orderData['address'] ?? '') . "\n\n";
    $msg .= "المنتجات:\n\n";

    foreach ($items as $item) {
        $color     = !empty($item['selected_color']) ? $item['selected_color'] : ($item['color'] ?? 'أسود');
        $size      = !empty($item['selected_size'])  ? $item['selected_size']  : ($item['size'] ?? '54');
        $itemTotal = (float)$item['price'] * (int)$item['quantity'];
        $msg .= "- " . ($item['product_name'] ?? $item['name'] ?? '') . " (مقاس {$size})\n";
        $msg .= "اللون: {$color}\n";
        $msg .= "الكمية: " . $item['quantity'] . "\n";
        $msg .= "السعر: " . number_format($itemTotal, 0) . " " . DEFAULT_CURRENCY . "\n\n";
    }

    if ($discountAmount > 0) {
        $msg .= "الخصم: " . number_format($discountAmount, 0) . " " . DEFAULT_CURRENCY . "\n";
    }
    $msg .= "الإجمالي: " . number_format($total, 0) . " " . DEFAULT_CURRENCY . "\n\n";
    $msg .= "طريقة الدفع: {$paymentMethodText}\n";

    if (!empty($orderData['notes'])) {
        $msg .= "\nملاحظات: " . $orderData['notes'] . "\n";
    }

    return "https://wa.me/" . WHATSAPP_PHONE . "?text=" . rawurlencode($msg);
}

function buildSpecialOrderWhatsAppUrl(array $data): string {
    $msg  = "طلب خاص - MANON\n\n";
    $msg .= "الاسم: "            . ($data['name']   ?? '') . "\n";
    $msg .= "رقم الهاتف: "       . ($data['phone']  ?? '') . "\n";
    $msg .= "التصميم المطلوب: "  . ($data['design'] ?? '') . "\n";
    $msg .= "اللون: "             . ($data['color']  ?? '') . "\n";
    $msg .= "المقاس: "            . ($data['size']   ?? '') . "\n";
    $msg .= "الملاحظات: "         . ($data['notes']  ?? '') . "\n";
    return "https://wa.me/" . WHATSAPP_PHONE . "?text=" . rawurlencode($msg);
}

// =========================================================
// FILE UPLOAD
// =========================================================

function handleImageUpload(array $file, string $subFolder = 'products'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'معاملات الملف غير صالحة'];
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:    break;
        case UPLOAD_ERR_NO_FILE: return ['success' => false, 'error' => 'لم يتم تحديد أي ملف'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return ['success' => false, 'error' => 'حجم الملف يتجاوز الحد'];
        default: return ['success' => false, 'error' => 'خطأ غير متوقع أثناء الرفع'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت'];
    }
    $finfo        = new finfo(FILEINFO_MIME_TYPE);
    $mime         = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];
    $ext = array_search($mime, $allowedMimes, true);
    if ($ext === false) {
        return ['success' => false, 'error' => 'نوع الصورة غير مدعوم. يرجى رفع JPG أو PNG أو WebP'];
    }
    $uploadDir = ROOT_PATH . 'uploads/' . $subFolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath  = $uploadDir . $newFileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'تعذر حفظ الملف'];
    }
    return [
        'success' => true,
        'path'    => 'uploads/' . $subFolder . '/' . $newFileName,
    ];
}

// =========================================================
// ORDER STATUS BADGE
// =========================================================

function getStatusBadge(string $status): string {
    $badges = [
        'new'       => '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill">جديد</span>',
        'confirmed' => '<span class="badge bg-info text-dark px-3 py-2 rounded-pill">مؤكد</span>',
        'preparing' => '<span class="badge bg-primary px-3 py-2 rounded-pill">قيد التجهيز</span>',
        'delivered' => '<span class="badge bg-success px-3 py-2 rounded-pill">تم التوصيل</span>',
        'cancelled' => '<span class="badge bg-danger px-3 py-2 rounded-pill">ملغي</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary px-3 py-2 rounded-pill">' . e($status) . '</span>';
}

// =========================================================
// SLUG GENERATOR
// =========================================================

function generateSlug(string $text): string {
    // Simple transliteration for Arabic-safe slugs
    $text = trim($text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
    $text = strtolower($text);
    return $text ?: 'item-' . time();
}

// =========================================================
// REDIRECT HELPER
// =========================================================

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

