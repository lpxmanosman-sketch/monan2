<?php
/**
 * Categories Showcase Page - MANON
 */
$pageTitle = 'تشكيلة المجموعات والتصنيفات | منون';
require_once __DIR__ . '/includes/header.php';

// Fetch categories with product counts from JSON
$allCategories = readJsonFile('categories.json');
$allProducts   = readJsonFile('products.json');

// Count active products per category
$productCounts = [];
foreach ($allProducts as $p) {
    if (!empty($p['is_active'])) {
        $catId = $p['category_id'] ?? 0;
        $productCounts[$catId] = ($productCounts[$catId] ?? 0) + 1;
    }
}

$categories = [];
foreach ($allCategories as $c) {
    if (!empty($c['is_active'])) {
        $c['products_count'] = $productCounts[$c['id']] ?? 0;
        $categories[] = $c;
    }
}

// Sort by sort_order ASC, id ASC
usort($categories, function($a, $b) {
    $cmp = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    return $cmp !== 0 ? $cmp : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
});
?>

<div class="py-5 text-center" style="background: linear-gradient(135deg, #F8F3EC 0%, #EFE5D7 100%); border-bottom: 1px solid var(--color-border);">
    <div class="container">
        <span class="section-tag">OUR EXCLUSIVE LINES</span>
        <h1 class="display-5 fw-bold text-dark mb-2">تصنيفات ومجموعات منون</h1>
        <p class="text-muted fs-6 max-w-600 mx-auto">
            تصفحي تشكيلاتنا المتنوعة من العبايات الكلاسيكية والفاخرة والعملية المصممة بعناية فائقة لتناسب كل مناسبة وأوقاتك اليومية.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
            <div class="col-lg-4 col-md-6">
                <div class="category-card position-relative overflow-hidden shadow-sm" style="height: 380px;">
                    <img src="<?= e($cat['image'] ?: 'assets/images/cat1.jpg') ?>" alt="<?= e($cat['name']) ?>">
                    <div class="category-overlay"></div>
                    <div class="category-info p-4 w-100">
                        <span class="badge bg-warning text-dark mb-2"><?= $cat['products_count'] ?> عبايات متوفرة</span>
                        <h2 class="category-name text-white fs-3 mb-2"><?= e($cat['name']) ?></h2>
                        <p class="text-light opacity-75 small mb-3"><?= e($cat['description']) ?></p>
                        <a href="shop.php?category=<?= e($cat['slug']) ?>" class="btn btn-sm btn-manon-gold">
                            <span>استعراض المجموعة</span>
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
