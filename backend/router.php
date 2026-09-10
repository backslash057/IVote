<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';

class Route {
    public function __construct(
        public string $uri,
        public string $target,
        public string $method = ""
    ) {}
}

$urlpatterns = [
    new Route("/", "/routes/home.php", "GET"),

    new Route("/campaigns", "/routes/campaign_list.php"),
    new Route("/my-campaigns", "/routes/my_campaigns.php"),
    
    // Dynamic URL parameters using {param} syntax
    new Route("/campaigns/{id}", "/routes/campaign_page.php", "GET"),
    new Route("/campaigns/{id}/votes", "CampaignController@recordVote", "POST"),
 
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

    // Convert route placeholder `{param}` into regex pattern `(?P<param>[^/]+)`
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route->uri);
    $pattern = "#^" . $pattern . "$#";

    if ($methodMatches && preg_match($pattern, $requestUri, $matches)) {
        $routeFound = true;

        // Filter out numeric keys from preg_match to get clean named parameters
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        if (str_ends_with($route->target, '.php')) {
            // Option 1: Expose params via global/REQUEST array for procedural PHP files
            $_GET = array_merge($_GET, $params);
            require_once $_SERVER["DOCUMENT_ROOT"] . $route->target;
        } else {
            // Option 2: Pass dynamic parameters into Controller method
            try {
                [$controllerName, $action] = explode('@', $route->target);
                $controller = new $controllerName();
                
                // Pass the extracted parameter array into controller action
                $response = call_user_func_array([$controller, $action], $params);

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