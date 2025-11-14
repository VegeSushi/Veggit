<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;

// Load .env from project root
$projectRoot = realpath(__DIR__ . '/../../..');
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Init DB + auth
$dbService = new DbService($projectRoot . '/');
$auth = $dbService->getAuth();

// Public path defaults
$defaultPic = '/images/carrot.png';

// DB file path
$dbPath = $_ENV['DB_PATH'] ?? ($projectRoot . '/database/veggit.sqlite');

// Connect to DB
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch users + profile pictures
$query = "
    SELECT 
        u.id,
        u.username,
        COALESCE(ui.profile_picture_url, '') AS profile_picture_url
    FROM users u
    LEFT JOIN user_info ui ON ui.user_id = u.id
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

                    <?php
                        $pic = $defaultPic;
                        if (!empty($u['profile_picture_url'])) {
                            $pic = $u['profile_picture_url'];
                        }
                    ?>

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
