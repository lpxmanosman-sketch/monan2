<?php
/**
 * Admin - Manage Customer Reviews (إدارة آراء العملاء)
 */
$adminTitle = 'إدارة آراء وتقييمات العملاء';
require_once __DIR__ . '/includes/header.php';

// Handle Actions (Approve, Reject, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = (string)($_POST['id'] ?? '');
    $reviews = readJsonFile('reviews.json');

    if ($action === 'approve' && $id) {
        updateItemById($reviews, $id, ['status' => 'approved']);
        writeJsonFile('reviews.json', $reviews);
        setFlash('success', 'تم اعتماد ونشر التقييم في المتجر بنجاح');
        header('Location: reviews.php');
        exit;
    }

    if ($action === 'reject' && $id) {
        updateItemById($reviews, $id, ['status' => 'rejected']);
        writeJsonFile('reviews.json', $reviews);
        setFlash('warning', 'تم رفض التقييم وإخفاؤه');
        header('Location: reviews.php');
        exit;
    }

    if ($action === 'delete' && $id) {
        deleteItemById($reviews, $id);
        writeJsonFile('reviews.json', $reviews);
        setFlash('success', 'تم حذف التقييم نهائياً');
        header('Location: reviews.php');
        exit;
    }
}

// Load all reviews
$allReviews   = readJsonFile('reviews.json');
$statusFilter = $_GET['status'] ?? 'all';

// Build counts
$counts = [
    'all'      => count($allReviews),
    'pending'  => count(array_filter($allReviews, fn($r) => ($r['status'] ?? '') === 'pending')),
    'approved' => count(array_filter($allReviews, fn($r) => ($r['status'] ?? '') === 'approved')),
    'rejected' => count(array_filter($allReviews, fn($r) => ($r['status'] ?? '') === 'rejected')),
];

// Filter by status
if ($statusFilter !== 'all') {
    $reviews = array_values(array_filter($allReviews, fn($r) => ($r['status'] ?? '') === $statusFilter));
} else {
    $reviews = $allReviews;
}

// Sort by id descending (newest first)
usort($reviews, fn($a, $b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">مراجعة وتقييمات العميلات</h4>
        <p class="text-muted small mb-0">اعتماد ومراجعة التعليقات والتجارب المكتوبة قبل ظهورها في الصفحة الرئيسية للمتجر</p>
    </div>
</div>

<!-- Tabs Bar -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="reviews.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3">
        كل التقييمات (<?= $counts['all'] ?>)
    </a>
    <a href="reviews.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-danger' : 'btn-outline-danger' ?> rounded-pill px-3">
        <i class="bi bi-clock-history me-1"></i> بانتظار الاعتماد (<?= $counts['pending'] ?>)
    </a>
    <a href="reviews.php?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">
        <i class="bi bi-check2-circle me-1"></i> المعتمدة والمنشورة (<?= $counts['approved'] ?>)
    </a>
    <a href="reviews.php?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-secondary' : 'btn-outline-secondary' ?> rounded-pill px-3">
        المرفوضة (<?= $counts['rejected'] ?>)
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">#</th>
                    <th>العميلة</th>
                    <th>المدينة</th>
                    <th>التقييم</th>
                    <th>نص التجربة والتعليق</th>
                    <th>تاريخ الإرسال</th>
                    <th>الحالة</th>
                    <th class="text-end pe-4">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">لا توجد تقييمات مطابقة لهذا الفلتر</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $rev['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($rev['customer_name']) ?></div>
                            </td>
                            <td>
                                <span class="text-muted small"><?= e($rev['customer_city'] ?? 'غير محدد') ?></span>
                            </td>
                            <td>
                                <div class="text-warning">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td style="max-width: 320px;">
                                <div class="p-2 bg-light rounded-3 small text-dark lh-base">
                                    "<?= nl2br(e($rev['review_text'])) ?>"
                                </div>
                            </td>
                            <td class="small text-muted">
                                <?= date('Y/m/d H:i', strtotime($rev['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($rev['status'] === 'approved'): ?>
                                    <span class="badge bg-success">معتمد ومنشور</span>
                                <?php elseif ($rev['status'] === 'pending'): ?>
                                    <span class="badge bg-danger">بانتظار المراجعة</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">مرفوض</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if ($rev['status'] !== 'approved'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" title="اعتماد ونشر في الموقع">
                                                <i class="bi bi-check2 me-1"></i> اعتماد
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($rev['status'] !== 'rejected'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="رفض">
                                                <i class="bi bi-x-circle me-1"></i> رفض
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" onsubmit="return confirm('هل تريد حذف هذا التقييم نهائياً؟');" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $rev['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
