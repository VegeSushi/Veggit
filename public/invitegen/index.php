<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;

$projectRoot = __DIR__ . '/../../';
$dbService = new DbService($projectRoot);
$db = $dbService->getDb();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $masterPassword = $_POST['master_password'] ?? '';
    $expectedMasterPassword = $_ENV['MASTER_PASSWORD'] ?? '';

    if ($expectedMasterPassword === '') {
        die('MASTER_PASSWORD not set in .env');
    }

    if ($masterPassword !== $expectedMasterPassword) {
        die('Invalid master password');
    }

    $numKeys = intval($_POST['num_keys'] ?? 1);
    $useCount = intval($_POST['use_count'] ?? 1);

    $generated = [];
    $stmt = $db->prepare('INSERT INTO invite_keys (key, use_count) VALUES (:key, :use_count)');

    for ($i = 0; $i < $numKeys; $i++) {
        $key = bin2hex(random_bytes(6)); // 12-character hex
        try {
            $stmt->execute([
                ':key' => $key,
                ':use_count' => $useCount
            ]);
            $generated[] = $key;
        } catch (PDOException $e) {
            $i--; // retry if duplicate
        }
    }

    echo "<h3>Generated Invite Keys:</h3><ul>";
    foreach ($generated as $k) {
        echo "<li>" . htmlspecialchars($k) . " (uses: $useCount)</li>";
    }
    echo "</ul>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generate Invite Keys</title>
</head>
<body>
<h2>Generate Invite Keys</h2>
<form method="post">
    <label>Master Password: <input type="password" name="master_password" required></label><br>
    <label>Number of Keys: <input type="number" name="num_keys" value="1" min="1" required></label><br>
    <label>Use Count per Key: <input type="number" name="use_count" value="1" min="1" required></label><br>
    <button type="submit">Generate Keys</button>
</form>
</body>
</html>
