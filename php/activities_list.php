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

$type = (string)($_GET["type"] ?? "all");
$from = trim((string)($_GET["from"] ?? ""));
$to   = trim((string)($_GET["to"] ?? ""));

try {
  $activities = Activity::list($userId, $type, $from, $to);
  echo json_encode(["ok" => true, "activities" => $activities]);
} catch (InvalidArgumentException $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Error interno"]);
}