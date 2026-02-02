<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/Activity.php";

header("Content-Type: application/json; charset=utf-8");

start_session();
$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "No autorizado"]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Método no permitido"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "JSON inválido"]);
  exit;
}

$id = (int)($data["id"] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "ID inválido"]);
  exit;
}

try {
  $deleted = Activity::delete($userId, $id);
  if (!$deleted) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "No encontrada o no autorizada"]);
    exit;
  }
  echo json_encode(["ok" => true, "deleted_id" => $id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Error interno"]);
}