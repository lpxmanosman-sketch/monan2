<?php
/**
 * Admin - Add New Product
 */
$adminTitle = 'إضافة عباية جديدة';
require_once __DIR__ . '/includes/header.php';

// Load dropdown data from JSON
$allCategories = readJsonFile('categories.json');
$categories    = array_values(array_filter($allCategories, fn($c) => !empty($c['is_active'])));
usort($categories, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0) ?: strcmp($a['name'] ?? '', $b['name'] ?? ''));

$allColors    = readJsonFile('colors.json');
$activeColors = array_values(array_filter($allColors, fn($c) => !empty($c['is_active'])));
usort($activeColors, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'خطأ أمني: رمز CSRF غير صالح.';
    }

    $name       = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $sku        = trim($_POST['sku'] ?? '');
    $price      = (float)($_POST['price'] ?? 0);
    $salePrice  = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock      = (int)($_POST['stock_quantity'] ?? 10);
    $sizes      = trim($_POST['sizes'] ?? '52, 54, 56, 58, 60');
    $fabric     = trim($_POST['fabric'] ?? 'حرير كريب كوري فاخر');
    $color      = trim($_POST['color'] ?? 'أسود ملكي');
    $shortDesc  = trim($_POST['short_desc'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isNew      = isset($_POST['is_new']) ? 1 : 0;
    $isActive   = isset($_POST['is_active']) ? 1 : 0;
    // Colors checkboxes — store as array of color name strings
    $selectedColors = isset($_POST['colors']) && is_array($_POST['colors'])
        ? array_values(array_filter($_POST['colors'], fn($c) => $c !== ''))
        : [];

    if (empty($name)) {
        $errors[] = 'اسم العباية مطلوب.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'يرجى اختيار تصنيف للعباية.';
    }
    if ($price <= 0) {
        $errors[] = 'يرجى تحديد سعر صحيح أكبر من صفر.';
    }

    // Main Image Upload
    $mainImagePath = '';
    if (empty($_FILES['main_image']['name'])) {
        $errors[] = 'الصورة الرئيسية للعباية مطلوبة.';
    } else {
        $uploadResult = handleImageUpload($_FILES['main_image'], 'products');
        if (!$uploadResult['success']) {
            $errors[] = 'خطأ في الصورة الرئيسية: ' . $uploadResult['error'];
        } else {
            $mainImagePath = $uploadResult['path'];
        }
    }

    if (empty($errors)) {
        // Handle gallery images — upload each and collect paths
        $galleryImages = [];
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $galleryFiles = $_FILES['gallery_images'];
            $totalFiles   = count($galleryFiles['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($galleryFiles['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $galleryFiles['name'][$i],
                        'type'     => $galleryFiles['type'][$i],
                        'tmp_name' => $galleryFiles['tmp_name'][$i],
                        'error'    => $galleryFiles['error'][$i],
                        'size'     => $galleryFiles['size'][$i],
                    ];
                    $gRes = handleImageUpload($singleFile, 'products');
                    if ($gRes['success']) {
                        $galleryImages[] = $gRes['path'];
                    }
                }
            }
        }

        // Read existing products and generate new id
        $products = readJsonFile('products.json');
        $newId    = generateNumericId($products);

        $newProduct = [
            'id'             => $newId,
            'name'           => $name,
            'sku'            => $sku,
            'short_desc'     => $shortDesc,
            'description'    => $description,
            'price'          => $price,
            'sale_price'     => $salePrice,
            'category_id'    => $categoryId,
            'main_image'     => $mainImagePath,
            'images'         => $galleryImages,
            'colors'         => $selectedColors,
            'fabric'         => $fabric,
            'color'          => $color,
            'sizes'          => $sizes,
            'stock_quantity' => $stock,
            'is_featured'    => $isFeatured,
            'is_new'         => $isNew,
            'is_active'      => $isActive,
            'views_count'    => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $products[] = $newProduct;

        if (writeJsonFile('products.json', $products)) {
            setFlash('success', 'تمت إضافة العباية بنجاح إلى المتجر!');
            header('Location: products.php');
            exit;
        } else {
            $errors[] = 'حدث خطأ أثناء الحفظ في ملف البيانات. يرجى المحاولة مرة أخرى.';
        }
    }
}
?>

