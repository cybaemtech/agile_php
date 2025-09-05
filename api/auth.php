<?php
require_once 'config/cors.php';
require_once 'config/database.php';

session_start();

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/php/auth', '', $path);
$path = str_replace('/api/auth', '', $path);

switch ($method . ':' . $path) {
    case 'POST:/login':
        login($conn);
        break;
    
    case 'POST:/logout':
        logout();
        break;
    
    case 'GET:/status':
        getAuthStatus();
        break;
    
    case 'GET:/user':
        getCurrentUser($conn);
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found']);
        break;
}

function login($conn) {
    // Read from stdin since data is piped from Node.js proxy
    $inputData = '';
    if (!feof(STDIN)) {
        $inputData = stream_get_contents(STDIN);
    }
    
    // Also try php://input as fallback
    if (empty($inputData)) {
        $inputData = file_get_contents('php://input');
    }
    
    $input = json_decode($inputData, true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Email and password are required']);
        return;
    }
    
    // If database connection failed, return error
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['message' => 'Database connection failed']);
        return;
    }
    
    try {
        // Determine the correct column name based on database type
        $roleColumn = 'user_role'; // Default for PostgreSQL and updated MySQL schema
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'User not found', 'email' => $input['email']]);
            return;
        }
        
        $passwordMatch = password_verify($input['password'], $user['password']);
        if (!$passwordMatch) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
            return;
        }
        
        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
        $updateStmt->execute([date('Y-m-d H:i:s'), $user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user[$roleColumn];
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullName' => $user['full_name'],
                'role' => $user[$roleColumn],
                'avatarUrl' => $user['avatar_url']
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function logout() {
    session_destroy();
    echo json_encode(['message' => 'Logged out successfully']);
}

function getAuthStatus() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'authenticated' => true,
            'userRole' => $_SESSION['user_role']
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}

function getCurrentUser($conn) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
        return;
    }
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['message' => 'Database connection failed']);
        return;
    }
    
    try {
        $roleColumn = 'user_role'; // Consistent with login function
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, user_role, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
            return;
        }
        
        echo json_encode([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullName' => $user['full_name'],
            'role' => $user[$roleColumn],
            'avatarUrl' => $user['avatar_url']
        ]);
        
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}
?>