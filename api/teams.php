<?php
require_once 'config/cors.php';
require_once 'config/database.php';

session_start();

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/php/teams', '', $path);
$path = str_replace('/api/teams', '', $path);

switch ($method . ':' . $path) {
    case 'GET:':
    case 'GET:/':
        getTeams($conn);
        break;
    
    case 'POST:':
    case 'POST:/':
        createTeam($conn);
        break;
    
    default:
        if (preg_match('/^\/(\d+)$/', $path, $matches)) {
            $teamId = $matches[1];
            if ($method === 'GET') {
                getTeam($conn, $teamId);
            }
        } elseif (preg_match('/^\/(\d+)\/members$/', $path, $matches)) {
            $teamId = $matches[1];
            if ($method === 'GET') {
                getTeamMembers($conn, $teamId);
            } elseif ($method === 'POST') {
                addTeamMember($conn, $teamId);
            }
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Endpoint not found']);
        }
        break;
}

function getTeams($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM teams ORDER BY created_at DESC");
        $stmt->execute();
        $teams = $stmt->fetchAll();
        
        $teams = array_map(function($team) {
            return [
                'id' => (int)$team['id'],
                'name' => $team['name'],
                'description' => $team['description'],
                'createdBy' => (int)$team['created_by'],
                'isActive' => (bool)$team['is_active'],
                'createdAt' => $team['created_at'],
                'updatedAt' => $team['updated_at']
            ];
        }, $teams);
        
        echo json_encode($teams);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function getTeam($conn, $teamId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();
        
        if (!$team) {
            http_response_code(404);
            echo json_encode(['message' => 'Team not found']);
            return;
        }
        
        $team = [
            'id' => (int)$team['id'],
            'name' => $team['name'],
            'description' => $team['description'],
            'createdBy' => (int)$team['created_by'],
            'isActive' => (bool)$team['is_active'],
            'createdAt' => $team['created_at'],
            'updatedAt' => $team['updated_at']
        ];
        
        echo json_encode($team);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function createTeam($conn) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Team name is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO teams (name, description, created_by) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'] ?? null,
            $_SESSION['user_id']
        ]);
        
        $teamId = $conn->lastInsertId();
        
        // Return created team
        getTeam($conn, $teamId);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function getTeamMembers($conn, $teamId) {
    try {
        $stmt = $conn->prepare("
            SELECT tm.*, u.username, u.email, u.full_name, u.avatar_url 
            FROM team_members tm 
            JOIN users u ON tm.user_id = u.id 
            WHERE tm.team_id = ?
        ");
        $stmt->execute([$teamId]);
        $members = $stmt->fetchAll();
        
        $members = array_map(function($member) {
            return [
                'id' => (int)$member['id'],
                'teamId' => (int)$member['team_id'],
                'userId' => (int)$member['user_id'],
                'role' => $member['role'],
                'joinedAt' => $member['joined_at'],
                'updatedAt' => $member['updated_at'],
                'user' => [
                    'id' => (int)$member['user_id'],
                    'username' => $member['username'],
                    'email' => $member['email'],
                    'fullName' => $member['full_name'],
                    'avatarUrl' => $member['avatar_url']
                ]
            ];
        }, $members);
        
        echo json_encode($members);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function addTeamMember($conn, $teamId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['userId'])) {
        http_response_code(400);
        echo json_encode(['message' => 'User ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO team_members (team_id, user_id, role) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $teamId,
            $input['userId'],
            $input['role'] ?? 'MEMBER'
        ]);
        
        $memberId = $conn->lastInsertId();
        
        // Return the new member with user details
        $getStmt = $conn->prepare("
            SELECT tm.*, u.username, u.email, u.full_name, u.avatar_url 
            FROM team_members tm 
            JOIN users u ON tm.user_id = u.id 
            WHERE tm.id = ?
        ");
        $getStmt->execute([$memberId]);
        $member = $getStmt->fetch();
        
        echo json_encode([
            'id' => (int)$member['id'],
            'teamId' => (int)$member['team_id'],
            'userId' => (int)$member['user_id'],
            'role' => $member['role'],
            'joinedAt' => $member['joined_at'],
            'updatedAt' => $member['updated_at'],
            'user' => [
                'id' => (int)$member['user_id'],
                'username' => $member['username'],
                'email' => $member['email'],
                'fullName' => $member['full_name'],
                'avatarUrl' => $member['avatar_url']
            ]
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['message' => 'User is already a member of this team']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Internal server error']);
        }
    }
}
?>