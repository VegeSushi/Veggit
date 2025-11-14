<?php
require __DIR__ . '/../../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;

// Load database and auth
$projectRoot = __DIR__ . '/../../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    try {
        // Attempt login
        $auth->login($email, $password, $remember);

        // Redirect to root after successful login
        header('Location: /');
        exit;
    }
    catch (\Delight\Auth\InvalidEmailException $e) {
        $message = 'Invalid email address';
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        $message = 'Wrong password';
    }
    catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $message = 'Email not verified. Please check your inbox.';
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        $message = 'Too many login attempts. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Veggit</title>
</head>
<body>
<h2>Login</h2>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <label><input type="checkbox" name="remember"> Remember Me</label><br>
    <button type="submit">Login</button>
</form>

<p><a href="/register">Don't have an account? Register here</a></p>

</body>
</html>
