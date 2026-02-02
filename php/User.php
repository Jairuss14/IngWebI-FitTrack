<?php
// php/User.php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

final class User {
  public static function existsByUsername(string $username): bool {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return (bool) $stmt->fetchColumn();
  }

  public static function create(string $username, string $password, string $dob): int {
    $pdo = db();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
      INSERT INTO users (username, password_hash, dob)
      VALUES (:u, :p, :d)
    ");

    $stmt->execute([
      ":u" => $username,
      ":p" => $hash,
      ":d" => $dob
    ]);

    return (int)$pdo->lastInsertId();
  }

  public static function verifyLogin(string $username, string $password): ?array {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, username, password_hash, avatar_path FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) return null;
    if (!password_verify($password, $user["password_hash"])) return null;

    return [
      "id" => (int)$user["id"],
      "username" => (string)$user["username"],
      "avatar_path" => (string)$user["avatar_path"],
    ];
  }
}