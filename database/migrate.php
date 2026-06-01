<?php

declare(strict_types=1);

/**
 * Database Migration Runner
 *
 * Usage: php database/migrate.php [--seed]
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'hanaka_cake';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Connect without database to create it if needed
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");

    echo "Connected to database '{$database}'.\n\n";

    // Run migrations
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        echo "Running migration: {$filename}... ";
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo "OK\n";
    }

    echo "\nAll migrations completed.\n";

    // Run seeds if --seed flag is passed
    if (in_array('--seed', $argv)) {
        echo "\nRunning seeds...\n";

        $seedsDir = __DIR__ . '/seeds';
        $seedFiles = glob($seedsDir . '/*.sql');
        sort($seedFiles);

        foreach ($seedFiles as $file) {
            $filename = basename($file);
            echo "Running seed: {$filename}... ";
            $sql = file_get_contents($file);
            $pdo->exec($sql);
            echo "OK\n";
        }

        echo "\nAll seeds completed.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
