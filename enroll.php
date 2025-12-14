<?php
session_start();

require_once "../config/config.php";
require_once "../lib/auth.php";

require_login();

/* ---------------- USER ---------------- */
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    exit("User not logged in.");
}

/* ---------------- COURSE ID ---------------- */
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$courseId) {
    http_response_code(400);
    exit("Invalid course.");
}

/* ---------------- COURSE CHECK ---------------- */
$course = fetch_query(
    "SELECT id, title FROM courses WHERE id = ? AND is_active = 1",
    [$courseId]
);

if (!$course) {
    http_response_code(404);
    exit("Course not found or inactive.");
}

/* ---------------- ALREADY ENROLLED? ---------------- */
$existing = fetch_query(
    "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?",
    [$userId, $courseId]
);

if ($existing) {
    // Already enrolled → go straight to learning
    header("Location: learn.php?course_id=" . $courseId);
    exit;
}

/* ---------------- FIRST UNIT ---------------- */
$firstUnit = fetch_query(
    "SELECT id FROM units WHERE course_id = ? ORDER BY unit_number ASC LIMIT 1",
    [$courseId]
);

$firstUnitId = $firstUnit['id'] ?? null;

/* ---------------- ENROLL ---------------- */
execute_query(
    "INSERT INTO enrollments (user_id, course_id, current_unit_id, completed, enrolled_at)
     VALUES (?, ?, ?, 0, NOW())",
    [$userId, $courseId, $firstUnitId]
);

/* ---------------- REDIRECT ---------------- */
if ($firstUnitId) {
    header("Location: learn.php?course_id=$courseId&unit_id=$firstUnitId");
} else {
    header("Location: course_view.php?course_id=$courseId");
}
exit;
