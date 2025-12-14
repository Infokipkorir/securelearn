$code = strtoupper(bin2hex(random_bytes(5)));

$db->prepare(
 "INSERT INTO certificates (user_id, course_id, certificate_code)
  VALUES (:u,:c,:code)"
)->execute([
 ':u'=>$user['id'],
 ':c'=>$courseId,
 ':code'=>$code
]);
