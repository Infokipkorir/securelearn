<?php
session_start();

require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";

require_login();

$user = current_user();
$db   = db();

/* ---------------- COURSE ID ---------------- */
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$courseId) {
    http_response_code(400);
    exit("Invalid course.");
}


/* ---------------- COURSE ---------------- */
$courseStmt = $db->prepare(
    "SELECT * FROM courses WHERE id=:id AND is_active=1"
);
$courseStmt->execute([':id'=>$courseId]);
$course = $courseStmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    exit("Course not found.");
}

/* ---------------- ENROLLMENT CHECK ---------------- */
$enrollStmt = $db->prepare(
  "SELECT id FROM enrollments WHERE user_id=:u AND course_id=:c"
);
$enrollStmt->execute([':u'=>$user['id'], ':c'=>$courseId]);

if (!$enrollStmt->fetchColumn()) {
    exit("You must enroll in this course first.");
}

/* ---------------- UNITS ---------------- */
$unitsStmt = $db->prepare(
  "SELECT * FROM units WHERE course_id=:c ORDER BY unit_number ASC"
);
$unitsStmt->execute([':c'=>$courseId]);
$units = $unitsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$units) {
    exit("No lessons added yet.");
}

/* ---------------- CURRENT UNIT ---------------- */
$currentUnitId = null;

/* Priority 1: URL unit_id */
if (isset($_GET['unit_id'])) {
    $unitCheck = $db->prepare(
        "SELECT id FROM units WHERE id=:u AND course_id=:c"
    );
    $unitCheck->execute([
        ':u'=>(int)$_GET['unit_id'],
        ':c'=>$courseId
    ]);
    if ($unitCheck->fetchColumn()) {
        $currentUnitId = (int)$_GET['unit_id'];
    }
}

/* Priority 2: Auto-resume */
if (!$currentUnitId) {
    $resumeStmt = $db->prepare(
      "SELECT unit_id FROM progress
       WHERE user_id=:u AND course_id=:c
       ORDER BY last_watched_at DESC LIMIT 1"
    );
    $resumeStmt->execute([':u'=>$user['id'], ':c'=>$courseId]);
    $currentUnitId = $resumeStmt->fetchColumn();
}

/* Priority 3: First lesson */
if (!$currentUnitId) {
    $currentUnitId = $units[0]['id'];
}

/* ---------------- FETCH CURRENT UNIT ---------------- */
$currentStmt = $db->prepare(
    "SELECT * FROM units WHERE id=:id AND course_id=:c"
);
$currentStmt->execute([
    ':id'=>$currentUnitId,
    ':c'=>$courseId
]);
$currentUnit = $currentStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUnit) {
    exit("Lesson not found.");
}

/* ---------------- MARK IN PROGRESS ---------------- */
$db->prepare(
  "INSERT INTO progress (user_id, course_id, unit_id, status)
   VALUES (:u,:c,:unit,'in_progress')
   ON DUPLICATE KEY UPDATE
     status='in_progress',
     last_watched_at=NOW()"
)->execute([
  ':u'=>$user['id'],
  ':c'=>$courseId,
  ':unit'=>$currentUnitId
]);

/* ---------------- PROGRESS MAP ---------------- */
$progressStmt = $db->prepare(
  "SELECT unit_id, status FROM progress
   WHERE user_id=:u AND course_id=:c"
);
$progressStmt->execute([':u'=>$user['id'], ':c'=>$courseId]);
$progress = $progressStmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* ---------------- YOUTUBE ID ---------------- */
$youtubeId = null;
if (!empty($currentUnit['youtube_url']) &&
    preg_match('/(?:v=|youtu\.be\/|embed\/)([\w\-]+)/', $currentUnit['youtube_url'], $m)) {
    $youtubeId = $m[1];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($course['title']) ?> | Learn</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f5f7fa; }
.sidebar {
  height:100vh;
  overflow-y:auto;
  background:#fff;
  border-right:1px solid #eee;
}
.lesson-item {
  padding:12px;
  border-bottom:1px solid #f0f0f0;
}
.lesson-item.active { background:#e9f2ff; font-weight:600; }
.lesson-item.complete { color:#198754; }
.video-box iframe {
  width:100%;
  height:420px;
  border-radius:12px;
}
</style>
</head>

<body>

<div class="container-fluid">
  <div class="row">

    <!-- SIDEBAR -->
    <div class="col-md-3 sidebar p-0">
      <div class="p-3 border-bottom">
        <h6 class="mb-0"><?= htmlspecialchars($course['title']) ?></h6>
        <small class="text-muted"><?= count($units) ?> Lessons</small>
      </div>

      <?php foreach ($units as $u): 
        $state = $progress[$u['id']] ?? '';
      ?>
        <a href="?course_id=<?= $courseId ?>&unit_id=<?= $u['id'] ?>"
           class="d-block text-decoration-none lesson-item
           <?= $u['id']==$currentUnitId?'active':'' ?>
           <?= $state=='complete'?'complete':'' ?>">
           
          <?= (int)$u['unit_number'] ?>. <?= htmlspecialchars($u['title']) ?>
          <?php if ($state=='complete'): ?>
            <span class="float-end">✔</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- CONTENT -->
    <div class="col-md-9 p-4">

      <h4><?= htmlspecialchars($currentUnit['title']) ?></h4>
      <p class="text-muted"><?= htmlspecialchars($currentUnit['summary'] ?? '') ?></p>

      <!-- VIDEO -->
      <?php if ($youtubeId): ?>
        <div class="video-box mb-3">
          <iframe src="https://www.youtube.com/embed/<?= $youtubeId ?>"
                  allowfullscreen></iframe>
        </div>
      <?php endif; ?>

      <!-- MARK COMPLETE -->
      <form method="POST" action="mark_complete.php">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <input type="hidden" name="unit_id" value="<?= $currentUnitId ?>">
        <button class="btn btn-success">
          Mark Lesson Complete
        </button>
      </form>

    </div>

  </div>
</div>

</body>
</html>
