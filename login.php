<?php
error_reporting(E_ERROR | E_PARSE);
session_start();
require_once __DIR__ . '/../lib/auth.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (login($email, $password)) {
        $success = "Login successful! Redirecting...";
        header("refresh:1.5;url=dashboard.php");
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>

    <style>
        body {
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .login-container {
            width: 380px;
            margin: 120px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
            text-align: center;
        }

        h2 {
            margin-bottom: 22px;
            color: #333;
            font-weight: 600;
        }

        .alert-error {
            background: #ffdddd;
            color: #a30000;
            padding: 12px;
            margin-bottom: 15px;
            border-left: 5px solid #ff0000;
            border-radius: 6px;
            font-size: 15px;
            text-align: left;
        }

        .alert-success {
            background: #ddffdd;
            color: #0a6e0a;
            padding: 12px;
            margin-bottom: 15px;
            border-left: 5px solid #00c200;
            border-radius: 6px;
            font-size: 15px;
            text-align: left;
        }

        input {
            width: 92%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: #fafafa;
            font-size: 16px;
        }

        input:focus {
            border-color: #007bff;
            outline: none;
            background: #fff;
        }

        button {
            width: 97%;
            padding: 12px;
            background: #007bff;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-top: 12px;
            transition: 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .register-link {
            margin-top: 15px;
            font-size: 14px;
        }

        .register-link a {
            color: #007bff;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .footer-text {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h2>User Login</h2>

    <?php if ($error): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter Email Address" required>

        <input type="password" name="password" placeholder="Enter Password" required>

        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        Don’t have an account? <a href="register.php">Create one</a>
    </div>

    <div class="footer-text">
        © <?= date("Y") ?> SecureLearn
    </div>

</div>

</body>
</html>
