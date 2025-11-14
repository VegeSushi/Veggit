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
if ($dbPath === null || trim($dbPath) === '') {
    die("ERROR: DB_PATH is not set in your .env file.");
}

// Connect to SQLite
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get user id from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid user id.");

// Fetch user + profile
$stmt = $pdo->prepare("
    SELECT u.username, ui.bio, ui.profile_picture_url
    FROM users u
    LEFT JOIN user_info ui ON ui.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) die("User not found.");

$username = $profile['username'];
$bioRaw    = $profile['bio'] ?? '';
$pic       = $profile['profile_picture_url'] ?? '/images/carrot.png';
if (empty($pic)) $pic = '/images/carrot.png';

// --- Decoda Setup ---
$decoda = new Decoda($bioRaw);
$decoda->defaults(); // Load default filters and hooks

// Remove potentially dangerous tags
$decoda->removeFilter('Video');  // Remove video embeds
$decoda->removeFilter('Audio');  // Remove audio embeds

// Disable unsafe attributes that could be used for XSS
$decoda->setXhtml(true);         // Use XHTML-compliant output
$decoda->setStrict(true);        // Enable strict mode

// Parse BBCode to HTML
$bioHtml = $decoda->parse();

// Additional sanitization to strip iframes and scripts
$bioHtml = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $bioHtml);
$bioHtml = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $bioHtml);
$bioHtml = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $bioHtml); // Remove inline event handlers

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile of <?= htmlspecialchars($username) ?></title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .bio img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .bio code {
            background-color: #2a2a2a;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
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

</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>