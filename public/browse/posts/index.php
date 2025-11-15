<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;

// Load environment
$projectRoot = realpath(__DIR__ . '/../../../');
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Init DB
$dbService = new DbService($projectRoot . '/');

// DB path
$dbPath = $_ENV['DB_PATH'] ?? null;
if (!$dbPath || !file_exists($dbPath)) die("Database not found.");

$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch categories
$categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Query params
$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build base query
$sqlBase = "
    FROM user_posts p
    JOIN users u ON u.id = p.author_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE 1
";

$params = [];

if ($categoryId) {
    $sqlBase .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($search !== '') {
    $sqlBase .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total posts
$countStmt = $pdo->prepare("SELECT COUNT(*) $sqlBase");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalPosts / $perPage));

// Fetch posts for current page
$sql = "
    SELECT p.id, p.title, p.short_description, p.date_published,
           u.id AS author_id, u.username AS author_name,
           c.name AS category_name
    $sqlBase
    ORDER BY p.date_published DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Posts</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="post-header">
    <a href="/"><button>Home</button></a>
</header>

<main>

    <form class="filter-bar" method="get">
        <input type="text" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <?php if (empty($posts)): ?>
        <p style="text-align:center;">No posts found.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <h3>
                    <a href="/post?id=<?= $post['id'] ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h3>

                <div class="post-meta">
                    Author:
                    <a href="/profile?id=<?= $post['author_id'] ?>">
                        <?= htmlspecialchars($post['author_name']) ?>
                    </a>

                    <?php if ($post['category_name']): ?>
                        | Category: <?= htmlspecialchars($post['category_name']) ?>
                    <?php endif; ?>

                    | Published: <?= date("Y-m-d", $post['date_published']) ?>
                </div>

                <?php if (!empty($post['short_description'])): ?>
                    <div class="post-short-desc"><?= htmlspecialchars($post['short_description']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
