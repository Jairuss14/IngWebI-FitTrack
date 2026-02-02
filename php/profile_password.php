<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");
start_session();

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"No autorizado"]); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); echo json_encode(["ok"=>false,"error"=>"Método no permitido"]); exit; }

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"JSON inválido"]); exit; }

$current = (string)($data["current_password"] ?? "");
$new = (string)($data["new_password"] ?? "");

if (strlen($new) < 8) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"La nueva contraseña debe tener al menos 8 caracteres."]); exit; }

try {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"No autorizado"]); exit; }

  if (!password_verify($current, (string)$row["password_hash"])) {
    http_response_code(400);
    echo json_encode(["ok"=>false,"error"=>"La contraseña actual no es correcta."]);
    exit;
  }

  $hash = password_hash($new, PASSWORD_DEFAULT);
  $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
  $upd->execute([$hash, $userId]);

  echo json_encode(["ok"=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"Error interno"]);
}