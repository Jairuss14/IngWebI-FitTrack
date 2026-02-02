<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");
start_session();

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"No autorizado"]); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); echo json_encode(["ok"=>false,"error"=>"Método no permitido"]); exit; }

$full_name = trim((string)($_POST["full_name"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$bio = trim((string)($_POST["bio"] ?? ""));

$errors = [];
if (mb_strlen($full_name) > 60) $errors[] = "Nombre demasiado largo.";
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";
if (mb_strlen($email) > 120) $errors[] = "Email demasiado largo.";
if (mb_strlen($bio) > 255) $errors[] = "Bio demasiado larga.";

if ($errors) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>implode(" ",$errors)]); exit; }

$avatarPath = null;

try {
  // Si viene avatar, lo procesamos
  if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
    $f = $_FILES["avatar"];
    if ($f["size"] > 5 * 1024 * 1024) {
      http_response_code(400);
      echo json_encode(["ok"=>false,"error"=>"Avatar demasiado grande (máx 5MB)."]);
      exit;
    }

    $mime = mime_content_type($f["tmp_name"]);
    $allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    if (!isset($allowed[$mime])) {
      http_response_code(400);
      echo json_encode(["ok"=>false,"error"=>"Formato de imagen inválido (JPG/PNG/WEBP)."]);
      exit;
    }

    $ext = $allowed[$mime];
    $dir = __DIR__ . "/../uploads/avatars";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = "u{$userId}_" . bin2hex(random_bytes(8)) . "." . $ext;
    $dest = $dir . "/" . $filename;

    $dir = __DIR__ . "/../uploads/avatars";
if (!is_dir($dir)) {
  if (!mkdir($dir, 0777, true)) {
    http_response_code(500);
    echo json_encode(["ok"=>false,"error"=>"No se pudo crear la carpeta uploads/avatars."]);
    exit;
  }
}

if (!is_writable($dir)) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"La carpeta uploads/avatars no tiene permisos de escritura."]);
  exit;
}

$filename = "u{$userId}_" . bin2hex(random_bytes(8)) . "." . $ext;
$dest = $dir . "/" . $filename;

// EXTRA: comprobar que realmente es un upload válido
if (!is_uploaded_file($f["tmp_name"])) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"El archivo no se recibió como subida válida."]);
  exit;
}

if (!move_uploaded_file($f["tmp_name"], $dest)) {
  $err = error_get_last();
  http_response_code(500);
  echo json_encode([
    "ok"=>false,
    "error"=>"No se pudo guardar el avatar.",
    "debug" => [
      "tmp" => $f["tmp_name"],
      "dest" => $dest,
      "php_error" => $err ? ($err["message"] ?? "") : ""
    ]
  ]);
  exit;
}

$avatarPath = "uploads/avatars/" . $filename;
  }

  $pdo = db();

  if ($avatarPath !== null) {
    $stmt = $pdo->prepare("UPDATE users SET full_name = :n, email = :e, bio = :b, avatar_path = :a WHERE id = :id");
    $stmt->execute([
      ":n" => ($full_name === "" ? null : $full_name),
      ":e" => ($email === "" ? null : $email),
      ":b" => ($bio === "" ? null : $bio),
      ":a" => $avatarPath,
      ":id" => $userId
    ]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET full_name = :n, email = :e, bio = :b WHERE id = :id");
    $stmt->execute([
      ":n" => ($full_name === "" ? null : $full_name),
      ":e" => ($email === "" ? null : $email),
      ":b" => ($bio === "" ? null : $bio),
      ":id" => $userId
    ]);
  }
  $_SESSION["avatar_path"] = $avatarPath;
  $resp = ["ok" => true];
  if ($avatarPath !== null) $resp["avatar_path"] = $avatarPath;

  echo json_encode($resp);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"Error interno"]);
}