<?php
// php/db.php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host = "localhost";
  $dbname = "fittrack_db";
  $user = "root";      // cambiar si el usuario es distinto
  $pass = "";          // cambiar si tienes contraseña
  $charset = "utf8mb4";

  $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  $pdo = new PDO($dsn, $user, $pass, $options);
  return $pdo;
}