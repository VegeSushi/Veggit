<?php
require __DIR__ . '/../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;

$projectRoot = __DIR__ . '/../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();

$message = '';

$selector = $_GET['selector'] ?? '';
$token    = $_GET['token'] ?? '';

if (!$selector || !$token) {
    $message = 'Invalid verification link.';
} else {
    try {
        $auth->confirmEmail($selector, $token);
        $message = 'Email verified successfully! You can now log in.';
    }
    catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        $message = 'Invalid token or selector. Verification failed.';
    }
    catch (\Delight\Auth\TokenExpiredException $e) {
        $message = 'Token has expired. Please request a new verification email.';
    }
    catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $message = 'User is already verified.';
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        $message = 'Too many requests. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Account - Veggit</title>
</head>
<body>
<h2>Account Verification</h2>

<p><?= htmlspecialchars($message) ?></p>

<a href="/index.php">Back to homepage</a>
</body>
</html>
