<?php
require_once __DIR__ . "/../../middleware/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../validations/auth-validation.php";
require_once __DIR__ . "/../../repositories/user-repository.php";

requireGuest();

if (isset($_POST['login']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $request = getLoginRequest($_POST);
        $errors = validateLogin($request);

        if (count($errors) === 0) {
            $user = getUserByEmail($request['email']);

            // Gagal jika email tidak terdaftar ATAU kata sandi tidak cocok.
            if (!$user || !password_verify($request['password'], $user['password'])) {
                $errors['login'] = "Email atau Kata Sandi salah";
            }
        }

        if (count($errors) > 0) {
            $_SESSION['errors'] = $errors;

            // Kata sandi tidak dikembalikan ke form, email di-escape karena akan dicetak ulang.
            $_SESSION['old'] = ['email' => htmlspecialchars($request['email'])];

            header('Location: ../../pages/auth/login.php');
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        header('Location: ../../pages/books/index.php');
        exit;
    } catch (PDOException $e) {
        throw $e;
    }
}

?>
