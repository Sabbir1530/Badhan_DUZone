<?php
/**
 * Database Migration Script
 * Run once to update the schema for v1.1.0 changes:
 * - quantity: INT -> VARCHAR(100) (supports text like "2 bags", "1 bag + 1 platelet")
 * - attendant_blood_group: ENUM NOT NULL -> VARCHAR(20) DEFAULT NULL (optional)
 */

require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Connected to database.\n";

    // 1. Change quantity column from INT to VARCHAR(100)
    $pdo->exec("ALTER TABLE `requisitions` MODIFY COLUMN `quantity` VARCHAR(100) NOT NULL DEFAULT '1'");
    echo "[OK] quantity column changed to VARCHAR(100).\n";

    // 2. Change attendant_blood_group from ENUM NOT NULL to VARCHAR(20) DEFAULT NULL
    $pdo->exec("ALTER TABLE `requisitions` MODIFY COLUMN `attendant_blood_group` VARCHAR(20) DEFAULT NULL");
    echo "[OK] attendant_blood_group column changed to VARCHAR(20) DEFAULT NULL.\n";

    echo "\nAll migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
