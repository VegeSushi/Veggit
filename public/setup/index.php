<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$projectRoot = __DIR__ . '/../../';
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Check master password
$inputPassword = $_POST['master_password'] ?? null;

// Load env vars
$MASTER_PASSWORD = $_ENV['MASTER_PASSWORD'] ?? '';
$DB_PATH = $_ENV['DB_PATH'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($MASTER_PASSWORD === '') {
        die('MASTER_PASSWORD not set in .env');
    }
    if ($inputPassword !== $MASTER_PASSWORD) {
        die('Invalid master password');
    }
    if ($DB_PATH === '') {
        die('DB_PATH not set in .env');
    }

    // Ensure directory exists
    $dir = dirname($DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // Connect to SQLite
    try {
        $pdo = new PDO("sqlite:$DB_PATH");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        die("DB error: " . $e->getMessage());
    }

    // --- Execute schema.sql ---
    $schema1 = $projectRoot . 'schema.sql';
    if (!file_exists($schema1)) die("Missing schema.sql");
    $pdo->exec(file_get_contents($schema1));
    echo "Executed schema.sql<br>";

    // --- Execute schema2.sql ---
    $schema2 = $projectRoot . 'schema2.sql';
    if (!file_exists($schema2)) die("Missing schema2.sql");
    $pdo->exec(file_get_contents($schema2));
    echo "Executed schema2.sql<br>";

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
