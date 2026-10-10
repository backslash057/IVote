<?php

require_once __DIR__ . '/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/http.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AuthController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CampaignController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CategoryController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/CandidateController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/VoteController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/PayoutController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/AdminController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/NotificationController.php';

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
    new Route("/admin", "/routes/admin.php", "GET"),
    new Route("/admin/notifications", "/routes/notifications.php", "GET"),
    new Route("/notifications", "/routes/notifications.php", "GET"),
    new Route("/admin/transactions.csv", "/routes/admin_export.php", "GET"),
    new Route("/admin/payouts.csv", "/routes/admin_export.php", "GET"),
    new Route("/login", "/routes/auth/login.php", "GET"),
    new Route("/signup", "/routes/auth/signup.php", "GET"),
    new Route("/logout", "/routes/auth/logout.php", "GET"),

    // API: System & Health
    new Route("/api/status", "CampaignController@status", "GET"),
    
    // API: Authentication & User data
    new Route("/api/auth/login", "AuthController@login", "POST"),
    new Route("/api/auth/signup", "AuthController@signup", "POST"),
    new Route("/api/auth/logout", "AuthController@logout", "POST"),
    new Route("/api/auth/me", "AuthController@me", "GET"),
    new Route("/api/auth/profile", "AuthController@updateProfile", "POST"),
    new Route("/api/auth/stats", "AuthController@stats", "GET"),

    // API: Campaigns
    new Route("/api/campaigns", "CampaignController@index", "GET"),
    new Route("/api/campaigns", "CampaignController@create", "POST"),
    new Route("/api/my-campaigns", "CampaignController@myCampaigns", "GET"),
    new Route("/api/campaigns/{id}", "CampaignController@show", "GET"),
    new Route("/api/campaigns/{id}/dashboard", "CampaignController@dashboard", "GET"),
    new Route("/api/campaigns/{id}/update", "CampaignController@update", "POST"),
    new Route("/api/campaigns/{id}", "CampaignController@delete", "DELETE"),
    new Route("/api/campaigns/{id}/status", "CampaignController@updateStatus", "POST"),
    new Route("/api/campaigns/{id}/relaunch", "CampaignController@relaunch", "POST"),
    new Route("/api/campaigns/{id}/update-photo", "CampaignController@updatePhoto", "POST"),

    // API: Categories
    new Route("/api/campaigns/{id}/categories", "CategoryController@index", "GET"),
    new Route("/api/campaigns/{id}/categories", "CategoryController@store", "POST"),
    new Route("/api/categories/{id}", "CategoryController@delete", "DELETE"),
    new Route("/api/categories/{id}", "CategoryController@update", "PUT"),

    // API: Candidates
    new Route("/api/campaigns/{id}/candidates", "CandidateController@index", "GET"),
    new Route("/api/campaigns/{id}/candidates", "CandidateController@store", "POST"),
    new Route("/api/candidates/{id}", "CandidateController@update", "PUT"),
    new Route("/api/candidates/{id}", "CandidateController@delete", "DELETE"),

    // API: Votes
    new Route("/api/campaigns/{id}/votes", "VoteController@index", "GET"),
    new Route("/api/campaigns/{id}/votes", "VoteController@recordVote", "POST"),

    // API: Payouts
    new Route("/api/campaigns/{id}/payouts", "PayoutController@index", "GET"),
    new Route("/api/campaigns/{id}/payouts", "PayoutController@store", "POST"),
    new Route("/api/organizer/revenues", "PayoutController@organizerRevenues", "GET"),

    // API: Super administration
    new Route("/api/admin/overview", "AdminController@overview", "GET"),
    new Route("/api/admin/users", "AdminController@users", "GET"),
    new Route("/api/admin/users/{id}/status", "AdminController@updateUserStatus", "POST"),
    new Route("/api/admin/campaigns", "AdminController@campaigns", "GET"),
    new Route("/api/admin/campaigns/{id}/moderate", "AdminController@moderateCampaign", "POST"),
    new Route("/api/admin/payouts", "AdminController@payouts", "GET"),
    new Route("/api/admin/payouts/{id}/review", "AdminController@reviewPayout", "POST"),
    new Route("/api/admin/notifications", "AdminController@sendNotification", "POST"),

    // API: Notifications
    new Route("/api/notifications", "NotificationController@index", "GET"),
    new Route("/api/notifications/unread-count", "NotificationController@unreadCount", "GET"),
    new Route("/api/notifications/read-all", "NotificationController@markAllRead", "POST"),
    new Route("/api/notifications/{id}/read", "NotificationController@markRead", "POST"),
];

$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Handle Preflight OPTIONS for CORS
if (http_handle_cors_preflight()) {
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
            http_send_cors_headers();

            try {
                [$controllerName, $action] = explode('@', $route->target);
                $controller = new $controllerName();
                $response = call_user_func_array([$controller, $action], $params);

                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (Throwable $e) {
                http_response_code(500);
                error_log('[IVote] Erreur API non gérée sur ' . $method . ' ' . $requestUri . ' : '
                    . get_class($e) . ': ' . $e->getMessage()
                    . ' à ' . $e->getFile() . ':' . $e->getLine());
                echo json_encode([
                    "success" => false,
                    "message" => "Une erreur interne est survenue.",
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        break;
    }
}

if (!$routeFound) {
    // Résolution réelle du chemin : interdit toute traversée (../) hors des
    // deux seuls répertoires publics autorisés.
    $documentRoot = realpath($_SERVER["DOCUMENT_ROOT"]);
    $staticFilePath = $documentRoot !== false ? realpath($documentRoot . $requestUri) : false;

    $allowedDirectories = [];
    if ($documentRoot !== false) {
        foreach (['/public', '/uploads'] as $subDirectory) {
            $resolved = realpath($documentRoot . $subDirectory);
            if ($resolved !== false) {
                $allowedDirectories[] = $resolved . DIRECTORY_SEPARATOR;
            }
        }
    }

    $isAllowedFile = false;
    if ($staticFilePath !== false && is_file($staticFilePath)) {
        foreach ($allowedDirectories as $directory) {
            if (str_starts_with($staticFilePath, $directory)) {
                $isAllowedFile = true;
                break;
            }
        }
    }

    if (
        $method === "GET" &&
        (str_starts_with($requestUri, "/public/") || str_starts_with($requestUri, "/uploads/")) &&
        $isAllowedFile
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
            http_send_cors_headers();
            echo json_encode(["success" => false, "message" => "Point d'entrée API introuvable."]);
        } else {
            require_once $_SERVER["DOCUMENT_ROOT"] . "/routes/404.html";
        }
    }
}