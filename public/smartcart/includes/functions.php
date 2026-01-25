<?php
/**
 * SmartCart - Helper Functions
 */

/**
 * Set CORS headers
 */
function setCorsHeaders(): void {
    header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function errorResponse(string $message, int $code = 400): void {
    jsonResponse(['error' => true, 'message' => $message], $code);
}

/**
 * Get JSON body from request
 */
function getJsonBody(): array {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

/**
 * Sanitize string
 */
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate price per kg
 */
function calculatePricePerKg(float $price, ?float $weight, string $unit): ?float {
    if (!$weight || $weight <= 0) return null;

    $weightInGrams = match(strtolower($unit)) {
        'кг', 'kg' => $weight * 1000,
        'г', 'g' => $weight,
        'л', 'l' => $weight * 1000,
        'мл', 'ml' => $weight,
        default => null
    };

    if (!$weightInGrams) return null;

    return round(($price / $weightInGrams) * 1000, 2);
}

/**
 * Format price
 */
function formatPrice(float $price): string {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

/**
 * Get relative time
 */
function relativeTime(string $datetime): string {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $diff = $now->diff($date);

    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) return 'только что';
            return $diff->i . ' мин назад';
        }
        return $diff->h . ' ч назад';
    }

    if ($diff->days == 1) return 'вчера';
    if ($diff->days < 7) return $diff->days . ' дн назад';

    return $date->format('d.m.Y');
}

/**
 * Match product by search term
 */
function matchProduct(string $productName, string $searchTerm): bool {
    $productName = mb_strtolower($productName);
    $searchTerm = mb_strtolower($searchTerm);

    // Exact match
    if (str_contains($productName, $searchTerm)) {
        return true;
    }

    // Word-by-word match
    $searchWords = preg_split('/\s+/', $searchTerm);
    $matchCount = 0;

    foreach ($searchWords as $word) {
        if (mb_strlen($word) >= 3 && str_contains($productName, $word)) {
            $matchCount++;
        }
    }

    return $matchCount >= count($searchWords) * 0.7;
}

/**
 * Get best price for product across all stores or specific store
 */
function getBestPrice(int $productId, ?int $storeId = null): ?array {
    $sql = "SELECT p.*, s.name as store_name, s.slug as store_slug
            FROM prices p
            JOIN stores s ON p.store_id = s.id
            WHERE p.product_id = ? AND p.is_available = 1";
    $params = [$productId];

    if ($storeId) {
        $sql .= " AND p.store_id = ?";
        $params[] = $storeId;
    }

    $sql .= " ORDER BY p.price ASC LIMIT 1";

    $result = Database::query($sql, $params);
    return $result[0] ?? null;
}

/**
 * Calculate recipe cost
 */
function calculateRecipeCost(int $recipeId, ?int $storeId = null): array {
    $ingredients = Database::query(
        "SELECT ri.*, p.id as product_id
         FROM recipe_ingredients ri
         LEFT JOIN products p ON ri.product_id = p.id
         WHERE ri.recipe_id = ?",
        [$recipeId]
    );

    $total = 0;
    $missing = [];
    $items = [];

    foreach ($ingredients as $ing) {
        $price = null;

        if ($ing['product_id']) {
            // Find price by product_id
            $sql = "SELECT p.*, s.name as store_name
                    FROM prices p
                    JOIN stores s ON p.store_id = s.id
                    WHERE p.product_id = ? AND p.is_available = 1";
            $params = [$ing['product_id']];

            if ($storeId) {
                $sql .= " AND p.store_id = ?";
                $params[] = $storeId;
            }

            $sql .= " ORDER BY p.price ASC LIMIT 1";
            $prices = Database::query($sql, $params);
            $price = $prices[0] ?? null;
        } else {
            // Find price by name search
            $sql = "SELECT p.*, s.name as store_name
                    FROM prices p
                    JOIN stores s ON p.store_id = s.id
                    WHERE p.is_available = 1";
            $params = [];

            if ($storeId) {
                $sql .= " AND p.store_id = ?";
                $params[] = $storeId;
            }

            $allPrices = Database::query($sql, $params);

            foreach ($allPrices as $p) {
                if (matchProduct($p['store_product_name'], $ing['product_name'])) {
                    if (!$price || $p['price'] < $price['price']) {
                        $price = $p;
                    }
                }
            }
        }

        if ($price) {
            // Calculate cost based on quantity needed
            $pricePerUnit = $price['weight'] > 0 ? $price['price'] / $price['weight'] : $price['price'];
            $cost = $pricePerUnit * $ing['quantity'];
            $total += $cost;

            $items[] = [
                'name' => $ing['product_name'],
                'quantity' => $ing['quantity'],
                'unit' => $ing['unit'],
                'price' => $price['price'],
                'cost' => round($cost, 2),
                'store' => $price['store_name'] ?? null
            ];
        } else {
            $missing[] = $ing['product_name'];
        }
    }

    return [
        'total' => round($total, 2),
        'items' => $items,
        'missing' => $missing,
        'complete' => count($missing) === 0
    ];
}

