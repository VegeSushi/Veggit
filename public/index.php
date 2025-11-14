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
$dbPath = $_ENV['DB_PATH'] ?? ($projectRoot . 'veggit.sqlite');

if ($loggedIn) {
    $userId = $auth->getUserId();
    $username = $auth->getUsername();

    if (file_exists($dbPath)) {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT profile_picture_url FROM user_info WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($profile['profile_picture_url'])) {
            $profilePic = $profile['profile_picture_url'];
        }
    }
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
        <a href="/logout"><button>Logout</button></a>
    <?php else: ?>
        <img src="<?= htmlspecialchars($defaultPic) ?>" width="64" height="64" alt="Profile Picture">
        <a href="/register"><button>Register</button></a>
        <a href="/login"><button>Login</button></a>
    <?php endif; ?>
</header>

<main>
    
</main>
</body>
</html>
