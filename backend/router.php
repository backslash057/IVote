<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CampaignController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CategoryController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CandidateController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/VoteController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/PayoutController.php';

class Route {
    public function __construct(
        public string $uri,
        public string $target,
        public string $method = ""
    ) {}
}

$urlpatterns = [
    // Web Views (HTML Pages)
    new Route("/", "/routes/home.php", "GET"),
    new Route("/campaigns", "/routes/campaigns.php", "GET"),
    new Route("/campaigns/new", "/routes/new_campaign.php", "GET"),
    new Route("/campaigns/{id}", "/routes/campaign_page.php", "GET"),
    new Route("/campaigns/{id}/dashboard", "/routes/campaign_dashboard.php", "GET"),
    new Route("/dashboard", "/routes/dashboard.php", "GET"),
    new Route("/login", "/routes/auth/login.php", "GET"),
    new Route("/signup", "/routes/auth/signup.php", "GET"),
    new Route("/logout", "/routes/auth/logout.php", "GET"),

    // API: System & Health
    new Route("/api/status", "CampaignController@status", "GET"),

    // API: Authentication
    new Route("/api/auth/login", "AuthController@login", "POST"),
    new Route("/api/auth/signup", "AuthController@signup", "POST"),
    new Route("/api/auth/logout", "AuthController@logout", "POST"),
    new Route("/api/auth/me", "AuthController@me", "GET"),

    // API: Campaigns
    new Route("/api/campaigns", "CampaignController@index", "GET"),
    new Route("/api/campaigns", "CampaignController@create", "POST"),
    new Route("/api/my-campaigns", "CampaignController@myCampaigns", "GET"),
    new Route("/api/campaigns/{id}", "CampaignController@show", "GET"),
    new Route("/api/campaigns/{id}/dashboard", "CampaignController@dashboard", "GET"),
    new Route("/api/campaigns/{id}/update", "CampaignController@update", "POST"),
    new Route("/api/campaigns/{id}/status", "CampaignController@updateStatus", "POST"),
    new Route("/api/campaigns/{id}/relaunch", "CampaignController@relaunch", "POST"),
    new Route("/api/campaigns/{id}/update-photo", "CampaignController@updatePhoto", "POST"),

    // API: Categories
    new Route("/api/campaigns/{id}/categories", "CategoryController@index", "GET"),
    new Route("/api/campaigns/{id}/categories", "CategoryController@store", "POST"),

    // API: Candidates
    new Route("/api/campaigns/{id}/candidates", "CandidateController@index", "GET"),
    new Route("/api/campaigns/{id}/candidates", "CandidateController@store", "POST"),

    // API: Votes
    new Route("/api/campaigns/{id}/votes", "VoteController@index", "GET"),
    new Route("/api/campaigns/{id}/votes", "VoteController@recordVote", "POST"),

    // API: Payouts
    new Route("/api/campaigns/{id}/payouts", "PayoutController@index", "GET"),
    new Route("/api/campaigns/{id}/payouts", "PayoutController@store", "POST"),
];

$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Handle Preflight OPTIONS for CORS
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

    // Convert route placeholder `{param}` to regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route->uri);
    $pattern = "#^" . $pattern . "$#";

    if ($methodMatches && preg_match($pattern, $requestUri, $matches)) {
        $routeFound = true;
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        if (str_ends_with($route->target, '.php')) {
            // Render PHP View
            $_GET = array_merge($_GET, $params);
            require_once $_SERVER["DOCUMENT_ROOT"] . $route->target;
        } else {
            // API Action Execution
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');

            try {
                [$controllerName, $action] = explode('@', $route->target);
                $controller = new $controllerName();
                $response = call_user_func_array([$controller, $action], $params);

                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Une erreur interne est survenue.",
                    "error"   => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        break;
    }
}

if (!$routeFound) {
    $staticFilePath = $_SERVER["DOCUMENT_ROOT"] . $requestUri;

    if (
        $method === "GET" && 
        (str_starts_with($requestUri, "/public/") || str_starts_with($requestUri, "/uploads/")) &&
        is_file($staticFilePath)
    ) {
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'html' => 'text/html',
            'htm'  => 'text/html',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
            'pdf'  => 'application/pdf',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'txt'  => 'text/plain',
        ];

        $extension = strtolower(pathinfo($staticFilePath, PATHINFO_EXTENSION));

        $mime = $mimeTypes[$extension] ?? mime_content_type($staticFilePath);

        header("Content-Type: $mime");
        readfile($staticFilePath);
        exit;
    } else {
        http_response_code(404);
        if (str_starts_with($requestUri, '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode(["success" => false, "message" => "Point d'entrée API introuvable."]);
        } else {
            require_once $_SERVER["DOCUMENT_ROOT"] . "/routes/404.html";
        }
    }
}