<?php
session_start();

// load DB (your config.php sets $conn = new mysqli(...))
require_once dirname(__DIR__) . "/config/config.php";

// require login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';
$course_id = (int)($_GET['id'] ?? 0);

if ($course_id <= 0) {
    die("Invalid course ID.");
}

/* ---------------- Utility: convert YouTube URL to embed ---------------- */
function youtube_embed_src(string $url): ?string {
    if (!$url) return null;
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }
    if (strpos($url, 'youtube.com/embed/') !== false) return $url;
    return null;
}

/* ---------------- Fetch course ---------------- */
$course = null;
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $course = $res->fetch_assoc();
}
$stmt->close();

if (!$course) {
    http_response_code(404);
    die("Course not found or not active.");
}

/* ---------------- Check enrollment ---------------- */
$isEnrolled = false; // initialize
$enrollStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$enrollStmt->bind_param("ii", $user_id, $course_id);
$enrollStmt->execute();
$res = $enrollStmt->get_result();
if ($res && $res->num_rows > 0) $isEnrolled = true;
$enrollStmt->close();

/* ---------------- Handle enrollment ---------------- */
$enrollSuccess = false;
$err = null;
if (!$isEnrolled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $ins = $conn->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
        $ins->bind_param("ii", $user_id, $course_id);
        $ins->execute();
        $ins->close();

        $upd = $conn->prepare("UPDATE courses SET students_count = students_count + 1 WHERE id = ?");
        $upd->bind_param("i", $course_id);
        $upd->execute();
        $upd->close();

        $desc = sprintf("User %s (ID %d) enrolled in course #%d (%s)", $conn->real_escape_string($user_name), $user_id, $course_id, $conn->real_escape_string($course['title']));
        $log = $conn->prepare("INSERT INTO admin_activity (action_type, description, created_at) VALUES ('enrollment', ?, NOW())");
        $log->bind_param("s", $desc);
        $log->execute();
        $log->close();

        $uact = $conn->prepare("INSERT INTO user_activity (user_id, activity_text, created_at) VALUES (?, ?, NOW())");
        $activityText = "Enrolled in course: " . $course['title'];
        $uact->bind_param("is", $user_id, $activityText);
        $uact->execute();
        $uact->close();

        $conn->commit();
        $isEnrolled = true;
        $enrollSuccess = true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Enroll error: " . $e->getMessage());
        $err = "Enrollment failed. Please contact support.";
    }
}

/* ---------------- Fetch lessons ---------------- */
$lessons = [];
$stmt = $conn->prepare("SELECT id, title, youtube_url, content, sort_order FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res) {
    while ($row = $res->fetch_assoc()) $lessons[] = $row;
}
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($course['title']) ?> — SecureLearn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f5f7fa; }
.course-hero { background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.06); overflow:hidden; }
.course-thumb { width:100%; height:260px; object-fit:cover; display:block; }
.enroll-box { background:#fff; border-radius:10px; padding:16px; box-shadow:0 6px 18px rgba(0,0,0,0.04); }
.lesson { background:#fff; border-radius:10px; padding:12px; margin-bottom:12px; box-shadow:0 4px 10px rgba(0,0,0,0.03); }
</style>
</head>
<body>
<div class="container py-4">

    <?php if ($enrollSuccess): ?>
        <div class="alert alert-success">🎉 Enrollment successful. You may now start learning below.</div>
    <?php elseif ($err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="course-hero mb-3">
                <?php
                    $thumb = $course['thumbnail'] ?? '';
                    $thumbUrl = ($thumb && file_exists(dirname(__DIR__) . '/public/assets/' . $thumb)) 
                                ? '/public/assets/' . rawurlencode($thumb) 
                                : '/public/assets/course-placeholder.jpg';
                ?>
                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="thumbnail" class="course-thumb">
                <div class="p-3">
                    <h2 class="h4 mb-1"><?= htmlspecialchars($course['title']) ?></h2>
                    <div class="text-muted small mb-2"><?= htmlspecialchars($course['category'] ?? '') ?> • <?= htmlspecialchars($course['level'] ?? '') ?> • <?= htmlspecialchars($course['duration'] ?? '') ?></div>
                    <p class="mb-1 text-muted"><?= htmlspecialchars($course['description']) ?></p>
                    <div class="mt-2">
                        <strong>Ksh <?= number_format((float)($course['price'] ?? 0), 2) ?></strong>
                        <span class="ms-3 text-muted">⭐ <?= htmlspecialchars($course['rating'] ?? 0) ?></span>
                        <span class="ms-3 text-muted"><?= (int)($course['students_count'] ?? 0) ?> learners</span>
                    </div>
                </div>
            </div>

            <?php if ($isEnrolled): ?>
                <h5>Course Lessons</h5>
                <?php if (empty($lessons)): ?>
                    <div class="alert alert-info">No lessons yet. Instructor will add content soon.</div>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="lesson">
                            <h6 class="mb-2"><?= htmlspecialchars($lesson['title']) ?></h6>
                            <?php if (!empty($lesson['youtube_url'])): 
                                $embed = youtube_embed_src($lesson['youtube_url']);
                                if ($embed): ?>
                                    <div class="ratio ratio-16x9 mb-2">
                                        <iframe src="<?= htmlspecialchars($embed) ?>" allowfullscreen frameborder="0"></iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2"><a href="<?= htmlspecialchars($lesson['youtube_url']) ?>" target="_blank"><?= htmlspecialchars($lesson['youtube_url']) ?></a></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($lesson['content'])): ?>
                                <div><?= nl2br(htmlspecialchars($lesson['content'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">You must enroll to view course lessons.</div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="enroll-box">
                <h5 class="mb-2"><?= htmlspecialchars($course['title']) ?></h5>
                <div class="mb-2 text-muted"><?= htmlspecialchars($course['category'] ?? '') ?> • <?= htmlspecialchars($course['level'] ?? '') ?></div>
                <?php if ($isEnrolled): ?>
                    <div class="alert alert-success">✔ You are enrolled</div>
                    <a href="learn.php?course_id=<?= $course_id ?>" class="btn btn-primary w-100 mb-2">Start Learning</a>
                <?php else: ?>
                    <form method="POST">
                        <button class="btn btn-success w-100">Enroll Now</button>
                    </form>
                <?php endif; ?>
                <div class="mt-3 small text-muted">
                    <div>Instructor: <?= htmlspecialchars($course['instructor'] ?? 'TBA') ?></div>
                    <div>Certificate: <?= !empty($course['certificate']) ? 'Yes' : 'No' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