/**
 * Compare stores for shopping list
 */
function compareStoresForCart(array $cartItems): array {
    $stores = Database::query("SELECT * FROM stores WHERE is_active = 1");
    $comparison = [];

    foreach ($stores as $store) {
        $total = 0;
        $available = 0;
        $missing = [];

        foreach ($cartItems as $item) {
            $price = null;

            // Try to find by product_id first
            if (!empty($item['product_id'])) {
                $prices = Database::query(
                    "SELECT * FROM prices WHERE product_id = ? AND store_id = ? AND is_available = 1 ORDER BY price ASC LIMIT 1",
                    [$item['product_id'], $store['id']]
                );
                $price = $prices[0] ?? null;
            }

            // If not found, search by name
            if (!$price) {
                $allPrices = Database::query(
                    "SELECT * FROM prices WHERE store_id = ? AND is_available = 1",
                    [$store['id']]
                );

                $searchTerm = $item['search_term'] ?? $item['product_name'] ?? '';
                foreach ($allPrices as $p) {
                    if (matchProduct($p['store_product_name'], $searchTerm)) {
                        if (!$price || $p['price'] < $price['price']) {
                            $price = $p;
                        }
                    }
                }
            }

            if ($price) {
                $qty = $item['quantity'] ?? 1;
                $total += $price['price'] * $qty;
                $available++;
            } else {
                $missing[] = $item['product_name'] ?? $item['search_term'] ?? 'Unknown';
            }
        }

        $comparison[] = [
            'store' => [
                'id' => $store['id'],
                'slug' => $store['slug'],
                'name' => $store['name'],
            ],
            'total' => round($total, 2),
            'available' => $available,
            'missing' => $missing,
            'delivery_time' => $store['delivery_time_min'] . '-' . $store['delivery_time_max'] . ' мин',
            'min_order' => $store['min_order']
        ];
    }

    // Sort by total price
    usort($comparison, fn($a, $b) => $a['total'] <=> $b['total']);

    return $comparison;
}

/**
 * Get statistics
 */
function getStats(): array {
    $productsCount = Database::query("SELECT COUNT(*) as cnt FROM products")[0]['cnt'];
    $pricesCount = Database::query("SELECT COUNT(*) as cnt FROM prices")[0]['cnt'];
    $recipesCount = Database::query("SELECT COUNT(*) as cnt FROM recipes")[0]['cnt'];
    $cartCount = Database::query("SELECT COUNT(*) as cnt FROM shopping_list WHERE is_checked = 0")[0]['cnt'];

    $lastSync = Database::query("SELECT parsed_at FROM prices ORDER BY parsed_at DESC LIMIT 1");
    $lastSyncTime = $lastSync[0]['parsed_at'] ?? null;

    return [
        'products' => $productsCount,
        'prices' => $pricesCount,
        'recipes' => $recipesCount,
        'cart_items' => $cartCount,
        'last_sync' => $lastSyncTime ? relativeTime($lastSyncTime) : 'никогда'
    ];
}
