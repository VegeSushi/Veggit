<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Vegesushi\Veggit\Services\MailtrapMailer;

$projectRoot = __DIR__ . '/../../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();
$db = $dbService->getDb();

$mailer = new MailtrapMailer();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $inviteKey = $_POST['invite_key'] ?? '';

    try {
        // Check invite key
        $stmt = $db->prepare('SELECT use_count FROM invite_keys WHERE key = :key');
        $stmt->execute([':key' => $inviteKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Invalid invite key.');
        }

        $useCount = (int)$row['use_count'];
        if ($useCount <= 0) {
            throw new Exception('Invite key has no remaining uses.');
        }

        // Proceed with registration
        $userId = $auth->register($email, $password, $username, function($selector, $token) use ($mailer, $email, $username) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . '://' . $host;

            $link = sprintf('%s/verify.php?selector=%s&token=%s', $baseUrl, $selector, $token);

            $subject = 'Verify your Veggit account';
            $body = <<<HTML
Hi {$username},<br><br>
Please verify your Veggit account by clicking the link below:<br>
<a href="{$link}">Verify Account</a>
HTML;

            if (!$mailer->send($email, $username, $subject, $body)) {
                error_log("Failed to send Mailtrap email to {$email}");
            }
        });

        // Decrement use count
        $stmt = $db->prepare('UPDATE invite_keys SET use_count = use_count - 1 WHERE key = :key');
        $stmt->execute([':key' => $inviteKey]);

        $message = 'Registration successful! Please check your email to verify your account.';
    }
    catch (\Delight\Auth\InvalidEmailException $e) { $message = 'Invalid email address'; }
    catch (\Delight\Auth\InvalidPasswordException $e) { $message = 'Invalid password'; }
    catch (\Delight\Auth\UserAlreadyExistsException $e) { $message = 'User already exists'; }
    catch (\Delight\Auth\TooManyRequestsException $e) { $message = 'Too many requests'; }
    catch (Exception $e) { $message = $e->getMessage(); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Veggit</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<h2>Register</h2>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <label>Invite Key: <input type="text" name="invite_key" required></label><br>
    <button type="submit">Register</button>
</form>

</body>
</html>
