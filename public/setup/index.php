<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$projectRoot = __DIR__ . '/../../';
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Check master password
$inputPassword = $_POST['master_password'] ?? null;

if (!isset($_ENV['MASTER_PASSWORD']) || $_ENV['MASTER_PASSWORD'] === '') {
    die('MASTER_PASSWORD not set in .env');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($inputPassword !== $_ENV['MASTER_PASSWORD']) {
        die('Invalid master password');
    }

    // Path to SQLite database
    if (!isset($_ENV['DB_PATH']) || $_ENV['DB_PATH'] === '') {
        die('DB_PATH not set in .env');
    }
    $dbPath = $_ENV['DB_PATH'];

    // Make sure directory exists
    $dir = dirname($dbPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        die("Failed to create directory for database: {$dir}");
    }

    // Connect to SQLite
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (\PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }

    // Read and execute schema.sql
    $sqlFile = $projectRoot . 'schema.sql';
    if (!file_exists($sqlFile)) {
        die('schema.sql file not found');
    }

    $sql = file_get_contents($sqlFile);
    try {
        $pdo->exec($sql);
        echo "Database schema executed successfully!";
    } catch (\PDOException $e) {
        die('Failed to execute schema: ' . $e->getMessage());
    }

    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup Veggit Database</title>
</head>
<body>
<h2>Setup Veggit Database</h2>
<form method="post">
    <label>Master Password: <input type="password" name="master_password" required></label><br>
    <button type="submit">Run Schema</button>
</form>
</body>
</html>
