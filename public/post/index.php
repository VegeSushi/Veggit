<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;
use Decoda\Decoda;

// Load environment
$projectRoot = realpath(__DIR__ . '/../../');
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Init DB + auth
$dbService = new DbService($projectRoot . '/');
$auth = $dbService->getAuth();

// Require DB_PATH
$dbPath = $_ENV['DB_PATH'] ?? null;
if (!$dbPath || !file_exists($dbPath)) {
    die("ERROR: DB_PATH is not set or database file is missing.");
}

// Connect to SQLite
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get post id from query string
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($postId <= 0) die("Invalid post id.");

// Fetch post, author, category
$stmt = $pdo->prepare("
    SELECT p.title, p.short_description, p.content, u.id AS author_id, u.username AS author_name, c.name AS category_name
    FROM user_posts p
    JOIN users u ON u.id = p.author_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) die("Post not found.");

// --- Decoda Setup ---
$contentRaw = $post['content'] ?? '';
$decoda = new Decoda($contentRaw);
$decoda->defaults();
$decoda->setXhtml(true);
$decoda->setStrict(true);
$postHtml = $decoda->parse();

// Additional sanitization
$postHtml = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $postHtml);
$postHtml = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $postHtml);
$postHtml = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $postHtml);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="post-header">
    <a href="/"><button>Home</button></a>
</header>

<main>
    <h2><?= htmlspecialchars($post['title']) ?></h2>

    <div class="post-meta">
        Author: <a href="/profile?id=<?= $post['author_id'] ?>"><?= htmlspecialchars($post['author_name']) ?></a>
        <?php if ($post['category_name']): ?>
            | Category: <?= htmlspecialchars($post['category_name']) ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($post['short_description'])): ?>
        <div class="post-short-desc"><?= htmlspecialchars($post['short_description']) ?></div>
    <?php endif; ?>

    <section class="post-content">
        <?= $postHtml ?>
    </section>
</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
