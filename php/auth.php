<?php
// php/auth.php
declare(strict_types=1);

function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function require_login(): void {
  start_session();
  if (empty($_SESSION["user_id"]) || empty($_SESSION["username"])) {
    header("Location: ../login.html");
    exit;
  }
}