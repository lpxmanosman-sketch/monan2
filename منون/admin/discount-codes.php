<?php
/**
 * Admin - Wheel of Fortune & Discount Codes
 */
$adminTitle = 'عجلة الحظ وأكواد الخصم';
require_once __DIR__ . '/includes/header.php';

// Handle Actions (Toggle Wheel, Add Manual Code, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_wheel') {
        $settings    = readJsonFile('settings.json');
        $current     = $settings['wheel_enabled'] ?? '1';
        $newVal      = ($current == '1') ? '0' : '1';
        $settings['wheel_enabled'] = $newVal;
        writeJsonFile('settings.json', $settings);
        setFlash('success', ($newVal == '1') ? 'تم تفعيل عجلة الحظ للزوار' : 'تم إيقاف عجلة الحظ');
        header('Location: discount-codes.php');
        exit;
    }

    if ($action === 'add_code') {
        $code    = strtoupper(trim($_POST['code'] ?? ''));
        $percent = (int)($_POST['discount_percent'] ?? 10);
        $days    = (int)($_POST['valid_days'] ?? 7);

        if (!empty($code) && $percent > 0 && $percent <= 100) {
            $codes     = readJsonFile('discount_codes.json');
            // Check for duplicate code
            $duplicate = false;
            foreach ($codes as $c) {
                if (strtoupper($c['code'] ?? '') === $code) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                setFlash('danger', 'كود الخصم هذا مسجل بالفعل!');
            } else {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
                $newCode   = [
                    'id'               => generateNumericId($codes),
                    'code'             => $code,
                    'discount_percent' => $percent,
                    'is_used'          => 0,
                    'expires_at'       => $expiresAt,
                    'created_at'       => date('Y-m-d H:i:s'),
                ];
                $codes[] = $newCode;
                writeJsonFile('discount_codes.json', $codes);
                setFlash('success', "تم إنشاء كود الخصم {$code} بنسبة {$percent}% بنجاح");
            }
        } else {
            setFlash('danger', 'يرجى إدخال كود ونسبة خصم صحيحة');
        }
        header('Location: discount-codes.php');
        exit;
    }

    if ($action === 'delete') {
        $id    = (string)($_POST['id'] ?? '');
        $codes = readJsonFile('discount_codes.json');
        if ($id && deleteItemById($codes, $id)) {
            writeJsonFile('discount_codes.json', $codes);
            setFlash('success', 'تم حذف كود الخصم');
        }
        header('Location: discount-codes.php');
        exit;
    }
}

// Current Wheel Setting
$settings     = readJsonFile('settings.json');
$wheelEnabled = ($settings['wheel_enabled'] ?? '1') == '1';

// Load codes (latest 100, sorted by id desc)
$allCodes = readJsonFile('discount_codes.json');
usort($allCodes, fn($a, $b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
$codes = array_slice($allCodes, 0, 100);

// Summary Stats
$totalCodes  = count($allCodes);
$usedCodes   = count(array_filter($allCodes, fn($c) => !empty($c['is_used'])));
$activeCodes = count(array_filter($allCodes, fn($c) => empty($c['is_used']) && strtotime($c['expires_at'] ?? '0') > time()));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">عجلة الحظ وقسائم الخصم</h4>
        <p class="text-muted small mb-0">تحكمي في ظهور عجلة الحظ، ونسب التخفيض، وأكواد الخصم الترويجية</p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="toggle_wheel">
            <button type="submit" class="btn <?= $wheelEnabled ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-gift me-1"></i>
                حالة العجلة: <?= $wheelEnabled ? 'مفعلة للزوار' : 'معطلة' ?>
            </button>
        </form>
        <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCodeModal">
            <i class="bi bi-plus-lg ms-1"></i> إنشاء كود خصم
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">إجمالي الأكواد المُصدرة</div>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?= $totalCodes ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">أكواد صالحة للاستخدام</div>
                    <h3 class="fw-bold text-success mt-2 mb-0"><?= $activeCodes ?></h3>
                </div>
                <div class="stat-icon" style="background: #E8F5E9; color: #2E7D32;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">أكواد تم استخدامها</div>
                    <h3 class="fw-bold text-primary mt-2 mb-0"><?= $usedCodes ?></h3>
                </div>
                <div class="stat-icon" style="background: #E3F2FD; color: #1565C0;">
                    <i class="bi bi-bag-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Discount Codes Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">سجل أكواد الخصم (آخر 100 كود)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">#</th>
                    <th>كود الخصم</th>
                    <th>نسبة الخصم</th>
                    <th>حالة الاستخدام</th>
                    <th>تاريخ الصلاحية</th>
                    <th>تاريخ الإنشاء</th>
                    <th class="text-end pe-4">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($codes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">لا توجد أكواد خصم مسجلة حتى الآن</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($codes as $c):
                        $isExpired = strtotime($c['expires_at'] ?? '0') < time();
                    ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $c['id'] ?></td>
                            <td>
                                <span class="fw-bold font-monospace px-2 py-1 bg-light border rounded text-dark"><?= e($c['code']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark fs-6"><?= $c['discount_percent'] ?>% خصم</span>
                            </td>
                            <td>
                                <?php if ($c['is_used']): ?>
                                    <span class="badge bg-secondary">مُستخدم سابقاً</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="badge bg-danger">منتهي الصلاحية</span>
                                <?php else: ?>
                                    <span class="badge bg-success">نشط ومتاح</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <span class="<?= $isExpired ? 'text-danger' : 'text-muted' ?>">
                                    <?= date('Y/m/d H:i', strtotime($c['expires_at'])) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= date('Y/m/d H:i', strtotime($c['created_at'])) ?>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" onsubmit="return confirm('هل تريد حذف هذا الكود نهائياً؟');" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Manual Code -->
<div class="modal fade" id="addCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">إنشاء كود خصم ترويجي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4 text-end">
                    <input type="hidden" name="action" value="add_code">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">رمز الكود (مثال: MANON20، VIP10) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase font-monospace" required placeholder="MANON20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">نسبة الخصم المئوية (%) <span class="text-danger">*</span></label>
                        <select name="discount_percent" class="form-select" required>
                            <option value="5">5% خصم</option>
                            <option value="10" selected>10% خصم</option>
                            <option value="15">15% خصم</option>
                            <option value="20">20% خصم</option>
                            <option value="25">25% خصم</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">مدة الصلاحية (بالأيام)</label>
                        <input type="number" name="valid_days" class="form-control" value="7" min="1" max="365">
                        <div class="form-text small">افتراضياً 7 أيام من تاريخ الإنشاء</div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">إنشاء الكود</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
