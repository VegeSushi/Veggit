<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;

// Initialize DbService (loads .env, sets up DB and Auth)
$projectRoot = realpath(__DIR__ . '/../../..');
$dbService = new DbService($projectRoot);
$pdo = $dbService->getDb();

// Default profile picture
$defaultPic = '/images/carrot.png';

// Fetch verified users with optional profile pictures
$query = "
    SELECT 
        u.id,
        u.username,
        COALESCE(ui.profile_picture_url, '') AS profile_picture_url
    FROM users u
    LEFT JOIN user_info ui ON ui.user_id = u.id
    WHERE u.verified = 1
    ORDER BY u.username COLLATE NOCASE ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Users</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header>
    <a href="/"><button>Home</button></a>
</header>

<main>
    <h2>Browse Users</h2>

    <div class="user-list">
        <?php foreach ($users as $u): ?>
            <div class="user-card">
                <a href="/profile?id=<?= htmlspecialchars($u['id']) ?>">
                    <?php $pic = !empty($u['profile_picture_url']) ? $u['profile_picture_url'] : $defaultPic; ?>
                    <img src="<?= htmlspecialchars($pic) ?>"
                         width="64" height="64"
                         alt="Profile Picture">
                    <div><?= htmlspecialchars($u['username'] ?? 'Unknown') ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
