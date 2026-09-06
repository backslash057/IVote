<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/controllers/authController.php";


// Try, load and verify the user datas from his cookies
$controller = new Authcontroller();
$userData = $controller->checkAuthentification();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deconnexion - Classroom</title>
    <meta charset="UTF-8">
    <style><?php include_once $_SERVER["DOCUMENT_ROOT"] . "/public/css/logout.css" ?></style>
    <style><?php include_once $_SERVER["DOCUMENT_ROOT"] . "/public/css/fonts.css" ?></style>
</head>
<body>
<?php
    if($userData == null) {
?>
        <div class="output">You are already disconnected</div>
        <a href='/' class="link">Return Home</a>
<?php
    }
    else {
?>
    <div class="output">
        You are connected as <b><?php echo $userData['email']; ?><br>
    </div>
    <button class="link button">Disconnect me</button>
    <a href='/' class="link home">Return Home</a>
    
    <script src="js/logout.js"></script>
<?php
    }
?>
</body>
</html>
