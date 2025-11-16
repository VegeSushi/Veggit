<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Decoda\Decoda;

// Initialize DbService (handles .env, DB, Auth)
$projectRoot = realpath(__DIR__ . '/../../');
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();
$pdo = $dbService->getDb();

$masterUrl = '/';
$defaultPic = $masterUrl . 'images/carrot.png';

// Get post id from query string
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($postId <= 0) {
    die("Invalid post id.");
}

// Fetch post, author, category
$stmt = $pdo->prepare("
    SELECT p.title, p.short_description, p.content,
           u.id AS author_id, u.username AS author_name,
           ui.profile_picture_url AS author_pfp,
           c.name AS category_name
    FROM user_posts p
    JOIN users u ON u.id = p.author_id
    LEFT JOIN user_info ui ON ui.user_id = u.id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

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

// Fetch comments
$commentsStmt = $pdo->prepare("
    SELECT c.id, c.content, c.date_added,
           u.id AS author_id, u.username AS author_name,
           ui.profile_picture_url AS author_pfp
    FROM comments c
    JOIN users u ON u.id = c.author_id
    LEFT JOIN user_info ui ON ui.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.date_added DESC
");
$commentsStmt->execute([$postId]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle comment submission
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    if ($auth->isLoggedIn()) {
        $commentContent = trim($_POST['comment_content']);
        if ($commentContent !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO comments (post_id, author_id, content, date_added)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $postId,
                $auth->getUserId(),
                $commentContent,
                time()
            ]);
            header("Location: ?id=$postId");
            exit;
        } else {
            $commentError = "Comment cannot be empty.";
        }
    } else {
        $commentError = "You must be logged in to post a comment.";
    }
}

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
        <img src="<?= htmlspecialchars(!empty($post['author_pfp']) ? $post['author_pfp'] : $defaultPic) ?>" alt="Author Picture" class="pfp">
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

    <section class="comments-section">
        <h3>Comments (<?= count($comments) ?>)</h3>

        <?php if ($auth->isLoggedIn()): ?>
            <form method="post" class="comment-form">
                <textarea name="comment_content" rows="1" placeholder="Write your comment here..." style="resize: none; width: 90%;" required></textarea>
                <button type="submit">Send Comment</button>
            </form>
            <?php if (!empty($commentError)): ?>
                <p class="comment-error"><?= htmlspecialchars($commentError) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="/login">Log in</a> to post a comment.</p>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <p>No comments yet. Be the first to comment!</p>
        <?php else: ?>
            <ul class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <li class="comment-item">
                        <div class="comment-meta">
                            <img src="<?= htmlspecialchars(!empty($comment['author_pfp']) ? $comment['author_pfp'] : $defaultPic) ?>" alt="Commenter Picture" class="comment-pfp">
                            <a href="/profile?id=<?= (int)$comment['author_id'] ?>">
                                <?= htmlspecialchars($comment['author_name']) ?>
                            </a>
                            <span class="comment-date">
                                <?= date('F j, Y H:i', (int)$comment['date_added']) ?>
                            </span>
                        </div>
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

</main>

<footer>
    <a href="/privacy"><button>Privacy Policy</button></a>
</footer>

</body>
</html>
