<?php
require __DIR__ . '/../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;

// Load environment variables from parent of public
$envPath = realpath(__DIR__ . '/../');
$dotenv = Dotenv::createImmutable($envPath);
$dotenv->load();

// Load database and auth
$projectRoot = __DIR__ . '/../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();

// Default profile picture
$masterUrl = '/'; 
$defaultPic = $masterUrl . 'images/carrot.png';

$loggedIn = $auth->isLoggedIn();
$username = '';
$profilePic = $defaultPic;

// Get DB path from .env
$dbPath = $_ENV['DB_PATH'];

if (file_exists($dbPath)) {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($loggedIn) {
        $userId = $auth->getUserId();
        $username = $auth->getUsername();

        $stmt = $pdo->prepare('SELECT profile_picture_url FROM user_info WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($profile['profile_picture_url'])) {
            $profilePic = $profile['profile_picture_url'];
        }
    }

    // Fetch first 5 posts with author, category, and date
    $stmt = $pdo->query("
        SELECT p.id, p.title, p.short_description, p.date_published,
               u.username AS author_name, c.name AS category_name
        FROM user_posts p
        JOIN users u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        ORDER BY p.date_published DESC
        LIMIT 5
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $posts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Veggit Homepage</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header>
    <?php if ($loggedIn): ?>
        <img src="<?= htmlspecialchars($profilePic) ?>" width="64" height="64" alt="Profile Picture">
        <span><?= htmlspecialchars($username) ?></span>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="/logout.php"><button>Logout</button></a>
        <a href="/customize"><button>Customize profile</button></a>
    <?php else: ?>
        <img src="<?= htmlspecialchars($defaultPic) ?>" width="64" height="64" alt="Profile Picture">
        <a href="/register"><button>Register</button></a>
        <a href="/login"><button>Login</button></a>
    <?php endif; ?>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="/browse/posts"><button>Browse posts</button></a>
    <a href="/browse/users"><button>Browse users</button></a>
    <?php if ($loggedIn): ?>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="/create"><button>Post</button></a>
    <?php endif; ?>
</header>

<main>
    <h2>Latest Posts</h2>

    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card" onclick="window.location.href='/post?id=<?= $post['id'] ?>'">
                <h3><?= htmlspecialchars($post['title']) ?></h3>

                <div class="meta">
                    Author: <?= htmlspecialchars($post['author_name']) ?>

                    <?php if ($post['category_name']): ?>
                        | Category: <?= htmlspecialchars($post['category_name']) ?>
                    <?php endif; ?>

                    | Published: <?= date("Y-m-d", $post['date_published']) ?>
                </div>

                <?php if (!empty($post['short_description'])): ?>
                    <div class="description"><?= htmlspecialchars($post['short_description']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No posts available.</p>
    <?php endif; ?>
</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
