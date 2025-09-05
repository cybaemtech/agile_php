<?php
require_once 'config/cors.php';
require_once 'config/database.php';

session_start();

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/php/users', '', $path);
$path = str_replace('/api/users', '', $path);

switch ($method . ':' . $path) {
    case 'GET:':
    case 'GET:/':
        getUsers($conn);
        break;
    
    case 'POST:':
    case 'POST:/':
        createUser($conn);
        break;
    
    default:
        if (preg_match('/^\/(\d+)$/', $path, $matches)) {
            $userId = $matches[1];
            if ($method === 'GET') {
                getUser($conn, $userId);
            }
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Endpoint not found']);
        }
        break;
}

function getUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, username, email, full_name, avatar_url, is_active, role, last_login, created_at, updated_at FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $users = array_map(function($user) {
            return [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullName' => $user['full_name'],
                'avatarUrl' => $user['avatar_url'],
                'isActive' => (bool)$user['is_active'],
                'role' => $user['role'],
                'lastLogin' => $user['last_login'],
                'createdAt' => $user['created_at'],
                'updatedAt' => $user['updated_at']
            ];
        }, $users);
        
        echo json_encode($users);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function getUser($conn, $userId) {
    try {
        $stmt = $conn->prepare("SELECT id, username, email, full_name, avatar_url, is_active, role, last_login, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
            return;
        }
        
        $user = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullName' => $user['full_name'],
            'avatarUrl' => $user['avatar_url'],
            'isActive' => (bool)$user['is_active'],
            'role' => $user['role'],
            'lastLogin' => $user['last_login'],
            'createdAt' => $user['created_at'],
            'updatedAt' => $user['updated_at']
        ];
        
        echo json_encode($user);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function createUser($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Username, email and password are required']);
        return;
    }
    
    try {
        $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, full_name, password, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['username'],
            $input['email'],
            $input['fullName'] ?? $input['username'],
            $hashedPassword,
            $input['role'] ?? 'USER'
        ]);
        
        $userId = $conn->lastInsertId();
        
        // Return created user without password
        getUser($conn, $userId);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['message' => 'User with this email or username already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Internal server error']);
        }
    }
}
?>