<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Dotenv\Dotenv;

// Load .env from project root
$projectRoot = realpath(__DIR__ . '/../../');
$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->load();

// Init DB + auth
$dbService = new DbService($projectRoot . '/');
$auth = $dbService->getAuth();

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login");
    exit;
}

$userId = $auth->getUserId();

// --- DB PATH (NO DEFAULT VALUE) ---
$dbPath = $_ENV['DB_PATH'] ?? null;

if ($dbPath === null || trim($dbPath) === '') {
    die("ERROR: DB_PATH is not set in your .env file.");
}

// Connect
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle form submission
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $pic = trim($_POST['profile_picture_url'] ?? '');

    $stmt = $pdo->prepare("
        INSERT INTO user_info (user_id, bio, profile_picture_url)
        VALUES (?, ?, ?)
        ON CONFLICT(user_id) DO UPDATE SET
            bio = excluded.bio,
            profile_picture_url = excluded.profile_picture_url
    ");
    $stmt->execute([$userId, $bio, $pic]);

    $message = "Profile updated!";
}

// Fetch current info
$stmt = $pdo->prepare("SELECT bio, profile_picture_url FROM user_info WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$currentBio = $profile['bio'] ?? '';
$currentPic = $profile['profile_picture_url'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customize Profile</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header>
    <a href="/"><button>Home</button></a>
</header>

<main>
    <h2>Customize Your Profile</h2>

    <?php if ($message): ?>
        <div>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Bio (uses BBCode)</label><br>
        <textarea name="bio" rows="25" style="width:100%;"><?= htmlspecialchars($currentBio) ?></textarea>
        <br><br>

        <label>Profile Picture URL</label><br>
        <input type="text" name="profile_picture_url" style="width:100%;"
               value="<?= htmlspecialchars($currentPic) ?>">
        <br><br>

        <button type="submit">Save Changes</button>
    </form>

</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
