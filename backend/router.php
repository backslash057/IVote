<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';

class Route {
    public function __construct(
        public string $uri,
        public string $target,
        public string $method = ""
    ) {}
}

$urlpatterns = [
    new Route("/", "/routes/home.php", "GET"),

    new Route("/login", "/routes/auth/login.php", "GET"),
    new Route("/login", "AuthController@login", "POST"),

    new Route("/signup", "/routes/auth/signup.php", "GET"),
    new Route("/signup", "AuthController@signup", "POST"),
];

$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];
$routeFound = false;

foreach ($urlpatterns as $route) {
    $methodMatches = empty($route->method) || strtoupper($route->method) === $method;

    if ($route->uri === $requestUri && $methodMatches) {
        $routeFound = true;

        if (str_ends_with($route->target, '.php')) {
            require_once $_SERVER["DOCUMENT_ROOT"] . $route->target;
        } else {
            try {
                [$controllerName, $action] = explode('@', $route->target);
                $controller = new $controllerName();
                $response = $controller->$action();

                header("Content-Type: application/json");
                echo json_encode($response);
            } catch (Exception $e) {
                http_response_code(500);
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "message" => "An internal error occurred.",
                    "error"   => $e->getMessage(),
                ]);
            }
        }

        break;
    }
}

if (!$routeFound) {
    $staticFilePath = $_SERVER["DOCUMENT_ROOT"] . $requestUri;

    if ($method === "GET" && str_starts_with($requestUri, "/public/") && is_file($staticFilePath)) {
        include_once $staticFilePath;
    } else {
        http_response_code(404);
        require_once $_SERVER["DOCUMENT_ROOT"] . "/views/404.html";
    }
}

?>