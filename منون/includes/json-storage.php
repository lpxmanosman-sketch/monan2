<?php
/**
 * MANON — JSON Flat-File Storage Library
 * Replaces all MySQL/PDO operations with file-based JSON CRUD.
 * Uses flock() for safe concurrent writes.
 */
require_once __DIR__ . '/config.php';

// =========================================================
// CORE READ / WRITE
// =========================================================

/**
 * Read a JSON file and return decoded array.
 * Returns [] if file doesn't exist or is invalid.
 */
function readJsonFile(string $filename): array {
    $path = DATA_PATH . $filename;
    if (!file_exists($path)) {
        return [];
    }
    $fp = fopen($path, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Write data array to a JSON file atomically.
 * Uses exclusive lock + temp file to prevent corruption.
 */
function writeJsonFile(string $filename, array $data): bool {
    $path    = DATA_PATH . $filename;
    $tmpPath = $path . '.tmp.' . getmypid();
    $json    = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;

    // Write to temp file
    if (file_put_contents($tmpPath, $json, LOCK_EX) === false) {
        return false;
    }
    // Atomic rename
    return rename($tmpPath, $path);
}

// =========================================================
// ID GENERATION
// =========================================================

/**
 * Generate a unique numeric ID (max existing + 1).
 */
function generateNumericId(array $data): int {
    if (empty($data)) return 1;
    $ids = array_column($data, 'id');
    return (int)max($ids) + 1;
}

/**
 * Generate a formatted order number like MANON-2026-0001
 */
function generateOrderNumber(array $orders): string {
    $year = date('Y');
    // Filter orders from this year
    $yearOrders = array_filter($orders, function($o) use ($year) {
        return strpos($o['order_number'] ?? '', "MANON-{$year}-") === 0;
    });
    $count = count($yearOrders) + 1;
    return sprintf('MANON-%s-%04d', $year, $count);
}

// =========================================================
// FIND HELPERS
// =========================================================

/**
 * Find a single item by its 'id' field.
 */
function findItemById(array $data, $id): ?array {
    foreach ($data as $item) {
        if ((string)($item['id'] ?? '') === (string)$id) {
            return $item;
        }
    }
    return null;
}

/**
 * Find a single item by an arbitrary field value.
 */
function findItemBy(array $data, string $field, $value): ?array {
    foreach ($data as $item) {
        if (isset($item[$field]) && (string)$item[$field] === (string)$value) {
            return $item;
        }
    }
    return null;
}

// =========================================================
// UPDATE / DELETE
// =========================================================

/**
 * Update an item in-place by id. Returns true if found & updated.
 * @param array $data  The full array (passed by reference)
 * @param int|string $id
 * @param array $newData  Fields to merge/overwrite
 */
function updateItemById(array &$data, $id, array $newData): bool {
    foreach ($data as &$item) {
        if ((string)($item['id'] ?? '') === (string)$id) {
            $item = array_merge($item, $newData);
            return true;
        }
    }
    unset($item);
    return false;
}

/**
 * Delete an item from the array by id. Returns true if deleted.
 */
function deleteItemById(array &$data, $id): bool {
    foreach ($data as $k => $item) {
        if ((string)($item['id'] ?? '') === (string)$id) {
            array_splice($data, $k, 1);
            return true;
        }
    }
    return false;
}

// =========================================================
// PRODUCT HELPERS
// =========================================================

/**
 * Get all active products with their category name injected.
 */
function getAllProducts(bool $activeOnly = true): array {
    $products   = readJsonFile('products.json');
    $categories = readJsonFile('categories.json');
    $catMap     = [];
    foreach ($categories as $c) {
        $catMap[$c['id']] = $c['name'];
    }
    $result = [];
    foreach ($products as $p) {
        if ($activeOnly && empty($p['is_active'])) continue;
        $p['category_name'] = $catMap[$p['category_id'] ?? 0] ?? '';
        $result[] = $p;
    }
    return $result;
}

/**
 * Get a single product by id, with category_name injected.
 */
function getProductById($id): ?array {
    $products = getAllProducts(false);
    foreach ($products as $p) {
        if ((string)$p['id'] === (string)$id) return $p;
    }
    return null;
}

/**
 * Increment a product's view count in products.json
 */
function incrementProductViews($id): void {
    $products = readJsonFile('products.json');
    foreach ($products as &$p) {
        if ((string)$p['id'] === (string)$id) {
            $p['views_count'] = (int)($p['views_count'] ?? 0) + 1;
            break;
        }
    }
    unset($p);
    writeJsonFile('products.json', $products);
}
