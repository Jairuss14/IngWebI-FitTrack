<?php
// php/register.php
declare(strict_types=1);

require_once __DIR__ . "/User.php";

function isValidUsername(string $u): bool {
  return (bool) preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $u);
}

function isStrongPassword(string $p): bool {
  // >=8, 1 mayuscula, 1 minuscula, 1 numero
  return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $p);
}

function isAdult(string $dob): bool {
  $dt = DateTime::createFromFormat('Y-m-d', $dob);
  if (!$dt) return false;

  $today = new DateTime("today");
  $age = $dt->diff($today)->y;
  return $age >= 18;
}

// Aceptamos POST normal (form submit)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo "Método no permitido";
  exit;
}

// sanitizar input 
$username = trim((string)($_POST["username"] ?? ""));
$dob = (string)($_POST["dob"] ?? "");
$password = (string)($_POST["password"] ?? "");
$password2 = (string)($_POST["password2"] ?? "");

// Validaciones servidor
$errors = [];

if ($username === "") $errors[] = "El nombre de usuario es obligatorio.";
elseif (!isValidUsername($username)) $errors[] = "Usuario inválido. Usa 3–20 caracteres: letras, números, _ o -.";

if ($dob === "") $errors[] = "La fecha de nacimiento es obligatoria.";
elseif (!isAdult($dob)) $errors[] = "Debes ser mayor de 18 años para registrarte.";

if ($password === "") $errors[] = "La contraseña es obligatoria.";
elseif (!isStrongPassword($password)) $errors[] = "Contraseña débil: 8+ con mayúscula, minúscula y número.";

if ($password2 === "") $errors[] = "Debes verificar la contraseña.";
elseif ($password2 !== $password) $errors[] = "Las contraseñas no coinciden.";

if (!$errors && User::existsByUsername($username)) {
  $errors[] = "Ese nombre de usuario ya está en uso.";
}

if ($errors) {
  // Respuesta simple y segura (sin mostrar inputs tal cual)
  $safe = array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, "UTF-8"), $errors);

  echo "<!doctype html><html lang='es'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Error de registro</title></head><body style='font-family:system-ui;background:#393F43;color:#fff;padding:24px;'>";
  echo "<h1 style='margin:0 0 12px'>No se pudo registrar</h1>";
  echo "<ul>";
  foreach ($safe as $msg) echo "<li style='color:#F26A5D;font-weight:700;'>$msg</li>";
  echo "</ul>";
  echo "<p><a href='../register.html' style='color:#fff;text-decoration:underline;'>Volver al registro</a></p>";
  echo "</body></html>";
  exit;
}

// Crear usuario
try {
  $id = User::create($username, $password, $dob);

  // Inicio sesion
  session_start();
  session_regenerate_id(true);
  $_SESSION["user_id"] = $id;
  $_SESSION["username"] = $username;

  header("Location: ../dashboard.php");
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo "Error interno.";
  exit;
}