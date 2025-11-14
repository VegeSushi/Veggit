<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;

// Load environment
$projectRoot = realpath(__DIR__ . '/../../');
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Init DB + auth
$dbService = new DbService($projectRoot . '/');
$auth = $dbService->getAuth();

// Require login
if (!$auth->isLoggedIn()) {
    header('Location: /login');
    exit;
}

$userId = $auth->getUserId();

// Connect to SQLite
$dbPath = $_ENV['DB_PATH'] ?? null;
if (!$dbPath || !file_exists($dbPath)) die("Database not found.");

$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch categories
$categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $categoryId = isset($_POST['category']) && is_numeric($_POST['category']) ? (int)$_POST['category'] : null;
    $content = trim($_POST['content'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($content === '') $errors[] = "Content is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO user_posts (title, short_description, category_id, content, date_published, author_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $shortDescription ?: null,
            $categoryId ?: null,
            $content,
            time(),
            $userId
        ]);
        $success = true;
        $title = $shortDescription = $content = '';
        $categoryId = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create a Post</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header>
    <a href="/"><button>Home</button></a>
</header>

<main>
    <div class="form-container">
        <h2>Create a New Post</h2>

        <?php if ($success): ?>
            <div class="success">Post created successfully!</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
                <div class="error"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="post">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" maxlength="200" value="<?= htmlspecialchars($title ?? '') ?>" required>

            <label for="short_description">Short Description</label>
            <input type="text" name="short_description" id="short_description" maxlength="500" value="<?= htmlspecialchars($shortDescription ?? '') ?>">

            <label for="category">Category</label>
            <select name="category" id="category">
                <option value="">-- Select a category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($categoryId) && $categoryId == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="content">Content (BBCode allowed)</label>
            <textarea name="content" id="content" rows="10"><?= htmlspecialchars($content ?? '') ?></textarea>

            <button type="submit">Create Post</button>
        </form>
    </div>
</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
