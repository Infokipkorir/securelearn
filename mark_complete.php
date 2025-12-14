<?php
session_start();
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";

require_login();
$user = current_user();
$db = db();

$unitId   = (int)($_POST['unit_id'] ?? 0);
$courseId = (int)($_POST['course_id'] ?? 0);

if ($unitId <= 0 || $courseId <= 0) {
    die("Invalid request.");
}

/* ---------- Mark unit as complete ---------- */
$stmt = $db->prepare("
    INSERT INTO progress (user_id, unit_id, status, updated_at)
    VALUES (:u, :unit, 'complete', NOW())
    ON DUPLICATE KEY UPDATE status='complete', updated_at=NOW()
");
$stmt->execute([
    ':u' => $user['id'],
    ':unit' => $unitId
]);

/* ---------- Check if course is fully completed ---------- */
$unitsStmt = $db->prepare("SELECT id FROM units WHERE course_id=:c");
$unitsStmt->execute([':c'=>$courseId]);
$unitIds = $unitsStmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($unitIds)) {
    $placeholders = implode(',', $unitIds);
    $progressStmt = $db->prepare("
        SELECT COUNT(*) FROM progress 
        WHERE user_id=:u AND unit_id IN ($placeholders) AND status='complete'
    ");
    $progressStmt->execute([':u'=>$user['id']]);
    $completedCount = $progressStmt->fetchColumn();

    if ($completedCount == count($unitIds)) {
        // User completed the course
        // Optional: insert certificate
        $certStmt = $db->prepare("
            INSERT INTO certificates (user_id, course_id, course_title, created_at)
            VALUES (:u, :c, (SELECT title FROM courses WHERE id=:c2), NOW())
        ");
        $certStmt->execute([
            ':u' => $user['id'],
            ':c' => $courseId,
            ':c2'=> $courseId
        ]);

        // Optional: log admin activity
        $db->prepare("
            INSERT INTO admin_activity (action, course_id, user_id, created_at)
            VALUES ('course_complete', :c, :u, NOW())
        ")->execute([
            ':c' => $courseId,
            ':u' => $user['id']
        ]);
    }
}

header("Location: course_view.php?id=".$courseId);
exit();
