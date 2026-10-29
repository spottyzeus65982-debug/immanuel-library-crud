<?php
require_once __DIR__ . "/../config/database.php";

// Karakter % dan _ pada pencarian dianggap teks biasa, bukan wildcard LIKE.
function escapeLike($value)
{
  return addcslashes($value, '\\%_');
}

// Menyusun kondisi WHERE untuk pencarian judul dan filter kategori.
function buildBookFilter($search, $categoryId)
{
  $where = "b.title like :search";
  $params = [
    ':search' => "%" . escapeLike($search) . "%"
  ];

  if ($categoryId !== '') {
    $where .= " and b.category_id = :category_id";
    $params[':category_id'] = $categoryId;
  }

  return [$where, $params];
}

function countBooks($search, $categoryId)
{
  global $pdo;

  [$where, $params] = buildBookFilter($search, $categoryId);

  $query = "select count(b.id) from books b
            where $where
           ";
  $stmt = $pdo->prepare($query);
  $stmt->execute($params);

  return (int) $stmt->fetchColumn();
}

function getBooks($search, $categoryId, $page, $perPage)
{
  global $pdo;

  [$where, $params] = buildBookFilter($search, $categoryId);

  $query = "select b.id, b.title, b.stock, c.name as category from books b
            left join categories c on b.category_id = c.id
            where $where
            order by b.id desc
            limit :limit offset :offset
           ";
  $stmt = $pdo->prepare($query);

  foreach ($params as $name => $value) {
    $stmt->bindValue($name, $value);
  }

  // LIMIT dan OFFSET wajib bertipe integer, jadi tidak bisa lewat execute([...]).
  $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', (int) (($page - 1) * $perPage), PDO::PARAM_INT);
  $stmt->execute();

  $books = $stmt->fetchAll();

  if (count($books) === 0) {
    return $books;
  }

  // Ambil penulis hanya untuk buku yang tampil di halaman ini.
  $bookIds = array_column($books, 'id');
  $placeholders = implode(',', array_fill(0, count($bookIds), '?'));

  $query = "select ba.book_id, a.name from book_author ba
            left join authors a on ba.author_id = a.id
            where ba.book_id in ($placeholders)
           ";
  $stmt = $pdo->prepare($query);
  $stmt->execute($bookIds);

  $bookAuthors = $stmt->fetchAll();

  $bookByAuthors = [];
  foreach ($bookAuthors as $bookAuthor) {
    $bookByAuthors[$bookAuthor['book_id']][] = $bookAuthor['name'];
  }

  foreach ($books as $index => $book) {
    $books[$index]['authors'] = $bookByAuthors[$book['id']] ?? [];
  }

  return $books;
}

function getBook($id)
{
  global $pdo;

  $query = "select id, title, isbn, year, stock, category_id, description from books where id = :id";
  $stmt = $pdo->prepare($query);
  $stmt->execute([
    'id' => $id
  ]);

  $book = $stmt->fetch();

  if (!$book) {
    header("Location: ../books/index.php");
    exit;
  }

  $query = "select a.id from book_author ba
            left join authors a on ba.author_id = a.id
            where ba.book_id = :book_id
           ";
  $stmt = $pdo->prepare($query);
  $stmt->execute([
    'book_id' => $book['id']
  ]);

  $authorIds = $stmt->fetchAll();

  $book['author_ids'] = array_column($authorIds, 'id');

  return $book;
}

function isBookExists($id)
{
  global $pdo;

  $query = "select count(id) from books where id = :id";

  $stmt = $pdo->prepare($query);
  $stmt->execute([
    'id' => $id
  ]);

  $isBookExists = $stmt->fetchColumn() > 0;

  return $isBookExists;
}

function isIsbnTaken($isbn, $ignoreBookId)
{
  global $pdo;

  $bookId = intval($ignoreBookId);

  $query = "select count(id) from books where isbn = :isbn && id != :book_id";

  $stmt = $pdo->prepare($query);
  $stmt->execute([
    'isbn' => $isbn,
    'book_id' => $bookId
  ]);

  $isIsbnTaken = $stmt->fetchColumn() > 0;

  return $isIsbnTaken;
}
