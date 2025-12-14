<?php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../lib/db.php";

$pdo = db();

/* ---------- Inputs (filters + pagination) ---------- */
$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$level    = trim($_GET['level'] ?? '');
$min      = isset($_GET['min']) ? (float)$_GET['min'] : null;
$max      = isset($_GET['max']) ? (float)$_GET['max'] : null;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset  = ($page - 1) * $perPage;

/* ---------- Build WHERE + params ---------- */
$where   = ["is_active = 1"];
$params  = [];

if ($q !== '') {
    $where[] = "(title LIKE :q OR description LIKE :q OR category LIKE :q OR code LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($category !== '') {
    $where[] = "category = :category";
    $params[':category'] = $category;
}
if ($level !== '') {
    $where[] = "level = :level";
    $params[':level'] = $level;
}
if ($min !== null) {
    $where[] = "price >= :min";
    $params[':min'] = $min;
}
if ($max !== null) {
    $where[] = "price <= :max";
    $params[':max'] = $max;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ---------- Count total for pagination ---------- */
$countSql = "SELECT COUNT(*) FROM courses $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

/* ---------- Fetch courses ---------- */
$sql = "SELECT id, code, title, description, category, level, duration, price, thumbnail, rating
        FROM courses $whereSql
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Helper functions ---------- */
function escape($s) {
    return htmlspecialchars($s, ENT_QUOTES);
}

function build_query(array $overrides = []) {
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') unset($base[$k]);
        else $base[$k] = $v;
    }
    return http_build_query($base);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SecureLearn Courses</title>
<style>
:root{
    --bg:#f5f7fb;
    --card:#fff;
    --accent:#0d6efd;
    --muted:#6b7280;
    --radius:14px;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,Arial;background:var(--bg);color:#122;}
header{background:#fff;padding:18px 28px;box-shadow:0 6px 20px rgba(15,15,20,0.04);display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;top:0;z-index:10}
.logo{font-weight:700;font-size:20px;color:var(--accent)}
nav a{margin-right:12px;color:#111;text-decoration:none}
.container{max-width:1200px;margin:28px auto;padding:0 18px}
.hero{display:flex;flex-wrap:wrap;gap:18px;align-items:center;justify-content:space-between;margin-bottom:18px}
.hero .left{max-width:720px}
h1{margin:0 0 6px;font-size:28px}
p.lead{margin:0;color:var(--muted)}
.filters{display:flex;flex-wrap:wrap;gap:12px;margin-top:14px;align-items:end}
.filter {background:var(--card);padding:12px;border-radius:10px;box-shadow:0 6px 18px rgba(12,13,20,0.03);display:flex;flex-direction:column;min-width:160px}
.filter label{font-size:13px;color:var(--muted);margin-bottom:6px}
.filter input[type="text"], .filter select, .filter input[type="number"]{padding:10px;border-radius:8px;border:1px solid #e6e9ef;font-size:14px}
.actions{display:flex;gap:8px;align-items:center}
.btn {background:var(--accent);color:#fff;padding:10px 14px;border-radius:10px;border:none;font-weight:600;cursor:pointer}
.btn.ghost{background:transparent;color:var(--accent);border:1px solid #e6ecff}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:18px}
.card{background:var(--card);border-radius:var(--radius);overflow:hidden;box-shadow:0 8px 26px rgba(12,13,20,0.05);display:flex;flex-direction:column;cursor:pointer}
.thumb{height:180px;width:100%;object-fit:cover;background:#e9f0ff;display:block}
.card-body{padding:14px;flex:1;display:flex;flex-direction:column;gap:8px}
.meta{display:flex;gap:8px;flex-wrap:wrap}
.badge{font-size:12px;padding:6px 8px;border-radius:999px;background:#eef3ff;color:#08306b}
.title{font-size:16px;font-weight:700;margin:0}
.desc{font-size:13px;color:var(--muted);margin:0}
.card-foot{display:flex;justify-content:space-between;align-items:center;padding:12px;border-top:1px solid #f1f5fa;background:#fff}
.price{font-weight:700}
.rating{font-size:13px;color:var(--muted)}
.empty{padding:24px;text-align:center;color:var(--muted);background:#fff;border-radius:12px;box-shadow:0 6px 18px rgba(12,13,20,0.03)}
.pagination{display:flex;gap:8px;justify-content:center;margin:20px 0}
.page{padding:8px 12px;border-radius:8px;background:#fff;border:1px solid #eef2f7;cursor:pointer}
.page.active{background:var(--accent);color:#fff;border-color:var(--accent)}
@media (max-width:1000px){.grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:640px){header{padding:12px} .grid{grid-template-columns:1fr} .filters{flex-direction:column;align-items:stretch} .filter{min-width:auto;width:100%} h1{font-size:22px}}
</style>
</head>
<body>

<header>
    <div class="logo">SecureLearn</div>
    <nav aria-label="Main">
        <a href="#">Courses</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
    </nav>
</header>

<main class="container">
  <section class="hero">
    <div class="left">
      <h1>Security & Guard Training — Trusted by professionals</h1>
      <p class="lead">Browse SecureLearn's security courses: Introduction, Technology, incident response, physical security, and compliance training.</p>

      <form method="get" class="filters" onsubmit="return true;">
        <div class="filter">
          <label for="q">Search</label>
          <input id="q" name="q" type="text" value="<?= escape($q) ?>" placeholder="title, description or code">
        </div>
        <div class="filter">
          <label for="category">Category</label>
          <select name="category" id="category">
            <option value="">All</option>
            <option <?= $category==='Cybersecurity'?'selected':'' ?>>Private Guarding</option>
            <option <?= $category==='Physical Security'?'selected':'' ?>>Physical Security</option>
            <option <?= $category==='Compliance'?'selected':'' ?>>Compliance</option>
            <option <?= $category==='Forensics'?'selected':'' ?>>Forensics</option>
          </select>
        </div>
        <div class="filter">
          <label for="level">Level</label>
          <select name="level" id="level">
            <option value="">All</option>
            <option <?= $level==='Beginner'?'selected':'' ?>>Beginner</option>
            <option <?= $level==='Intermediate'?'selected':'' ?>>Intermediate</option>
            <option <?= $level==='Advanced'?'selected':'' ?>>Advanced</option>
          </select>
        </div>
        <div class="filter" style="min-width:180px;">
          <label>Price (KES)</label>
          <div style="display:flex;gap:8px">
            <input name="min" type="number" placeholder="min" value="<?= escape($min) ?>">
            <input name="max" type="number" placeholder="max" value="<?= escape($max) ?>">
          </div>
        </div>
        <div class="actions">
          <button type="submit" class="btn">Apply</button>
          <a href="?" class="btn ghost">Reset</a>
        </div>
      </form>
    </div>
  </section>

  <section aria-label="Course results">
    <div class="grid">
    <?php if($courses): ?>
      <?php foreach($courses as $c): ?>
        <article class="card" onclick="window.location='course.php?id=<?= $c['id'] ?>'">
          <?php if($c['thumbnail']): ?>
            <img src="<?= escape($c['thumbnail']) ?>" alt="<?= escape($c['title']) ?>" class="thumb">
          <?php else: ?>
            <div class="thumb d-flex" style="display:flex;align-items:center;justify-content:center;color:var(--muted);font-weight:700"><?= escape($c['code']) ?></div>
          <?php endif; ?>
          <div class="card-body">
            <div class="meta">
              <span class="badge"><?= escape($c['category']) ?></span>
              <span class="badge"><?= escape($c['level']) ?></span>
              <span class="badge"><?= escape($c['duration']) ?></span>
            </div>
            <h3 class="title"><?= escape($c['title']) ?></h3>
            <p class="desc"><?= escape(strlen($c['description'])>120?substr($c['description'],0,117).'…':$c['description']) ?></p>
          </div>
          <div class="card-foot">
            <div class="price">Ksh <?= number_format($c['price']) ?></div>
            <div class="rating">⭐ <?= number_format($c['rating'],1) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty">No courses match your filters.</div>
    <?php endif; ?>
    </div>

    <?php if($totalPages>1): ?>
      <div class="pagination">
        <?php if($page>1): ?>
          <a class="page" href="?<?= build_query(['page'=>$page-1]) ?>">‹</a>
        <?php endif; ?>
        <?php for($p=1;$p<=$totalPages;$p++): ?>
          <a class="page <?= $p==$page?'active':'' ?>" href="?<?= build_query(['page'=>$p]) ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if($page<$totalPages): ?>
          <a class="page" href="?<?= build_query(['page'=>$page+1]) ?>">›</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
