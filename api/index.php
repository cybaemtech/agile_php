<?php
// Simple PHP router for the API
require_once 'config/cors.php';

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remove the script name from the URI to get the path
$path = str_replace(dirname($scriptName), '', $requestUri);
$path = str_replace('/api', '', $path);

// Route requests to appropriate files
if (strpos($path, '/auth') === 0) {
    include 'auth.php';
} elseif (strpos($path, '/users') === 0) {
    include 'users.php';
} elseif (strpos($path, '/teams') === 0) {
    include 'teams.php';
} elseif (strpos($path, '/projects') === 0) {
    include 'projects.php';
} else {
    http_response_code(404);
    echo json_encode(['message' => 'API endpoint not found']);
}
?>