<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");
start_session();

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"No autorizado"]); exit; }

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["ok"=>false,"error"=>"Método no permitido"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"JSON inválido"]); exit; }

$goal_type = (string)($data["goal_type"] ?? "");
$target_value = (int)($data["target_value"] ?? 0);
$period = (string)($data["period"] ?? "month");
$activity_type = trim((string)($data["activity_type"] ?? ""));
$activity_type = ($activity_type === "") ? null : $activity_type;
$start_date = date("Y-m-d"); // inicio = hoy

$validTypes = ["minutes","activities"];
$validPeriods = ["week","month","all"];

if (!in_array($goal_type, $validTypes, true)) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Tipo de objetivo inválido"]); exit; }
if ($target_value < 1 || $target_value > 100000) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Objetivo inválido"]); exit; }
if (!in_array($period, $validPeriods, true)) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Periodo inválido"]); exit; }

try {
  $pdo = db();

  // desactivar objetivo anterior (si existe)
  $pdo->prepare("UPDATE goals SET is_active=0 WHERE user_id=? AND is_active=1")->execute([$userId]);

  // insertar nuevo
  $stmt = $pdo->prepare("INSERT INTO goals (user_id, goal_type, target_value, activity_type, period, start_date, is_active) VALUES (?,?,?,?,?,?,1)");
  $stmt->execute([$userId, $goal_type, $target_value, $activity_type, $period,$start_date]);

  echo json_encode(["ok"=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"Error interno"]);
}