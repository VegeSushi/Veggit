<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Decoda\Decoda;

// Initialize DbService (handles .env, DB, Auth)
$projectRoot = realpath(__DIR__ . '/../../');
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();
$pdo = $dbService->getDb();

// Get user id from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid user id.");

// Fetch user profile
$stmt = $pdo->prepare("
    SELECT u.username, ui.bio, ui.profile_picture_url
    FROM users u
    LEFT JOIN user_info ui ON ui.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) die("User not found.");

// Fetch user's posts
$postStmt = $pdo->prepare("
    SELECT p.id, p.title, p.short_description, p.date_published, c.name AS category_name
    FROM user_posts p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.author_id = ?
    ORDER BY p.date_published DESC
");
$postStmt->execute([$id]);
$userPosts = $postStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare profile data
$username = $profile['username'];
$bioRaw    = $profile['bio'] ?? '';
$pic       = $profile['profile_picture_url'] ?: '/images/carrot.png';

// --- Decoda Setup ---
$decoda = new Decoda($bioRaw);
$decoda->defaults();
$decoda->setXhtml(true);
$decoda->setStrict(true);
$bioHtml = $decoda->parse();

// Additional sanitization to remove unsafe elements
$bioHtml = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $bioHtml);
$bioHtml = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $bioHtml);
$bioHtml = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $bioHtml);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile of <?= htmlspecialchars($username) ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header>
    <a href="/"><button>Home</button></a>
</header>

<main>
    <h2>Profile: <?= htmlspecialchars($username) ?></h2>

    <div style="margin-bottom:20px;">
        <img src="<?= htmlspecialchars($pic) ?>" width="128" height="128" alt="Profile Picture"
             style="border:1px solid #555; border-radius:4px;">
    </div>

    <section>
        <h3>Bio</h3>
        <div class="bio" style="background-color:#1f1f1f;padding:10px;border:1px solid #333;border-radius:4px;">
            <?= $bioHtml ?>
        </div>
    </section>
        
    <section style="margin-top:40px;">
        <h3>Posts by <?= htmlspecialchars($username) ?></h3>

        <?php if (empty($userPosts)): ?>
            <p>This user has not posted anything yet.</p>
        <?php else: ?>
            <?php foreach ($userPosts as $post): ?>
                <div class="post-card">
                    <h4>
                        <a href="/post?id=<?= $post['id'] ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h4>

                    <div class="post-meta">
                        <?= date("Y-m-d", $post['date_published']) ?>
                        <?php if ($post['category_name']): ?>
                            • <?= htmlspecialchars($post['category_name']) ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($post['short_description'])): ?>
                        <div class="post-short-desc">
                            <?= htmlspecialchars($post['short_description']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
