<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

final class Activity {
  private static array $allowedTypes = ["Running","Gym","Bici","Natación","Caminata"];

  public static function validate(array $data): array {
    $type = trim((string)($data["type"] ?? ""));
    $minutes = (int)($data["minutes"] ?? 0);
    $date = (string)($data["date"] ?? "");
    $notes = trim((string)($data["notes"] ?? ""));

    $errors = [];

    if (!in_array($type, self::$allowedTypes, true)) $errors[] = "Tipo inválido.";
    if ($minutes < 1 || $minutes > 600) $errors[] = "Duración inválida (1–600).";

    $dt = DateTime::createFromFormat("Y-m-d", $date);
    if (!$dt || $dt->format("Y-m-d") !== $date) $errors[] = "Fecha inválida.";

    if (mb_strlen($notes) > 255) $errors[] = "Notas demasiado largas (máx 255).";

    return [$errors, $type, $minutes, $date, $notes];
  }

  public static function add(int $userId, string $type, int $minutes, string $date, string $notes = ""): array {
    $pdo = db();
    $stmt = $pdo->prepare("
      INSERT INTO activities (user_id, type, minutes, activity_date, notes)
      VALUES (:uid, :t, :m, :d, :n)
    ");
    $stmt->execute([
      ":uid" => $userId,
      ":t" => $type,
      ":m" => $minutes,
      ":d" => $date,
      ":n" => ($notes === "" ? null : $notes)
    ]);

    return [
      "id" => (int)$pdo->lastInsertId(),
      "type" => $type,
      "minutes" => $minutes,
      "date" => $date,
      "notes" => $notes
    ];
  }

public static function list(int $userId, string $type = "all", string $from = "", string $to = ""): array {
  $pdo = db();

  // Si no se pasa rango, por defecto: HOY
  if ($from === "" && $to === "") {
    $today = date("Y-m-d");
    $from = $today;
    $to = $today;
  }

  // Validar type (si procede)
  if ($type !== "all" && !in_array($type, self::$allowedTypes, true)) {
    throw new InvalidArgumentException("Filtro inválido.");
  }

  // Validar fechas si vienen
  $isDate = fn(string $s): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

  if ($from !== "" && !$isDate($from)) {
    throw new InvalidArgumentException("Fecha 'desde' inválida.");
  }
  if ($to !== "" && !$isDate($to)) {
    throw new InvalidArgumentException("Fecha 'hasta' inválida.");
  }

  // Si vienen las dos y están invertidas, las intercambiamos (UX mejor)
  if ($from !== "" && $to !== "" && $from > $to) {
    [$from, $to] = [$to, $from];
  }

  // Construir SQL dinámico seguro
  $sql = "
    SELECT id, type, minutes, activity_date, notes
    FROM activities
    WHERE user_id = ?
  ";
  $params = [$userId];

  if ($type !== "all") {
    $sql .= " AND type = ?";
    $params[] = $type;
  }

  if ($from !== "") {
    $sql .= " AND activity_date >= ?";
    $params[] = $from;
  }

  if ($to !== "") {
    $sql .= " AND activity_date <= ?";
    $params[] = $to;
  }

  $sql .= " ORDER BY activity_date DESC, id DESC LIMIT 200";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  return array_map(fn($r) => [
    "id" => (int)$r["id"],
    "type" => (string)$r["type"],
    "minutes" => (int)$r["minutes"],
    "date" => (string)$r["activity_date"], // mantenemos "date" para el frontend
    "notes" => $r["notes"] ?? ""
  ], $stmt->fetchAll());
}
  public static function delete(int $userId, int $activityId): bool {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM activities WHERE id = :id AND user_id = :uid");
    $stmt->execute([
      ":id" => $activityId,
      ":uid" => $userId
    ]);
    return $stmt->rowCount() === 1;
  }
}