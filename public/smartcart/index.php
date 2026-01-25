<?php
/**
 * SmartCart - Router
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/smartcart';

// Remove base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strlen($basePath));
$path = trim($path, '/');

// Handle API routes
if (str_starts_with($path, 'api/')) {
    $apiPath = substr($path, 4); // Remove 'api/'
    $parts = explode('/', $apiPath);
    $endpoint = $parts[0] ?? '';

    // Set parameters for API files
    if (isset($parts[1])) {
        if ($endpoint === 'stores' && isset($parts[2])) {
            $_GET['slug'] = $parts[1];
            $_GET['action'] = $parts[2];
        } elseif ($endpoint === 'recipes' && isset($parts[2])) {
            $_GET['id'] = $parts[1];
            $_GET['action'] = $parts[2];
        } elseif ($endpoint === 'prices' && $parts[1] === 'bulk') {
            $_GET['action'] = 'bulk';
        } elseif ($endpoint === 'cart') {
            $_GET['action'] = $parts[1];
        } elseif ($endpoint === 'export') {
            $_GET['type'] = $parts[1];
        } else {
            // Default: treat second part as ID
            $_GET['id'] = $parts[1];
            if (isset($parts[2])) {
                $_GET['action'] = $parts[2];
            }
        }
    }

    // Route to API file
    $apiFile = __DIR__ . '/api/' . $endpoint . '.php';

    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    } else {
        setCorsHeaders();
        errorResponse('API endpoint not found', 404);
    }
}

// Initialize database (creates if not exists)
Database::getInstance();

// Get stats for pages
$stats = getStats();

// Route to pages
switch ($path) {
    case '':
    case 'dashboard':
        require __DIR__ . '/pages/dashboard.php';
        break;

    case 'recipes':
        require __DIR__ . '/pages/recipes.php';
        break;

    case 'products':
        require __DIR__ . '/pages/products.php';
        break;

    case 'prices':
        require __DIR__ . '/pages/prices.php';
        break;

    case 'cart':
        require __DIR__ . '/pages/cart.php';
        break;

    case 'analytics':
        require __DIR__ . '/pages/analytics.php';
        break;

    case 'settings':
        require __DIR__ . '/pages/settings.php';
        break;

    default:
        // Check if it's a recipe detail page
        if (preg_match('/^recipes\/(\d+)$/', $path, $matches)) {
            $_GET['id'] = $matches[1];
            require __DIR__ . '/pages/recipe-detail.php';
            break;
        }

        // Check if it's a product detail page
        if (preg_match('/^products\/(\d+)$/', $path, $matches)) {
            $_GET['id'] = $matches[1];
            require __DIR__ . '/pages/product-detail.php';
            break;
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
}
