<?php
session_start();
require_once __DIR__ . "/../lib/auth.php";   // loads login(), require_login(), current_user()
require_once __DIR__ . "/../lib/db.php";     // if you use a db() helper

require_login(); // now this function exists
$pageTitle = "Dashboard";

$user = current_user();
$db = db(); // or use $conn from config.php

// Fetch all courses user enrolled
$coursesStmt = $db->prepare("
    SELECT c.*, e.id as enrollment_id 
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=?
    ORDER BY c.created_at DESC
");
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();

// Count completed units for each course
$completed = 0;
$ongoing = 0;
$certificates = [];

foreach ($courses as $c) {
    $unitsStmt = $db->prepare("SELECT u.id, p.status
                               FROM units u
                               LEFT JOIN progress p ON p.unit_id=u.id AND p.user_id=? 
                               WHERE u.course_id=?");
    $unitsStmt->execute([$user['id'], $c['id']]);
    $units = $unitsStmt->fetchAll();

    $total = count($units);
    $done = count(array_filter($units, fn($u) => $u['status'] === 'complete'));

    if ($done === $total && $total>0) {
        $completed++;
        $certStmt = $db->prepare("SELECT * FROM certificates WHERE user_id=? AND course_id=?");
        $certStmt->execute([$user['id'], $c['id']]);
        $cert = $certStmt->fetch();
        if ($cert) $certificates[] = $cert;
    } elseif ($done>0) {
        $ongoing++;
    }
}

require "partials/header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home | SecureLearn</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.card { border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-header { background:#fff; font-weight:600; border-bottom:1px solid #dee2e6; }
.badge { font-size:0.85rem; }
.btn { border-radius: 8px; }
.list-group-item { transition: background 0.2s; }
.list-group-item:hover { background: #f1f3f5; text-decoration: none; }
.progress-bar { background-color: #0d6efd; }
</style>
</head>
<body>

<div class="container my-4">
    <h3 class="mb-4">Welcome, <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?> 
        <span class="badge bg-info text-dark"><?= $user['role'] ?></span>
    </h3>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Ongoing Courses</h6>
                <div class="fs-3 fw-bold"><?= $ongoing ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Completed Courses</h6>
                <div class="fs-3 fw-bold"><?= $completed ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Certificates Ready</h6>
                <div class="fs-3 fw-bold"><?= count($certificates) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h6>Exams Taken</h6>
                <div class="fs-3 fw-bold">
                    <?= array_sum(array_map(fn($c) => isset($c['score'])?1:0, $courses)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Your Courses</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($courses as $c): 
                        $unitsStmt = $db->prepare("SELECT u.id, p.status FROM units u LEFT JOIN progress p ON p.unit_id=u.id AND p.user_id=? WHERE u.course_id=?");
                        $unitsStmt->execute([$user['id'], $c['id']]);
                        $units = $unitsStmt->fetchAll();
                        $total = count($units);
                        $done = count(array_filter($units, fn($u) => $u['status']==='complete'));
                        $progress = $total?round(($done/$total)*100):0;
                    ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="course_view.php?id=<?= $c['id'] ?>">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($c['title']) ?></div>
                                <div class="small text-muted"><?= $done ?>/<?= $total ?> Units Complete</div>
                            </div>
                            <span class="badge rounded-pill bg-success"><?= $progress ?>%</span>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($courses)): ?>
                        <p class="text-center text-muted my-3">You are not enrolled in any courses yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Certificates</div>
                <div class="card-body d-flex flex-column gap-2">
                    <?php foreach($certificates as $cert): ?>
                        <a class="btn btn-outline-success" href="certificate.php?id=<?= $cert['id'] ?>" target="_blank">
                            <?= htmlspecialchars($cert['course_title'] ?? "Certificate") ?> · Download
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($certificates)): ?>
                        <p class="text-muted">No certificates ready for download.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require "partials/footer.php"; ?>
</body>
</html>
