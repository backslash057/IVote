<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/controllers/authController.php";

// Try, load and verify the user data from cookies
$controller = new Authcontroller();
$userData = $controller->checkAuthentification();

if ($userData) {
    require_once $_SERVER["DOCUMENT_ROOT"] . "/views/organizer_dashboard.php";
} else {
    require_once $_SERVER["DOCUMENT_ROOT"] . "/views/landing_page.php";
}

?>