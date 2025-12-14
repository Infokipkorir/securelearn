<?php
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/db.php";
require_login();

$db = db();
$user = current_user();
$unit_id = (int)($_GET["unit_id"] ?? 0);

$unit = $db->prepare("SELECT * FROM units WHERE id=?"); $unit->execute([$unit_id]);
$unit = $unit->fetch();
if (!$unit) { http_response_code(404); exit("Unit not found"); }

$questions = $db->prepare("SELECT q.id, q.question, o.id AS option_id, o.option_text, o.is_correct
                           FROM quiz_questions q
                           JOIN quiz_options o ON o.question_id=q.id
                           WHERE q.unit_id=? ORDER BY q.id, o.id");
$questions->execute([$unit_id]);

$items = [];
while ($row = $questions->fetch()) {
  $qid = $row["id"];
  if (!isset($items[$qid])) $items[$qid] = ["question" => $row["question"], "options" => []];
  $items[$qid]["options"][] = ["id" => $row["option_id"], "text" => $row["option_text"], "correct" => (int)$row["is_correct"]];
}

$posted = $_SERVER["REQUEST_METHOD"] === "POST";
$score = 0; $total = count($items);

if ($posted) {
  foreach ($items as $qid => $data) {
    $picked = (int)($_POST["q_$qid"] ?? 0);
    foreach ($data["options"] as $opt) {
      if ($opt["id"] === $picked && $opt["correct"] === 1) { $score++; break; }
    }
  }

  $passed = ($total === 0) ? 1 : ( ($score / max(1,$total)) >= 0.6 ); // 60% pass threshold

  $db->prepare("INSERT INTO quiz_attempts (user_id, unit_id, score, passed) VALUES (?,?,?,?)")
     ->execute([$user["id"], $unit_id, $score, $passed ? 1 : 0]);

  $status = $passed ? "complete" : "in_progress";
  $db->prepare("INSERT INTO progress (user_id, unit_id, status, score) VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE status=VALUES(status), score=VALUES(score)")
     ->execute([$user["id"], $unit_id, $status, $score]);

  header("Location: dashboard.php");
  exit;
}

$pageTitle = "Quiz — Unit " . $unit["unit_number"];
require "partials/header.php";
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h5 mb-2">Quiz: Unit <?= (int)$unit["unit_number"] ?> — <?=htmlspecialchars($unit["title"])?></h1>
        <?php if (!$items): ?>
          <div class="alert alert-warning small">No questions have been seeded yet for this unit. Add questions via the admin panel (or seed SQL).</div>
        <?php else: ?>
          <form method="post">
            <?php foreach ($items as $qid => $data): ?>
              <div class="mb-3">
                <div class="fw-semibold mb-2"><?=htmlspecialchars($data["question"])?></div>
                <?php foreach ($data["options"] as $opt): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="q_<?=$qid?>" value="<?=$opt["id"]?>" required>
                    <label class="form-check-label"><?=htmlspecialchars($opt["text"])?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" type="submit">Submit quiz</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require "partials/footer.php"; ?>
