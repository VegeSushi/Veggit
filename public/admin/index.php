<?php
session_start();

require __DIR__ . '/../../vendor/autoload.php';
use Vegesushi\Veggit\Services\DbService;
use Delight\Auth\Auth;

// ------------------------------
// CONFIG / DB
// ------------------------------
$projectRoot = __DIR__ . '/../../';
$dbService = new DbService($projectRoot);
$db = $dbService->getDb();
$auth = $dbService->getAuth();

$MASTER_PASSWORD = $_ENV['MASTER_PASSWORD'] ?? '';
if ($MASTER_PASSWORD === '') die('MASTER_PASSWORD not set in .env');

// ------------------------------
// PASSWORD CHECK
// ------------------------------
$passwordEntered = $_POST['master_password'] ?? null;
if ($passwordEntered !== null) {
    $_SESSION['admin_authenticated'] = hash_equals($MASTER_PASSWORD, (string)$passwordEntered);
}

if (empty($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    $errorMessage = ($passwordEntered !== null) ? 'Incorrect password. Access forbidden.' : '';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
    </head>
    <body>
        <h1>Enter Admin Password</h1>
        <?php if ($errorMessage): ?>
            <p style="color:red;"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>
        <form method="post">
            <label>Password: <input type="password" name="master_password" required></label>
            <button type="submit">Enter</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// ------------------------------
// ADMIN PANEL LOGIC
// ------------------------------
$message = "";

// --- 0. Run schema.sql / schema2.sql ---
if (isset($_POST['run_schema'])) {
    $schemaFiles = ['schema.sql', 'schema2.sql'];
    foreach ($schemaFiles as $schemaFile) {
        $path = $projectRoot . $schemaFile;
        if (!file_exists($path)) {
            $message .= "Missing $schemaFile<br>";
            continue;
        }
        try {
            $sql = file_get_contents($path);
            $db->exec($sql);
            $message .= "Executed $schemaFile successfully<br>";
        } catch (PDOException $e) {
            $message .= "Error executing $schemaFile: " . $e->getMessage() . "<br>";
        }
    }
}

// --- 1. Generate Invite Keys ---
if (isset($_POST['generate_keys'])) {
    $numKeys = max(1, (int)($_POST['num_keys'] ?? 1));
    $useCount = max(1, (int)($_POST['use_count'] ?? 1));
    $stmt = $db->prepare('INSERT INTO invite_keys (key, use_count) VALUES (:key, :use_count)');
    $generated = [];
    for ($i = 0; $i < $numKeys; $i++) {
        $key = bin2hex(random_bytes(6));
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
    $message .= "<strong>Generated Keys:</strong><br>" . implode("<br>", $generated) . "<br>";
}

// --- 2. Add Category ---
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['category_description'] ?? '');
    if ($name === '') {
        $message .= "Category name cannot be empty.<br>";
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT
            );
        ");
        $cols = $db->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');
        if (!in_array('description', $colNames)) {
            $db->exec("ALTER TABLE categories ADD COLUMN description TEXT");
        }
        $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        try {
            $stmt->execute([$name, $desc]);
            $message .= "Category added: " . htmlspecialchars($name) . "<br>";
        } catch (PDOException $e) {
            $message .= str_contains($e->getMessage(), "UNIQUE") ? "Category already exists.<br>" : "Error: " . $e->getMessage() . "<br>";
        }
    }
}

// --- 3. Delete Category ---
if (isset($_POST['delete_category'])) {
    $name = trim($_POST['delete_category_name'] ?? '');
    if ($name === '') {
        $message .= "Category name cannot be empty.<br>";
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        $message .= $stmt->rowCount() > 0 ? "Deleted category: " . htmlspecialchars($name) . "<br>" : "Category not found: " . htmlspecialchars($name) . "<br>";
    }
}

// --- 4. Delete Post ---
if (isset($_POST['delete_post'])) {
    $id = (int)($_POST['post_id'] ?? 0);
    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM user_posts WHERE id = ?");
        $stmt->execute([$id]);
        $message .= "Deleted post ID: $id<br>";
    } else {
        $message .= "Invalid post ID.<br>";
    }
}

// --- 5. Delete User ---
if (isset($_POST['delete_user'])) {
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id > 0) {
        try {
            $auth->admin()->deleteUserById($id);
            $message .= "Deleted user ID: $id<br>";
        } catch (Exception $e) {
            $message .= "Error deleting user: " . $e->getMessage() . "<br>";
        }
    } else {
        $message .= "Invalid user ID.<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
</head>
<body>
<h1>Veggit Admin Panel</h1>

<?php if ($message): ?>
    <p><strong><?= $message ?></strong></p>
<?php endif; ?>

<!-- RUN SCHEMA -->
<h2>Run Schema Files</h2>
<form method="post">
    <input type="hidden" name="run_schema" value="1">
    <button type="submit">Run schema.sql and schema2.sql</button>
</form>

<!-- INVITE KEYS -->
<h2>Generate Invite Keys</h2>
<form method="post">
    <input type="hidden" name="generate_keys" value="1">
    <label>Number of Keys: <input type="number" name="num_keys" value="1" min="1"></label><br>
    <label>Use Count: <input type="number" name="use_count" value="1" min="1"></label><br>
    <button type="submit">Generate</button>
</form>

<!-- ADD CATEGORY -->
<h2>Add Category</h2>
<form method="post">
    <input type="hidden" name="add_category" value="1">
    <label>Category Name: <input type="text" name="category_name" required></label><br>
    <label>Description: <textarea name="category_description"></textarea></label><br>
    <button type="submit">Add</button>
</form>

<!-- DELETE CATEGORY -->
<h2>Delete Category by Name</h2>
<form method="post">
    <input type="hidden" name="delete_category" value="1">
    <label>Category Name: <input type="text" name="delete_category_name" required></label><br>
    <button type="submit">Delete</button>
</form>

<!-- DELETE POST -->
<h2>Delete Post by ID</h2>
<form method="post">
    <input type="hidden" name="delete_post" value="1">
    <label>Post ID: <input type="number" name="post_id" required></label><br>
    <button type="submit">Delete</button>
</form>

<!-- DELETE USER -->
<h2>Delete User by ID</h2>
<form method="post">
    <input type="hidden" name="delete_user" value="1">
    <label>User ID: <input type="number" name="user_id" required></label><br>
    <button type="submit">Delete</button>
</form>

</body>
</html>
