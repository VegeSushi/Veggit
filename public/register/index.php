<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;
use Vegesushi\Veggit\Services\MailtrapMailer;

// Load database and auth
$projectRoot = __DIR__ . '/../../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();

// Load Mailtrap mailer
$mailer = new MailtrapMailer();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $userId = $auth->register($email, $password, $username, function($selector, $token) use ($mailer, $email, $username) {
            // Detect current protocol and host
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . '://' . $host;

            // Build verification link
            $link = sprintf(
                '%s/verify.php?selector=%s&token=%s',
                $baseUrl,
                $selector,
                $token
            );

            // Prepare email
            $subject = 'Verify your Veggit account';
            $body = <<<HTML
Hi {$username},<br><br>
Please verify your Veggit account by clicking the link below:<br>
<a href="{$link}">Verify Account</a>
HTML;

            // Send email via Mailtrap
            if (!$mailer->send($email, $username, $subject, $body)) {
                error_log("Failed to send Mailtrap email to {$email}");
            }
        });

        $message = 'Registration successful! Please check your email to verify your account.';
    }
    catch (\Delight\Auth\InvalidEmailException $e) { $message = 'Invalid email address'; }
    catch (\Delight\Auth\InvalidPasswordException $e) { $message = 'Invalid password'; }
    catch (\Delight\Auth\UserAlreadyExistsException $e) { $message = 'User already exists'; }
    catch (\Delight\Auth\TooManyRequestsException $e) { $message = 'Too many requests'; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Veggit</title>
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
    <button type="submit">Register</button>
</form>

</body>
</html>
