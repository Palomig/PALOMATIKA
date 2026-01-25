<?php
/**
 * SmartCart - Database Class
 * SQLite database wrapper
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Get database connection (singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Connect to database and initialize if needed
     */
    private static function connect(): void {
        $dbPath = DB_PATH;
        $dbDir = dirname($dbPath);

        // Create data directory if not exists
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $isNewDb = !file_exists($dbPath);

        try {
            self::$instance = new PDO(
                'sqlite:' . $dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Enable foreign keys
            self::$instance->exec('PRAGMA foreign_keys = ON');

            // Initialize database if new
            if ($isNewDb) {
                self::initializeDatabase();
            }

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize database schema and seed data
     */
    private static function initializeDatabase(): void {
        $db = self::$instance;

        // Create tables
        $db->exec("
            -- Stores
            CREATE TABLE IF NOT EXISTS stores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                delivery_time_min INTEGER DEFAULT 30,
                delivery_time_max INTEGER DEFAULT 60,
                min_order INTEGER DEFAULT 0,
                logo_url TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- Categories
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                emoji TEXT,
                url_path TEXT,
                sort_order INTEGER DEFAULT 0
            );

            -- Products (base templates)
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category_id INTEGER,
                search_keywords TEXT,
                default_weight REAL,
                default_unit TEXT DEFAULT 'г',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );

            -- Prices (parsed from stores)
            CREATE TABLE IF NOT EXISTS prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                store_id INTEGER NOT NULL,
                store_product_name TEXT NOT NULL,
                price REAL NOT NULL,
                original_price REAL,
                discount_percent REAL,
                weight REAL,
                unit TEXT,
                price_per_kg REAL,
                url TEXT,
                category_slug TEXT,
                is_available INTEGER DEFAULT 1,
                parsed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (store_id) REFERENCES stores(id)
            );

            -- Recipes
            CREATE TABLE IF NOT EXISTS recipes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                instructions TEXT,
                cook_time INTEGER,
                prep_time INTEGER,
                servings INTEGER DEFAULT 2,
                equipment TEXT,
                category TEXT,
                tags TEXT,
                image_svg TEXT,
                is_favorite INTEGER DEFAULT 0,
                times_cooked INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- Recipe ingredients
            CREATE TABLE IF NOT EXISTS recipe_ingredients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                recipe_id INTEGER NOT NULL,
                product_id INTEGER,
                product_name TEXT NOT NULL,
                quantity REAL NOT NULL,
                unit TEXT,
                is_optional INTEGER DEFAULT 0,
                notes TEXT,
                FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            );

            -- Shopping list
            CREATE TABLE IF NOT EXISTS shopping_list (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                product_name TEXT NOT NULL,
                search_term TEXT,
                quantity REAL DEFAULT 1,
                expected_price REAL,
                url TEXT,
                is_checked INTEGER DEFAULT 0,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            );

            -- Purchases history
            CREATE TABLE IF NOT EXISTS purchases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER NOT NULL,
                total_price REAL,
                items_count INTEGER,
                notes TEXT,
                purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (store_id) REFERENCES stores(id)
            );

            -- Purchase items
            CREATE TABLE IF NOT EXISTS purchase_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                purchase_id INTEGER NOT NULL,
                price_id INTEGER,
                product_name TEXT NOT NULL,
                price REAL NOT NULL,
                quantity INTEGER DEFAULT 1,
                FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
                FOREIGN KEY (price_id) REFERENCES prices(id)
            );

            -- Settings
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );

            -- Indexes for performance
            CREATE INDEX IF NOT EXISTS idx_prices_store ON prices(store_id);
            CREATE INDEX IF NOT EXISTS idx_prices_category ON prices(category_slug);
            CREATE INDEX IF NOT EXISTS idx_prices_parsed ON prices(parsed_at);
            CREATE INDEX IF NOT EXISTS idx_recipe_ingredients_recipe ON recipe_ingredients(recipe_id);
        ");

        // Seed initial data
        self::seedData();
    }

    /**
     * Seed initial data
     */
    private static function seedData(): void {
        $db = self::$instance;

        // Stores
        $stores = [
            ['perekrestok', 'Перекрёсток', 30, 60, 500],
            ['pyaterochka', 'Пятёрочка', 25, 45, 300],
            ['magnit', 'Магнит', 30, 60, 400],
            ['vkusvill', 'ВкусВилл', 30, 60, 600],
            ['lenta', 'Лента', 40, 90, 500],
            ['dixy', 'Дикси', 30, 60, 300],
        ];

        $stmt = $db->prepare("INSERT OR IGNORE INTO stores (slug, name, delivery_time_min, delivery_time_max, min_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($stores as $store) {
            $stmt->execute($store);
        }

        // Categories
        $categories = [
            ['meat', 'Мясо и птица', '🍗', 'myaso-i-ptitsa', 1],
            ['fish', 'Рыба и морепродукты', '🐟', 'ryba-i-moreprodukty', 2],
            ['dairy', 'Молочные продукты', '🥛', 'molochnye-produkty', 3],
            ['eggs', 'Яйца', '🥚', 'yaytsa', 4],
            ['cereals', 'Крупы и макароны', '🌾', 'krupy-i-makarony', 5],
            ['vegetables', 'Овощи и фрукты', '🥬', 'ovoschi-i-frukty', 6],
            ['bread', 'Хлеб и выпечка', '🍞', 'khleb-i-vypechka', 7],
            ['drinks', 'Напитки', '☕', 'napitki', 8],
            ['sauces', 'Соусы и специи', '🧂', 'sousy-i-spetsii', 9],
        ];

        $stmt = $db->prepare("INSERT OR IGNORE INTO categories (slug, name, emoji, url_path, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }

        // Products
        $products = [
            ['Голень куриная', 'meat', 'голень курица куриная', 900, 'г'],
            ['Грудка куриная', 'meat', 'грудка курица куриная филе', 500, 'г'],
            ['Бедро куриное', 'meat', 'бедро курица куриное', 800, 'г'],
            ['Филе индейки', 'meat', 'индейка филе', 500, 'г'],
            ['Голень индейки', 'meat', 'индейка голень', 1000, 'г'],
            ['Фарш говяжий', 'meat', 'фарш говядина говяжий', 400, 'г'],
            ['Свинина шея', 'meat', 'свинина шея', 500, 'г'],

            ['Пангасиус филе', 'fish', 'пангасиус филе рыба', 500, 'г'],
            ['Горбуша', 'fish', 'горбуша рыба', 1000, 'г'],
            ['Минтай филе', 'fish', 'минтай филе рыба', 400, 'г'],

            ['Молоко 3.2%', 'dairy', 'молоко', 930, 'мл'],
            ['Сметана 20%', 'dairy', 'сметана', 200, 'г'],
            ['Творог 5%', 'dairy', 'творог', 200, 'г'],
            ['Сыр Российский', 'dairy', 'сыр российский', 200, 'г'],
            ['Масло сливочное', 'dairy', 'масло сливочное', 180, 'г'],

            ['Яйца С1', 'eggs', 'яйца', 10, 'шт'],
            ['Яйца С0', 'eggs', 'яйца', 10, 'шт'],

            ['Рис', 'cereals', 'рис', 900, 'г'],
            ['Гречка', 'cereals', 'гречка гречневая', 900, 'г'],
            ['Овсянка', 'cereals', 'овсянка овсяные хлопья', 400, 'г'],
            ['Макароны', 'cereals', 'макароны паста', 450, 'г'],

            ['Картофель', 'vegetables', 'картофель картошка', 1000, 'г'],
            ['Морковь', 'vegetables', 'морковь', 500, 'г'],
            ['Лук репчатый', 'vegetables', 'лук репчатый', 500, 'г'],
            ['Капуста белокочанная', 'vegetables', 'капуста белокочанная', 1000, 'г'],
            ['Помидоры', 'vegetables', 'помидоры томаты', 500, 'г'],
            ['Огурцы', 'vegetables', 'огурцы', 500, 'г'],
            ['Яблоки', 'vegetables', 'яблоки', 1000, 'г'],

            ['Хлеб белый', 'bread', 'хлеб белый батон', 400, 'г'],
            ['Хлеб чёрный', 'bread', 'хлеб черный ржаной', 400, 'г'],
        ];

        $stmtCat = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmtProd = $db->prepare("INSERT OR IGNORE INTO products (name, category_id, search_keywords, default_weight, default_unit) VALUES (?, ?, ?, ?, ?)");

        foreach ($products as $prod) {
            $stmtCat->execute([$prod[1]]);
            $catId = $stmtCat->fetchColumn();
            $stmtProd->execute([$prod[0], $catId, $prod[2], $prod[3], $prod[4]]);
        }

        // Load initial recipes
        self::loadInitialRecipes();
    }

    /**
     * Load initial recipes from JSON file
     */
    private static function loadInitialRecipes(): void {
        $recipesFile = __DIR__ . '/../data/initial-recipes.json';

        if (!file_exists($recipesFile)) {
            return;
        }

        $data = json_decode(file_get_contents($recipesFile), true);

        if (empty($data['recipes'])) {
            return;
        }

        $db = self::$instance;

        foreach ($data['recipes'] as $recipe) {
            if (empty($recipe['name'])) continue;

            // Insert recipe
            $stmt = $db->prepare(
                "INSERT OR IGNORE INTO recipes (name, description, instructions, cook_time, prep_time, servings, equipment, category, tags, image_svg)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $instructions = is_array($recipe['instructions'] ?? null)
                ? json_encode($recipe['instructions'], JSON_UNESCAPED_UNICODE)
                : ($recipe['instructions'] ?? null);

            $tags = is_array($recipe['tags'] ?? null)
                ? json_encode($recipe['tags'], JSON_UNESCAPED_UNICODE)
                : ($recipe['tags'] ?? null);

            $stmt->execute([
                $recipe['name'],
                $recipe['description'] ?? null,
                $instructions,
                $recipe['cook_time'] ?? null,
                $recipe['prep_time'] ?? null,
                $recipe['servings'] ?? 2,
                $recipe['equipment'] ?? null,
                $recipe['category'] ?? null,
                $tags,
                $recipe['image_svg'] ?? null
            ]);

            $recipeId = $db->lastInsertId();

            if ($recipeId && !empty($recipe['ingredients'])) {
                foreach ($recipe['ingredients'] as $ing) {
                    if (empty($ing['name'])) continue;

                    // Try to find matching product
                    $productId = null;
                    $stmtProd = $db->prepare("SELECT id FROM products WHERE name LIKE ? OR search_keywords LIKE ?");
                    $searchTerm = '%' . $ing['name'] . '%';
                    $stmtProd->execute([$searchTerm, $searchTerm]);
                    $found = $stmtProd->fetch();
                    if ($found) {
                        $productId = $found['id'];
                    }

                    $stmtIng = $db->prepare(
                        "INSERT INTO recipe_ingredients (recipe_id, product_id, product_name, quantity, unit, is_optional)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmtIng->execute([
                        $recipeId,
                        $productId,
                        $ing['name'],
                        $ing['quantity'] ?? 1,
                        $ing['unit'] ?? null,
                        $ing['is_optional'] ?? 0
                    ]);
                }
            }
        }
    }

    /**
     * Query helper
     */
    public static function query(string $sql, array $params = []): array {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute helper (for INSERT/UPDATE/DELETE)
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): int {
        return (int) self::getInstance()->lastInsertId();
    }
}
