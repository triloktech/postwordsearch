<?php 

require_once __DIR__."/api/PostController.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// all of our endpoints start with /person
// everything else results in a 404 Not Found
if ($uri[2] !== 'post') {
    echo "Url not found";
    header("HTTP/1.1 404 Not Found");
    exit();
}

// the search string, may be optional and must be a string:
$search = null;
if (isset($uri[4])) {
    $search = (string) $uri[4];
}

$method = null;
if (isset($uri[3])) {
    $method = (string) $uri[3];
}

$httpMethod = $_SERVER["REQUEST_METHOD"];

// pass the method name, request method and user ID to the PostController and process the HTTP request:
// $controller = new PostController($dbConnection, $requestMethod, $userId);
$controller = new PostController();
$controller->processRequest($method, $httpMethod, $search);

?>