<div class="mb-4">
    <a href="products.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="bi bi-arrow-right me-1"></i> العودة لقائمة المنتجات
    </a>
    <h4 class="fw-bold text-dark">إضافة عباية جديدة</h4>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-4 shadow-sm mb-4">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="product-add.php" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary-subtle mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">المعلومات الأساسية</h5>

                <div class="mb-3">
                    <label class="form-label small fw-bold">اسم العباية <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-lg" placeholder="مثال: عباية ملكية بتطريز ذهبي خافت" value="<?= e($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">التصنيف <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">اختاري التصنيف...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">كود المنتج (SKU)</label>
                        <input type="text" name="sku" class="form-control" placeholder="MANON-007" value="<?= e($_POST['sku'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">السعر الأصلي (ج.م) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" step="0.5" placeholder="1500" value="<?= e($_POST['price'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">سعر الخصم / العرض (ج.م)</label>
                        <input type="number" name="sale_price" class="form-control" step="0.5" placeholder="اتركيه فارغاً إذا لم يوجد خصم" value="<?= e($_POST['sale_price'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">الخامة والقماش</label>
                        <input type="text" name="fabric" class="form-control" placeholder="كريب فرسان ياباني" value="<?= e($_POST['fabric'] ?? 'حرير كريب كوري فاخر') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">اللون</label>
                        <input type="text" name="color" class="form-control" placeholder="أسود فاحم ملكي" value="<?= e($_POST['color'] ?? 'أسود فاحم ملكي') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">المقاسات المتاحة (مفصولة بفواصل)</label>
                    <input type="text" name="sizes" class="form-control" value="<?= e($_POST['sizes'] ?? '52, 54, 56, 58, 60') ?>">
                </div>

                <?php if (!empty($activeColors)): ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold">الألوان المتاحة</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $postColors = isset($_POST['colors']) && is_array($_POST['colors']) ? $_POST['colors'] : [];
                        foreach ($activeColors as $clr):
                        ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="colors[]"
                                       id="color_add_<?= $clr['id'] ?>"
                                       value="<?= e($clr['name']) ?>"
                                       <?= in_array($clr['name'], $postColors) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="color_add_<?= $clr['id'] ?>">
                                    <span class="d-inline-block rounded-circle border me-1"
                                          style="width:14px;height:14px;background:<?= e($clr['hex'] ?? '#ccc') ?>;vertical-align:middle;"></span>
                                    <?= e($clr['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">وصف موجز (يظهر في المعاينات)</label>
                    <textarea name="short_desc" class="form-control" rows="2" placeholder="وصف سريع يبرز قصة العباية وتطريزها..."><?= e($_POST['short_desc'] ?? '') ?></textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold">الوصف الكامل والمفصل</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="تفاصيل القصة، الملحقات، إرشادات العناية..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Media & Settings Sidebar -->
        <div class="col-lg-4">
            <!-- Images Upload Card -->
            <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary-subtle mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">صور العباية</h5>

                <div class="mb-3">
                    <label class="form-label small fw-bold">الصورة الرئيسية <span class="text-danger">*</span></label>
                    <input type="file" name="main_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text small">الحجم الأقصى: 5 ميجابايت (JPG, PNG, WebP)</div>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold">معرض صور إضافية (اختياري)</label>
                    <input type="file" name="gallery_images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text small">يمكنكِ اختيار عدة صور لتفاصيل الأكمام والتطريز.</div>
                </div>
            </div>

            <!-- Inventory & Visibility Card -->
            <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary-subtle mb-4">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">المخزون والحالة</h5>

                <div class="mb-3">
                    <label class="form-label small fw-bold">كمية المخزون المتاحة</label>
                    <input type="number" name="stock_quantity" class="form-control" value="<?= e($_POST['stock_quantity'] ?? '15') ?>" min="0">
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" checked>
                    <label class="form-check-label fw-bold small" for="isActiveCheck">تفعيل العرض في المتجر</label>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedCheck">
                    <label class="form-check-label fw-bold small" for="isFeaturedCheck">تمييز في قسم المختارات</label>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_new" id="isNewCheck" checked>
                    <label class="form-check-label fw-bold small" for="isNewCheck">عرض في كولكشن وصل حديثاً</label>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-2 fs-6">
                    <i class="bi bi-cloud-arrow-up me-1"></i> حفظ ونشر العباية
                </button>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
