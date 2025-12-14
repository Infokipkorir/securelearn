<?php
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

/* ---------------- COURSE ---------------- */
$course = fetch_query(
    "SELECT id, title, summary
     FROM courses
     WHERE id = ? AND is_active = 1",
    [$courseId]
);

if (!$course) {
    http_response_code(404);
    exit("Course not found or inactive.");
}

/* ---------------- UNITS ---------------- */
$units = fetch_all_query(
    "SELECT id, unit_number, title, summary,
            learning_outcomes, description, youtube_embed
     FROM units
     WHERE course_id = ?
     ORDER BY unit_number ASC",
    [$courseId]
);

if (!$units) {
    $units = [];
}

/* ---------------- ENROLLMENT ---------------- */
$enrolled = fetch_query(
    "SELECT id, current_unit_id, completed
     FROM enrollments
     WHERE user_id = ? AND course_id = ?",
    [$userId, $courseId]
);

/* ---------------- INLINE LEARNING ---------------- */
$learnUnit = null;
$youtubeId = null;

if ($enrolled && isset($_GET['learn_unit'])) {
    $learnUnitId = (int)$_GET['learn_unit'];

    $learnUnit = fetch_query(
        "SELECT id, title, summary, description, youtube_embed
         FROM units
         WHERE id = ? AND course_id = ?",
        [$learnUnitId, $courseId]
    );

    if ($learnUnit && !empty($learnUnit['youtube_embed'])) {
        if (preg_match('/(?:v=|youtu\.be\/|embed\/)([\w\-]+)/', $learnUnit['youtube_embed'], $m)) {
            $youtubeId = $m[1];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($course['title']) ?> | SecureLearn</title>

<style>
body {
    font-family: Arial, sans-serif;
    padding:20px;
    background:#f5f7fa;
}
.unit-box {
    border:1px solid #ddd;
    padding:15px;
    margin-bottom:15px;
    border-radius:8px;
    background:#fff;
}
.learn-box {
    background:#fff;
    border-radius:10px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 4px 10px rgba(0,0,0,0.08);
}
.learn-box iframe {
    width:100%;
    height:420px;
    border-radius:10px;
    border:none;
}
.btn {
    padding:10px 15px;
    background:#007bff;
    color:white;
    text-decoration:none;
    border-radius:6px;
    display:inline-block;
    margin-right:8px;
}
.btn-grey {
    padding:10px 15px;
    background:#6c757d;
    color:white;
    border-radius:6px;
    text-decoration:none;
}
</style>
</head>

<body>

<h1><?= htmlspecialchars($course['title']) ?></h1>
<p><?= nl2br(htmlspecialchars($course['summary'])) ?></p>

<hr>

<!-- ================= ENROLLMENT ================= -->
<?php if (!$enrolled): ?>

    <a class="btn" href="enroll.php?course_id=<?= $courseId ?>">
        Enroll in Course
    </a>

<?php elseif ((int)$enrolled['completed'] === 1): ?>

    <p><strong>🎉 You have completed this course.</strong></p>

<?php else: ?>

    <?php if (!empty($enrolled['current_unit_id'])): ?>
        <a class="btn"
           href="learn.php?course_id=<?= $courseId ?>&unit_id=<?= (int)$enrolled['current_unit_id'] ?>">
            Continue Learning
        </a>
    <?php elseif (!empty($units)): ?>
        <a class="btn"
           href="learn.php?course_id=<?= $courseId ?>&unit_id=<?= (int)$units[0]['id'] ?>">
            Start Course
        </a>
    <?php endif; ?>

<?php endif; ?>

<hr>

<!-- ================= INLINE LEARNING ================= -->
<?php if ($learnUnit && $youtubeId): ?>

<div class="learn-box">
    <h2>Now Learning: <?= htmlspecialchars($learnUnit['title']) ?></h2>
    <p><?= nl2br(htmlspecialchars($learnUnit['summary'])) ?></p>

    <iframe
        src="https://www.youtube.com/embed/<?= $youtubeId ?>"
        allowfullscreen>
    </iframe>

    <p><?= nl2br(htmlspecialchars($learnUnit['description'])) ?></p>

    <a class="btn"
       href="learn.php?course_id=<?= $courseId ?>&unit_id=<?= $learnUnit['id'] ?>">
        Continue in Full Learning Mode →
    </a>
</div>

<?php endif; ?>

<hr>

<h2>Course Units</h2>

<?php if (empty($units)): ?>

    <p>No units have been added to this course yet.</p>

<?php else: ?>

    <?php foreach ($units as $unit): ?>
        <div class="unit-box">

            <h3>
                Unit <?= (int)$unit['unit_number'] ?>:
                <?= htmlspecialchars($unit['title']) ?>
            </h3>

            <p><strong>Summary:</strong><br>
                <?= nl2br(htmlspecialchars($unit['summary'])) ?>
            </p>

            <?php if (!empty($unit['learning_outcomes'])): ?>
                <p><strong>Learning Outcomes:</strong><br>
                    <?= nl2br(htmlspecialchars($unit['learning_outcomes'])) ?>
                </p>
            <?php endif; ?>

            <p><strong>Description:</strong><br>
                <?= nl2br(htmlspecialchars($unit['description'])) ?>
            </p>

            <?php if ($enrolled): ?>
                <a class="btn-grey"
                   href="course_view.php?course_id=<?= $courseId ?>&learn_unit=<?= (int)$unit['id'] ?>">
                    ▶ Preview Lesson
                </a>

                <a class="btn"
                   href="learn.php?course_id=<?= $courseId ?>&unit_id=<?= (int)$unit['id'] ?>">
                    Learn Full →
                </a>
            <?php else: ?>
                <span class="btn-grey">Enroll to access</span>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
