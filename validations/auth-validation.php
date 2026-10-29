<?php
require_once __DIR__ . "/input-helper.php";
require_once __DIR__ . "/../repositories/user-repository.php";

function getRegisterRequest($request)
{
    return [
        'name' => cleanInput($request['name'] ?? ''),
        'email' => cleanInput($request['email'] ?? ''),
        'password' => rawInput($request['password'] ?? ''),
        'password_confirmation' => rawInput($request['password_confirmation'] ?? ''),
    ];
}

function validateRegister($request)
{
    $errors = [];

    // Validate name: required, max 150 character
    if ($request['name'] === '') {
        $errors['name'] = "Nama lengkap wajib diisi";
    } elseif (mb_strlen($request['name']) > 150) {
        $errors['name'] = "Nama lengkap tidak boleh melebihi dari 150 karakter";
    }

    // Validate email: required, max 150 character, valid format, isTaken?
    if ($request['email'] === '') {
        $errors['email'] = "Email wajib diisi";
    } elseif (mb_strlen($request['email']) > 150) {
        $errors['email'] = "Email tidak boleh melebihi dari 150 karakter";
    } elseif (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email yang dikirimkan tidak valid";
    } elseif (isEmailTaken($request['email'])) {
        $errors['email'] = "Email sudah didaftarkan sebelumnya";
    }

    // Validate password: required, min 8 character, max 72 byte (batas bcrypt)
    if ($request['password'] === '') {
        $errors['password'] = "Kata sandi wajib diisi";
    } elseif (mb_strlen($request['password']) < 8) {
        $errors['password'] = "Kata sandi tidak boleh kurang dari 8 karakter";
    } elseif (strlen($request['password']) > 72) {
        $errors['password'] = "Kata sandi terlalu panjang (maksimal 72 karakter)";
    }

    // Validate password confirmation: required, must match password
    if ($request['password_confirmation'] === '') {
        $errors['password_confirmation'] = "Konfirmasi kata sandi wajib diisi";
    } elseif ($request['password'] !== $request['password_confirmation']) {
        $errors['password_confirmation'] = "Kata sandi yang dimasukan tidak cocok";
    }

    return $errors;
}

function getLoginRequest($request)
{
    return [
        'email' => trim(rawInput($request['email'] ?? '')),
        'password' => rawInput($request['password'] ?? ''),
    ];
}

function validateLogin($request)
{
    $errors = [];

    // Validate email: required, valid format
    if ($request['email'] === '') {
        $errors['email'] = "Email wajib diisi";
    } elseif (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email yang dikirimkan tidak valid";
    }

    // Validate password: required
    if ($request['password'] === '') {
        $errors['password'] = "Kata sandi wajib diisi";
    }

    return $errors;
}

?>
