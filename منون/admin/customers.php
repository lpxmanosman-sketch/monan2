<?php
/**
 * Admin - Customers Directory
 */
$adminTitle = 'سجل العميلات وبيانات الطلب';
require_once __DIR__ . '/includes/header.php';

// Group unique customers by phone from orders.json
$orders = readJsonFile('orders.json');
$customerMap = [];

foreach ($orders as $o) {
    $phone = trim($o['phone'] ?? '');
    if ($phone === '') continue;

    if (!isset($customerMap[$phone])) {
        $customerMap[$phone] = [
            'customer_name'   => $o['customer_name'] ?? 'عميلة',
            'phone'           => $phone,
            'whatsapp'        => $o['whatsapp'] ?? $phone,
            'city'            => $o['city'] ?? '',
            'address'         => $o['address'] ?? '',
            'total_orders'    => 0,
            'total_spent'     => 0.0,
            'last_order_date' => $o['created_at'] ?? '',
        ];
    }

    $customerMap[$phone]['total_orders']++;
    $customerMap[$phone]['total_spent'] += (float)($o['total'] ?? 0);

    // Keep the latest date and most recent name/address
    if (strcmp($o['created_at'] ?? '', $customerMap[$phone]['last_order_date']) > 0) {
        $customerMap[$phone]['last_order_date'] = $o['created_at'];
        if (!empty($o['customer_name'])) $customerMap[$phone]['customer_name'] = $o['customer_name'];
        if (!empty($o['city'])) $customerMap[$phone]['city'] = $o['city'];
        if (!empty($o['address'])) $customerMap[$phone]['address'] = $o['address'];
    }
}

$customers = array_values($customerMap);

// Sort by total_orders DESC, last_order_date DESC
usort($customers, function($a, $b) {
    $cmp = ($b['total_orders'] <=> $a['total_orders']);
    return $cmp !== 0 ? $cmp : strcmp($b['last_order_date'], $a['last_order_date']);
});
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">سجل العميلات</h4>
        <p class="text-muted small mb-0">بيانات العميلات وقيمة مشترياتهن وتاريخ آخر طلب</p>
    </div>
</div>

<div class="bg-white rounded-4 shadow-sm border border-secondary-subtle overflow-hidden">
    <?php if (empty($customers)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-people fs-1 d-block mb-2"></i>
            لا توجد بيانات عميلات مسجلة بعد.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>اسم العميلة</th>
                        <th>رقم الهاتف / واتساب</th>
                        <th>المحافظة</th>
                        <th>عدد الطلبات</th>
                        <th>إجمالي المشتريات</th>
                        <th>تاريخ آخر طلب</th>
                        <th class="text-center" style="width: 130px;">تواصل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): 
                        $phoneNum = preg_replace('/\D/', '', $c['phone']);
                        if (strpos($phoneNum, '20') !== 0 && strlen($phoneNum) === 11 && strpos($phoneNum, '01') === 0) {
                            $phoneNum = '2' . $phoneNum;
                        }
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= e($c['customer_name']) ?></strong>
                            </td>
                            <td>
                                <div class="dir-ltr text-end fw-semibold"><?= e($c['phone']) ?></div>
                            </td>
                            <td><?= e($c['city']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2"><?= $c['total_orders'] ?> طلب</span>
                            </td>
                            <td class="fw-bold text-dark">
                                <?= formatPrice($c['total_spent']) ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('Y/m/d', strtotime($c['last_order_date'])) ?>
                            </td>
                            <td class="text-center">
                                <a href="https://wa.me/<?= $phoneNum ?>" target="_blank" class="btn btn-sm btn-success" title="محادثة واتساب">
                                    <i class="bi bi-whatsapp"></i> واتساب
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
