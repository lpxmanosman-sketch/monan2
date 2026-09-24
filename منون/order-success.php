<?php
/**
 * Order Success & WhatsApp Redirection - MANON
 * Reads order from session (set during checkout) — no database needed.
 */
$pageTitle = 'تم استلام طلبك بنجاح | منون';
require_once __DIR__ . '/includes/header.php';

// Order data is stored in session by checkout.php
$order = $_SESSION['last_order'] ?? null;
$items = $order['items'] ?? [];

if (!$order) {
    // Try to find by order_number in URL
    $orderNum = $_GET['order'] ?? '';
    if ($orderNum) {
        $allOrders = readJsonFile('orders.json');
        foreach ($allOrders as $o) {
            if (($o['order_number'] ?? '') === $orderNum) {
                $order = $o;
                $items = $order['items'] ?? [];
                break;
            }
        }
    }

    if (!$order) {
        echo "<div class='container py-5 text-center'><h3>عذراً، لم نتمكن من العثور على هذا الطلب</h3><a href='index.php' class='btn btn-dark mt-3'>الرئيسية</a></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

// Get the WhatsApp URL
$waUrl = $_SESSION['last_wa_url'] ?? '';
if (empty($waUrl)) {
    $itemsForWa = [];
    foreach ($items as $item) {
        $itemsForWa[] = [
            'product_name'  => $item['product_name'] ?? $item['name'] ?? '',
            'selected_size' => $item['selected_size'] ?? $item['size'] ?? '54',
            'selected_color'=> $item['selected_color'] ?? $item['color'] ?? 'أسود',
            'price'         => $item['price'],
            'quantity'      => $item['quantity'],
        ];
    }
    $waUrl = buildWhatsAppUrl($order, $itemsForWa, (float)$order['total'], (float)($order['discount_amount'] ?? 0), $order['payment_method'] ?? 'cod');
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-secondary-subtle text-center">
                <!-- Success Icon -->
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow" style="width: 80px; height: 80px; font-size: 2.5rem;">
                        <i class="bi bi-check2"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="fs-1">🎉</span>
                    <h1 class="h2 fw-bold text-dark mt-2 mb-2">تم تأكيد طلبك!</h1>
                    <p class="fs-5 text-muted mb-2">شكراً لتسوقك من MANON 🤍</p>
                    <div class="badge bg-dark text-warning fs-6 px-4 py-2 mb-3">رقم الطلب: #<?= e($order['order_number']) ?></div>
                    <p class="text-secondary small mb-4">سيتم التواصل معك لتأكيد الطلب وشحنه بأسرع وقت.</p>
                </div>

                <!-- THE WHATSAPP BUTTON -->
                <div class="my-4">
                    <a href="<?= $waUrl ?>" target="_blank" class="btn btn-whatsapp-order btn-lg px-5 py-3 fs-5 shadow" id="openWaDirectBtn">
                        <i class="bi bi-whatsapp fs-3 me-2"></i> التواصل عبر واتساب
                    </a>
                </div>

                <!-- Order Details Card -->
                <div class="text-start bg-light p-4 rounded-4 border mt-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">ملخص الطلب:</h5>
                    <div class="row g-2 mb-3 small">
                        <div class="col-sm-6">
                            <strong>اسم العميل:</strong> <?= e($order['customer_name']) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong>رقم الهاتف:</strong> <?= e($order['phone']) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong>المحافظة:</strong> <?= e($order['city']) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong>العنوان:</strong> <?= e($order['address']) ?>
                        </div>
                        <div class="col-sm-6">
                            <strong>طريقة الدفع:</strong> <?= ($order['payment_method'] ?? 'cod') === 'electronic' ? 'دفع إلكتروني' : 'الدفع عند الاستلام' ?>
                        </div>
                        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                            <div class="col-sm-6 text-danger">
                                <strong>كود الخصم المطبق:</strong> <?= e($order['discount_code'] ?? '') ?> (-<?= formatPrice($order['discount_amount']) ?>)
                            </div>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold mb-2 text-dark">المنتجات:</h6>
                    <div class="border rounded bg-white p-2 mb-3">
                        <?php foreach ($items as $it): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong class="text-dark"><?= e($it['product_name'] ?? $it['name'] ?? '') ?></strong>
                                    <span class="text-muted small ms-2">
                                        (اللون: <?= e($it['selected_color'] ?? $it['color'] ?? 'أسود') ?> | مقاس <?= e($it['selected_size'] ?? $it['size'] ?? '54') ?>) × <?= $it['quantity'] ?>
                                    </span>
                                </div>
                                <span class="fw-bold text-dark"><?= formatPrice(($it['price'] * $it['quantity'])) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                            <div class="d-flex justify-content-between align-items-center pt-2 text-muted small">
                                <span>المجموع قبل الخصم:</span>
                                <span><?= formatPrice($order['subtotal'] ?? $order['total']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 text-danger small">
                                <span>قيمة الخصم:</span>
                                <span>- <?= formatPrice($order['discount_amount']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2 fw-bold fs-5 text-dark border-top">
                            <span>الإجمالي النهائي:</span>
                            <span><?= formatPrice($order['total']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-2 d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-manon-outline">العودة للرئيسية</a>
                    <a href="shop.php" class="btn btn-manon-primary">مواصلة التسوق</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Automatically launch WhatsApp after order confirmation
window.addEventListener('DOMContentLoaded', () => {
    const waBtn = document.getElementById('openWaDirectBtn');
    if (waBtn && <?= isset($_SESSION['last_order']) ? 'true' : 'false' ?>) {
        setTimeout(() => {
            window.open('<?= $waUrl ?>', '_blank');
        }, 800);
    }
});
</script>

<?php
unset($_SESSION['last_order']);
unset($_SESSION['last_wa_url']);
require_once __DIR__ . '/includes/footer.php';
?>
