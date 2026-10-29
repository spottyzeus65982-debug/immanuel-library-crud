<?php
require_once __DIR__ . "/../../middleware/auth.php";

startSession();

$_SESSION = [];

// Hapus juga cookie sesi di browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

header('Location: ../../pages/auth/login.php');
exit;
?>
