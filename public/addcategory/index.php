<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment
$projectRoot = __DIR__ . '/../../';
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Load env vars
$MASTER_PASSWORD = $_ENV['MASTER_PASSWORD'] ?? '';
$DB_PATH = $_ENV['DB_PATH'] ?? '';

$inputPassword = $_POST['master_password'] ?? null;
$newCategory = trim($_POST['category_name'] ?? '');
$newDescription = trim($_POST['category_description'] ?? '');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Password checks
    if ($MASTER_PASSWORD === '') {
        die('MASTER_PASSWORD not set in .env');
    }
    if ($inputPassword !== $MASTER_PASSWORD) {
        die('Invalid master password');
    }
    if ($DB_PATH === '') {
        die('DB_PATH not set in .env');
    }

    // Validate inputs
    if ($newCategory === '') {
        die('Category name cannot be empty.');
    }

    // Ensure directory exists
    $dir = dirname($DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // Connect to DB
    try {
        $pdo = new PDO("sqlite:$DB_PATH");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        die("DB error: " . $e->getMessage());
    }

    // Create categories table if missing (with description column)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT
        );
    ");

    // Ensure description column exists (for old DBs)
    $cols = $pdo->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'name');

    if (!in_array('description', $colNames)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT;");
    }

    // Insert category
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

    try {
        $stmt->execute([$newCategory, $newDescription]);
        $message = "Added category: " . htmlspecialchars($newCategory);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "UNIQUE") !== false) {
            $message = "Category already exists.";
        } else {
            $message = "Error adding category: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Category</title>
</head>
<body>
<h2>Add a New Category</h2>

<?php if ($message): ?>
    <p><strong><?= $message ?></strong></p>
<?php endif; ?>

<form method="post">
    <label>
        Master Password:
        <input type="password" name="master_password" required>
    </label>
    <br><br>

    <label>
        Category Name:
        <input type="text" name="category_name" required>
    </label>
    <br><br>

    <label>
        Description (optional):
        <textarea name="category_description" rows="4" cols="40"></textarea>
    </label>
    <br><br>

    <button type="submit">Add Category</button>
</form>

</body>
</html>
