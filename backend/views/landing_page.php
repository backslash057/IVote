<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom | Home</title>

    <style><?php include_once "public/css/fonts.css" ?></style>
    <style><?php include_once "public/css/landing_page.css" ?></style>
    <link rel="stylesheet" href="css/landing_page.css">
</head>
<body>
    <nav class="menu">
        <a href="/" class="logo">
            <img src="imgs/logo.png" alt="Website logo">
        </a>
        <div class="menu-links">
            <a href="/login" class="link login">Log In</a>
            <a href="/signup" class="link signup">Sign Up</a> 
        </div>
    </nav>

    <div class="container">
        <img class="landing-image" src="imgs/home_design.png" alt="Illustration of school management app">
        
        <h1 class="main-text">Your School, Simplified.</h1>
        <h4 class="secondary-text">
            Manage your courses, track your progress, and stay connected with your school—all in one place.
        </h4>

        <div class="action-buttons">
            <a href="/signup" class="btn primary">Become a teacher</a>
            <a href="/signup" class="btn secondary">Get Started</a>
        </div>
    </div>
</body>
</html>
