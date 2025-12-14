<?php
$pageTitle = "Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SecureLearn Portal</title>
<style>
    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
        background: #f7f7f7;
    }

    header {
        width: 100%;
        padding: 25px 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        box-shadow: 0 3px 20px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
    }

    .logo {
        font-size: 26px;
        font-weight: 700;
        color: #2b2b2b;
    }

    nav a {
        margin-left: 25px;
        text-decoration: none;
        color: #444;
        font-weight: 500;
        transition: .2s;
    }

    nav a:hover {
        color: #0d6efd;
    }

    .hero {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 50px;
        background: #eef6ff;
    }

    .hero-text {
        max-width: 500px;
        margin-right: 40px;
    }

    .hero-text h1 {
        font-size: 46px;
        font-weight: 700;
        line-height: 1.2;
    }

    .hero-text p {
        font-size: 18px;
        margin: 20px 0;
        color: #555;
    }

    .btn-primary {
        padding: 14px 25px;
        background: #0d6efd;
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
    }

    .hero-img img {
        width: 500px;
        border-radius: 20px;
    }

    .section-title {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        margin-top: 70px;
    }

    .courses {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 25px;
        padding: 50px;
    }

    .course-card {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        transition: .3s;
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.15);
    }

    .course-card h3 {
        font-size: 20px;
        margin-bottom: 10px;
    }

    .course-card p {
        font-size: 14px;
        color: #555;
    }

    .footer {
        text-align: center;
        padding: 30px;
        background: #fff;
        margin-top: 60px;
        color: #777;
    }

</style>
</head>
<body>

<header>
    <div class="logo">Secure<span>Portal</span></div>
    <nav>
        <a href="#">Home</a>
        <a href="#">Courses</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-text">
        <h1>Learn New Skills Anywhere, Anytime</h1>
        <p>Join thousands of students learning from top instructors. Gain new skills and upgrade your career with our modern educational platform.</p>
        <a href="register.php" class="btn-primary">Get Started</a>
    </div>
    <div class="hero-img">
        <img src="assets\markus-winkler-wZsE5PzozIc-unsplash.jpg" alt="Learning Image">
    </div>
</section>

<h2 class="section-title">Popular Courses</h2>

<section class="courses">
    <div class="course-card">
        <h3>Introduction Private Security</h3>
        <p>Learn Security  </p>
    </div>
    <div class="course-card">
        <h3>Basic Guarding </h3>
        <p>Master report writing, Ob writing , and patrol techniques.</p>
    </div>
    <div class="course-card">
        <h3>Supervisor course</h3>
        <p>Grow your supervising skills.</p>
    </div>
    <div class="course-card">
        <h3>Technology </h3>
        <p>Create beautiful and intuitive user experiences.</p>
    </div>
</section>

<footer class="footer">© 2025 SecureLearn. All rights reserved.</footer>

</body>
</html>
