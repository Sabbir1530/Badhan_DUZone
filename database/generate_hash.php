<?php
/**
 * Utility script to generate password hashes for seeding the database.
 * Run this once from CLI: php generate_hash.php
 */

echo "admin123 => " . password_hash('admin123', PASSWORD_DEFAULT) . PHP_EOL;
echo "member123 => " . password_hash('member123', PASSWORD_DEFAULT) . PHP_EOL;
