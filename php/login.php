<?php
// php/login.php
declare(strict_types=1);

require_once __DIR__ . "/User.php";
// Metodo no permitido
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo "Método no permitido";
  exit;
}

$username = trim((string)($_POST["username"] ?? ""));
$password = (string)($_POST["password"] ?? "");
// Gestion de errores
$errors = [];
if ($username === "") $errors[] = "El usuario es obligatorio.";
if ($password === "") $errors[] = "La contraseña es obligatoria.";

if ($errors) {
  $safe = array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, "UTF-8"), $errors);
  echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Error de login</title></head><body style='font-family:system-ui;background:#393F43;color:#fff;padding:24px;'>";
  echo "<h1 style='margin:0 0 12px'>No se pudo iniciar sesión</h1>";
  echo "<ul>";
  foreach ($safe as $msg) echo "<li style='color:#F26A5D;font-weight:700;'>$msg</li>";
  echo "</ul>";
  echo "<p><a href='../login.html' style='color:#fff;text-decoration:underline;'>Volver al login</a></p>";
  echo "</body></html>";
  exit;
}

$user = User::verifyLogin($username, $password);
if (!$user) {
  echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Login</title></head><body style='font-family:system-ui;background:#393F43;color:#fff;padding:24px;'>";
  echo "<h1 style='margin:0 0 12px'>Credenciales incorrectas</h1>";
  echo "<p style='color:#F26A5D;font-weight:700;'>Usuario o contraseña no válidos.</p>";
  echo "<p><a href='../login.html' style='color:#fff;text-decoration:underline;'>Volver al login</a></p>";
  echo "</body></html>";
  exit;
}
// Inicio de sesion
session_start();
session_regenerate_id(true);
$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["username"];
$_SESSION["avatar_path"] =$user["avatar_path"];
header("Location: ../dashboard.php");
exit;