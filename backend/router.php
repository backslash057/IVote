<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/authController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/campaignController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/apiController.php';

class Route {
    public function __construct(
        public string $uri,
        public string $target,
        public string $method = ""
    ) {}
}

$urlpatterns = [
    new Route("/", "/routes/home.php", "GET"),

    new Route("/campaigns", "/routes/campaigns.php"),
    new Route("/dashboard", "/routes/dashboard.php"),
    
    new Route("/campaigns/new", "/routes/new_campaign.php", "GET"),
    new Route("/campaigns/new", "CampaignController@createCampaign", "POST"),

    // Routes Campagne & Tableau de bord
    new Route("/campaigns/{id}", "/routes/campaign_page.php", "GET"),
    new Route("/campaigns/{id}/votes", "CampaignController@recordVote", "POST"),
    new Route("/campaigns/{id}/dashboard", "/routes/campaign_dashboard.php", "GET"),
    new Route("/campaigns/{id}/update", "CampaignController@updateCampaign", "POST"),
    new Route("/campaigns/{id}/status", "CampaignController@updateStatus", "POST"),
    new Route("/campaigns/{id}/relaunch", "CampaignController@relaunch", "POST"),
    new Route("/campaigns/{id}/update-photo", "CampaignController@updatePhoto", "POST"),
    new Route("/campaigns/{id}/candidates", "CampaignController@addCandidate", "POST"),
    new Route("/campaigns/{id}/payouts", "CampaignController@createPayout", "POST"),
 
    new Route("/login", "/routes/auth/login.php", "GET"),
    new Route("/login", "AuthController@login", "POST"),
    
    new Route("/logout", "/routes/auth/logout.php", "GET"),
    new Route("/logout", "AuthController@logout", "POST"),

    new Route("/signup", "/routes/auth/signup.php", "GET"),
    new Route("/signup", "AuthController@signup", "POST"),

    // API REST (mobile)
    new Route("/api/status", "ApiController@status", "GET"),
    new Route("/api/campaigns/popular", "ApiController@popular", "GET"),
    new Route("/api/campaigns", "ApiController@campaigns", "GET"),
    new Route("/api/campaigns", "ApiController@create", "POST"),
    new Route("/api/my-campaigns", "ApiController@myCampaigns", "GET"),
    new Route("/api/auth/login", "ApiController@login", "POST"),
    new Route("/api/auth/signup", "ApiController@signup", "POST"),
    new Route("/api/auth/me", "ApiController@me", "GET"),
    new Route("/api/campaigns/{id}/dashboard", "ApiController@dashboard", "GET"),
    new Route("/api/campaigns/{id}/votes", "ApiController@votes", "POST"),
    new Route("/api/campaigns/{id}/update", "ApiController@update", "POST"),
    new Route("/api/campaigns/{id}/status", "ApiController@updateStatus", "POST"),
    new Route("/api/campaigns/{id}/relaunch", "ApiController@relaunch", "POST"),
    new Route("/api/campaigns/{id}/candidates", "ApiController@candidates", "POST"),
    new Route("/api/campaigns/{id}/payouts", "ApiController@payouts", "POST"),
    new Route("/api/campaigns/{id}", "ApiController@show", "GET"),
];

$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS' && str_starts_with($requestUri, '/api/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

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
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        require_once $_SERVER["DOCUMENT_ROOT"] . "/routes/404.html";
    }
}