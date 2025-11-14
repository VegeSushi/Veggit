<?php
require __DIR__ . '/../vendor/autoload.php';

use Vegesushi\Veggit\Services\DbService;

// Load database and auth
$projectRoot = __DIR__ . '/../';
$dbService = new DbService($projectRoot);
$auth = $dbService->getAuth();

try {
    $auth->logOut(); // Log out current session
    $auth->destroySession(); // Optional: destroy session completely
} catch (\Delight\Auth\NotLoggedInException $e) {
    // User was not logged in, ignore
}

// Redirect to root
header('Location: /');
exit;
