<?php
declare(strict_types=1);

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");
start_session();

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "No autorizado"]);
  exit;
}

try {
  $pdo = db();

  // Objetivo activo 
  $stmt = $pdo->prepare("
    SELECT id, goal_type, target_value, activity_type, period, created_at
    FROM goals
    WHERE user_id = ? AND is_active = 1
    ORDER BY id DESC
    LIMIT 1
  ");
  $stmt->execute([$userId]);
  $goal = $stmt->fetch();

  if (!$goal) {
    echo json_encode(["ok" => true, "has_goal" => false]);
    exit;
  }

  $goalStartDate = (new DateTime((string)$goal["created_at"]))->format("Y-m-d");
  
  // Base: solo actividades con fecha >= inicio del objetivo
  $whereParts = ["activity_date >= ?"];
  $params = [$userId, $goalStartDate];

  // Periodo (se aplica ademas del inicio)
  if ($goal["period"] === "week") {
    $whereParts[] = "activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
  } elseif ($goal["period"] === "month") {
    $whereParts[] = "activity_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
  }

  $endDate = null;
  if ($goal["period"] === "week") {
    $endDate = (new DateTime($goalStartDate))->modify("+7 day")->format("Y-m-d");
  } elseif ($goal["period"] === "month") {
    $endDate = (new DateTime($goalStartDate))->modify("+30 day")->format("Y-m-d");
  }




  // Filtro por tipo (opcional)
  $whereType = "";
  if (!empty($goal["activity_type"])) {
    $whereType = " AND type = ?";
    $params[] = (string)$goal["activity_type"];
  }

  $where = " AND " . implode(" AND ", $whereParts);

  // Progreso
  if ($goal["goal_type"] === "minutes") {
    $sql = "
      SELECT COALESCE(SUM(minutes),0) AS progress
      FROM activities
      WHERE user_id = ? $where $whereType
    ";
  } else { // activities
    $sql = "
      SELECT COUNT(*) AS progress
      FROM activities
      WHERE user_id = ? $where $whereType
    ";
  }

  $st2 = $pdo->prepare($sql);
  $st2->execute($params);
  $row = $st2->fetch();
  $progress = (int)($row["progress"] ?? 0);

  echo json_encode([
    "ok" => true,
    "has_goal" => true,
    "goal" => [
      "goal_type" => (string)$goal["goal_type"],
      "target_value" => (int)$goal["target_value"],
      "activity_type" => $goal["activity_type"] ? (string)$goal["activity_type"] : null,
      "period" => (string)$goal["period"],
      "progress" => $progress,
      "starts_from" => $goalStartDate,
      "ends_at" => $endDate,
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Error interno en goal_get"]);
}