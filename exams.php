$completed = $db->prepare(
 "SELECT COUNT(*) FROM progress 
  WHERE user_id=:u AND course_id=:c AND status='complete'"
);
$completed->execute([':u'=>$user['id'], ':c'=>$courseId]);

$total = $db->prepare(
 "SELECT COUNT(*) FROM units WHERE course_id=:c"
);
$total->execute([':c'=>$courseId]);

if ($completed->fetchColumn() < $total->fetchColumn()) {
   exit("Complete all lessons to unlock exam.");
}
