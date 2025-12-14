<?php
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/rbac.php";

require_login();
$pageTitle = "Dashboard";

$user = current_user();
$db = db();

$course = $db->query("SELECT * FROM courses WHERE is_active=1 LIMIT 1")->fetch();

if (!$course) {
    $units = [];
    $progressPct = 0;
} else {
    $enrollment = $db->prepare("SELECT * FROM enrollments WHERE user_id=? AND course_id=?");
    $enrollment->execute([$user["id"], $course["id"]]);
    $en = $enrollment->fetch();

    if (!$en) {
        $ins = $db->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?,?)");
        $ins->execute([$user["id"], $course["id"]]);
    }

    $unitsStmt = $db->prepare("SELECT u.*, p.status, p.score
                               FROM units u
                               LEFT JOIN progress p ON p.unit_id=u.id AND p.user_id=?
                               WHERE u.course_id=? ORDER BY u.unit_number");
    $unitsStmt->execute([$user["id"], $course["id"]]);
    $units = $unitsStmt->fetchAll();

    $done = 0; foreach ($units as $u) if ($u["status"] === "complete") $done++;
    $total = count($units);
    $progressPct = $total ? (int)round(($done/$total)*100) : 0;
}

require "partials/header.php";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Courses | SecureLearn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<style>
/* Custom dashboard CSS */
body {
    background: #f8f9fa;
}

.card {
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.card-header {
    font-weight: 600;
    background-color: #fff;
    border-bottom: 1px solid #dee2e6;
}

.progress-bar {
    background-color: #0d6efd;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s;
}

.list-group-item:hover {
    background: #f1f3f5;
    text-decoration: none;
}

.badge {
    font-size: 0.8rem;
    padding: 0.4em 0.6em;
}

.btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
}

.btn-outline-primary:hover {
    background: #0d6efd;
    color: #fff;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: #fff;
}

.btn-outline-success {
    border-color: #198754;
    color: #198754;
}

.btn-outline-success:hover {
    background: #198754;
    color: #fff;
}
</style>

<div class="container my-4">
    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="h5 mb-1">Your Training Path</h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($course["title"] ?? "No active course") ?></p>
            </div>
            <div class="text-end">
                <div class="small text-muted">Overall Progress</div>
                <div class="fw-bold fs-5"><?= $progressPct ?>%</div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="progress" style="height: 12px;">
                <div class="progress-bar" role="progressbar" style="width: <?= $progressPct ?>%"></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0">Units (<?= count($units) ?>)</h3>
                    <a class="btn btn-sm btn-outline-primary" href="courses.php">Open course page</a>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($units as $u): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start" 
                           href="unit.php?id=<?= (int)$u["id"] ?>">
                            <div>
                                <div class="fw-semibold">Unit <?= $u["unit_number"] ?> — <?= htmlspecialchars($u["title"]) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($u["summary"]) ?></div>
                            </div>
                            <span class="badge rounded-pill <?= $u["status"]==="complete" ? "bg-success" : ($u["status"]==="in_progress" ? "bg-warning text-dark" : "bg-secondary") ?>">
                                <?= $u["status"] ?: "not_started" ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($units)): ?>
                        <p class="text-center text-muted my-3">No units available for this course.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Next Step</h3></div>
                <div class="card-body">
                    <?php
                        $next = null;
                        foreach ($units as $u) { if ($u["status"] !== "complete") { $next = $u; break; } }
                    ?>
                    <?php if ($next): ?>
                        <p class="mb-2 small text-muted">Continue with:</p>
                        <a class="btn btn-primary w-100" href="unit.php?id=<?= (int)$next["id"] ?>">
                            Unit <?= (int)$next["unit_number"] ?> · <?= htmlspecialchars($next["title"]) ?>
                        </a>
                    <?php else: ?>
                        <p class="mb-2 small text-muted">You have completed all units.</p>
                        <a class="btn btn-success w-100" href="certificate.php">Generate Certificate</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (is_admin() || is_trainer()): ?>
                <div class="card shadow-sm">
                    <div class="card-header"><h3 class="h6 mb-0">Admin Tools</h3></div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a class="btn btn-outline-primary w-100" href="../admin/index.php">Admin Console</a>
                        <a class="btn btn-outline-secondary w-100" href="../admin/users.php">Users</a>
                        <a class="btn btn-outline-success w-100" href="../admin/courses.php">Manage Courses</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require "partials/footer.php"; ?>
