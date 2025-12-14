<?php
require_once __DIR__ . "/../../lib/admin_auth.php";
require_admin();
require_once __DIR__ . "/../../lib/db.php";

$pdo = db();

$stmt = $pdo->query("
    SELECT a.id, a.event_type, u.username AS user_name, a.created_at
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 50
");
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($activities);
