<?php
require_once __DIR__ . "/input-helper.php";
require_once __DIR__ . "/../repositories/book-repository.php";
require_once __DIR__ . "/../repositories/category-repository.php";
require_once __DIR__ . "/../repositories/author-repository.php";

function getBookRequest($request)
{
    $authorIds = $request['author_ids'] ?? [];
    $authorIds = is_array($authorIds) ? array_filter($authorIds, 'is_string') : [];

    return [
        'id' => cleanInput($request['id'] ?? ''),
        'title' => cleanInput($request['title'] ?? ''),
        'isbn' => cleanInput($request['isbn'] ?? ''),
        'year' => cleanInput($request['year'] ?? ''),
        'stock' => cleanInput($request['stock'] ?? ''),
        'description' => cleanInput($request['description'] ?? ''),
        'category_id' => cleanInput($request['category_id'] ?? ''),
        'author_ids' => array_values(array_unique($authorIds)),
    ];
}

function validateBook($request, $ignoreBookId = 0)
{
    $errors = [];

    // Validate book id (hanya saat update): harus berupa angka dan bukunya harus ada
    if ($ignoreBookId !== 0) {
        if (!ctype_digit((string) $ignoreBookId) || !isBookExists($ignoreBookId)) {
            $errors['id'] = "Buku yang akan diperbarui tidak ditemukan";
        }
    }

    // Validate title: required and max 100 character
    if ($request['title'] === '') {
        $errors['title'] = "Judul buku wajib diisi";
    } elseif (mb_strlen($request['title']) > 100) {
        $errors['title'] = "Judul buku tidak boleh melebihi dari 100 karakter";
    }

    // Validate isbn: required, max 20 character, 10 or 13 digit, isTaken?
    $isbnDigits = str_replace(['-', ' '], '', $request['isbn']);

    if ($request['isbn'] === '') {
        $errors['isbn'] = "ISBN wajib diisi";
    } elseif (mb_strlen($request['isbn']) > 20) {
        $errors['isbn'] = "ISBN tidak boleh melebihi dari 20 karakter";
    } elseif (!preg_match('/^(\d{9}[\dX]|\d{13})$/i', $isbnDigits)) {
        $errors['isbn'] = "ISBN harus terdiri dari 10 atau 13 digit";
    } elseif (isIsbnTaken($request['isbn'], $ignoreBookId)) {
        $errors['isbn'] = "ISBN sudah terdaftar sebelumnya";
    }

    // Validate year: required, integer between 1000 and this year
    $currentYear = (int) date('Y');

    if ($request['year'] === '') {
        $errors['year'] = "Tahun terbit wajib diisi";
    } elseif (filter_var($request['year'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => $currentYear]]) === false) {
        $errors['year'] = "Tahun terbit harus berupa angka antara 1000 sampai $currentYear";
    }

    // Validate stock: required, integer between 0 and 100000
    if ($request['stock'] === '') {
        $errors['stock'] = "Stok wajib diisi";
    } elseif (filter_var($request['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]) === false) {
        $errors['stock'] = "Stok harus berupa angka bulat antara 0 sampai 100000";
    }

    // Validate category: required, isNumber, isExists
    if ($request['category_id'] === '') {
        $errors['category_id'] = "Kategori wajib dipilih";
    } elseif (!ctype_digit($request['category_id']) || !isCategoryExists($request['category_id'])) {
        $errors['category_id'] = "Kategori yang dipilih tidak valid";
    }

    // Validate description: required, max 100 character
    if ($request['description'] === '') {
        $errors['description'] = "Deskripsi buku wajib diisi";
    } elseif (mb_strlen($request['description']) > 100) {
        $errors['description'] = "Deskripsi buku tidak boleh melebihi dari 100 karakter";
    }

    // Validate authors: required, isExists
    if (count($request['author_ids']) < 1) {
        $errors['author_ids'] = "Penulis buku wajib dipilih";
    } else {
        $validAuthorIds = array_map('strval', array_column(getAuthors(), 'id'));

        foreach ($request['author_ids'] as $authorId) {
            if (!in_array($authorId, $validAuthorIds, true)) {
                $errors['author_ids'] = "Ada penulis buku yang dipilih tidak valid";
                break;
            }
        }
    }

    return $errors;
}

?>
