<?php
/**
 * Admin - Categories Management
 */
$adminTitle = 'إدارة تصنيفات العبايات';
require_once __DIR__ . '/includes/header.php';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder   = (int)($_POST['sort_order'] ?? 0);
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($name)) {
            $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($name));
            $slug = trim($slug, '-') ?: 'cat-' . time();

            // Image
            $imgPath = 'assets/images/cat1.jpg';
            if (!empty($_FILES['image']['name'])) {
                $upload = handleImageUpload($_FILES['image'], 'categories');
                if ($upload['success']) {
                    $imgPath = $upload['path'];
                }
            }

            $allCats = readJsonFile('categories.json');

            // Check for duplicate slug
            $slugExists = !empty(array_filter($allCats, fn($c) => ($c['slug'] ?? '') === $slug));
            if ($slugExists) {
                $slug = $slug . '-' . time();
            }

            $newCat = [
                'id'          => generateNumericId($allCats),
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'image'       => $imgPath,
                'sort_order'  => $sortOrder,
                'is_active'   => $isActive,
            ];

            $allCats[] = $newCat;
            writeJsonFile('categories.json', $allCats);
            setFlash('success', 'تمت إضافة التصنيف بنجاح!');
        }
    }
    header('Location: categories.php');
    exit;
}

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId   = (int)$_GET['id'];
    $allProds = getAllProducts(false);

    // Check if products exist in this category
    $prodCount = count(array_filter($allProds, fn($p) => (int)($p['category_id'] ?? 0) === $delId));

    if ($prodCount > 0) {
        setFlash('danger', 'لا يمكن حذف هذا التصنيف لوجود عبايات مرتبطة به. يرجى نقل العبايات أو حذفها أولاً.');
    } else {
        $allCats = readJsonFile('categories.json');
        $result  = deleteItemById($allCats, $delId);
        if ($result !== false) {
            writeJsonFile('categories.json', $result);
            setFlash('success', 'تم حذف التصنيف بنجاح.');
        }
    }
    header('Location: categories.php');
    exit;
}

// Fetch categories and attach product counts
$allCats  = readJsonFile('categories.json');
$allProds = getAllProducts(false);

// Sort by sort_order asc, then id asc
usort($allCats, function ($a, $b) {
    $so = (int)($a['sort_order'] ?? 0) - (int)($b['sort_order'] ?? 0);
    return $so !== 0 ? $so : (int)($a['id'] ?? 0) - (int)($b['id'] ?? 0);
});

// Count products per category
$prodCountMap = [];
foreach ($allProds as $prod) {
    $cid = (int)($prod['category_id'] ?? 0);
    $prodCountMap[$cid] = ($prodCountMap[$cid] ?? 0) + 1;
}

// Attach products_count to each category
$categories = array_map(function ($cat) use ($prodCountMap) {
    $cat['products_count'] = $prodCountMap[(int)$cat['id']] ?? 0;
    return $cat;
}, $allCats);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">تصنيفات ومجموعات العبايات</h4>
        <p class="text-muted small mb-0">إدارة الأقسام وتحديد ترتيب ظهورها في المتجر</p>
    </div>
    <button class="btn btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#addCategoryCollapse">
        <i class="bi bi-plus-lg me-1"></i> إضافة تصنيف جديد
    </button>
</div>

<!-- Add Category Collapse Form -->
<div class="collapse mb-4" id="addCategoryCollapse">
    <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary-subtle">
        <h5 class="fw-bold mb-3 pb-2 border-bottom">إضافة تصنيف جديد</h5>
        <form action="categories.php" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">اسم التصنيف <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: عبايات كلوش فاخرة" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">ترتيب الظهور</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">صورة التصنيف</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">وصف التصنيف</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="وصف قصير يوضح طابع المجموعة وخاماتها..."></textarea>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="newCatActive" checked>
                        <label class="form-check-label fw-bold small" for="newCatActive">تفعيل التصنيف وعرضه فوراً</label>
                    </div>
                    <button type="submit" class="btn btn-dark px-4">حفظ التصنيف</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table -->
<div class="bg-white rounded-4 shadow-sm border border-secondary-subtle overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 80px;">الصورة</th>
                    <th>اسم التصنيف</th>
                    <th>الرابط (Slug)</th>
                    <th>عدد العبايات</th>
                    <th>الترتيب</th>
                    <th>الحالة</th>
                    <th class="text-center" style="width: 120px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL . e($cat['image'] ?: 'assets/images/cat1.jpg') ?>" class="rounded-3 border object-fit-cover" style="width: 60px; height: 50px;">
                        </td>
                        <td>
                            <strong class="text-dark"><?= e($cat['name']) ?></strong>
                            <div class="small text-muted text-truncate" style="max-width: 300px;"><?= e($cat['description']) ?></div>
                        </td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2"><?= $cat['products_count'] ?> عباية</span>
                        </td>
                        <td><?= $cat['sort_order'] ?></td>
                        <td>
                            <?= $cat['is_active'] ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-secondary">معطل</span>' ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>shop.php?category=<?= e($cat['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="معاينة بالمتجر">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف هذا التصنيف؟')" title="حذف">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
