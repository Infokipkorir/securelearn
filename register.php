<?php
require_once __DIR__ . "/../lib/auth.php";
$pageTitle = "Register";

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $name  = trim($_POST["full_name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $pass  = $_POST["password"] ?? "";

  if ($name === "" || $email === "" || $pass === "") {
      $errors[] = "All fields are required.";
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Valid email is required.";
  }

  if (!$errors) {
    $success = register($name, $email, $pass, "trainee");

    if (!$success) {
        $errors[] = "Registration failed. Email may already exist.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<style>
    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
        background: #F5F5F5;
    }

    .container {
        width: 100%;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .card {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 28px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        overflow: hidden;
        animation: fadeUp .6s ease;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(40px);}
        to { opacity: 1; transform: translateY(0);}
    }

    .card-header {
        height: 180px;
        background: url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=900&q=60');
        background-size: cover;
        background-position: center;
    }

    .card-body {
        padding: 30px;
    }

    h2 {
        margin: 0 0 20px;
        font-weight: 600;
        font-size: 26px;
    }

    .message-box {
        margin-bottom: 15px;
        padding: 12px;
        border-radius: 10px;
        font-size: 14px;
    }

    .error { background: #ffe0e0; color: #b10000; border:1px solid #ffbdbd; }
    .success { background:#e0ffe8; color:#007a2d; border:1px solid #9fffb8; }

    .input-group {
        margin-bottom: 18px;
    }

    .input-group label {
        display: block;
        font-size: 14px;
        margin-bottom: 6px;
        opacity: .8;
    }

    .input-group input {
        width: 100%;
        padding: 14px;
        border-radius: 14px;
        border: 1px solid #ddd;
        font-size: 15px;
        outline: none;
        transition: .25s;
    }

    .input-group input:focus {
        border-color: #00b36b;
        box-shadow: 0 0 0 2px rgba(0,179,107,0.2);
    }

    .btn {
        width: 100%;
        padding: 15px;
        background: #00b36b;
        border: none;
        border-radius: 14px;
        color: white;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: .25s;
    }

    .btn:hover {
        background: #009456;
    }

    .bottom-text {
        margin-top: 20px;
        text-align: center;
        font-size: 14px;
    }

    .bottom-text a {
        color: #00b36b;
        text-decoration: none;
        font-weight: 500;
    }
</style>
</head>

<body>

<div class="container">
    <div class="card">
        <div class="card-header"></div>

        <div class="card-body">
            <h2>Create Account</h2>

            <?php if ($errors): ?>
                <div class="message-box error">
                    <?= implode("<br>", array_map("htmlspecialchars", $errors)) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message-box success">
                    Registration successful! <a href="login.php">Click here to login</a>.
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="Enter your name">
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a password">
                </div>

                <button class="btn" type="submit">Sign Up</button>
            </form>

            <div class="bottom-text">
                Already registered? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
