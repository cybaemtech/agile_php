<?php
require_once 'config/cors.php';
require_once 'config/database.php';

session_start();

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/php/projects', '', $path);
$path = str_replace('/api/projects', '', $path);

switch ($method . ':' . $path) {
    case 'GET:':
    case 'GET:/':
        getProjects($conn);
        break;
    
    case 'POST:':
    case 'POST:/':
        createProject($conn);
        break;
    
    default:
        if (preg_match('/^\/(\d+)$/', $path, $matches)) {
            $projectId = $matches[1];
            switch ($method) {
                case 'GET':
                    getProject($conn, $projectId);
                    break;
                case 'PATCH':
                    updateProject($conn, $projectId);
                    break;
                case 'DELETE':
                    deleteProject($conn, $projectId);
                    break;
            }
        } elseif (preg_match('/^\/(\d+)\/work-items$/', $path, $matches)) {
            $projectId = $matches[1];
            if ($method === 'GET') {
                getWorkItems($conn, $projectId);
            }
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Endpoint not found']);
        }
        break;
}

function getProjects($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM projects ORDER BY created_at DESC");
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        // Convert dates and format data
        $projects = array_map(function($project) {
            return [
                'id' => (int)$project['id'],
                'key' => $project['project_key'],
                'name' => $project['name'],
                'description' => $project['description'],
                'status' => $project['status'],
                'createdBy' => (int)$project['created_by'],
                'teamId' => $project['team_id'] ? (int)$project['team_id'] : null,
                'startDate' => $project['start_date'],
                'targetDate' => $project['target_date'],
                'createdAt' => $project['created_at'],
                'updatedAt' => $project['updated_at']
            ];
        }, $projects);
        
        echo json_encode($projects);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function getProject($conn, $projectId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        if (!$project) {
            http_response_code(404);
            echo json_encode(['message' => 'Project not found']);
            return;
        }
        
        $project = [
            'id' => (int)$project['id'],
            'key' => $project['project_key'],
            'name' => $project['name'],
            'description' => $project['description'],
            'status' => $project['status'],
            'createdBy' => (int)$project['created_by'],
            'teamId' => $project['team_id'] ? (int)$project['team_id'] : null,
            'startDate' => $project['start_date'],
            'targetDate' => $project['target_date'],
            'createdAt' => $project['created_at'],
            'updatedAt' => $project['updated_at']
        ];
        
        echo json_encode($project);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function createProject($conn) {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['key'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Project name and key are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO projects (project_key, name, description, created_by, team_id, start_date, target_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['key'],
            $input['name'],
            $input['description'] ?? null,
            $input['createdBy'] ?? $_SESSION['user_id'],
            $input['teamId'] ?? null,
            $input['startDate'] ?? null,
            $input['targetDate'] ?? null
        ]);
        
        $projectId = $conn->lastInsertId();
        
        // Fetch the created project
        $getStmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
        $getStmt->execute([$projectId]);
        $project = $getStmt->fetch();
        
        echo json_encode([
            'id' => (int)$project['id'],
            'key' => $project['project_key'],
            'name' => $project['name'],
            'description' => $project['description'],
            'status' => $project['status'],
            'createdBy' => (int)$project['created_by'],
            'teamId' => $project['team_id'] ? (int)$project['team_id'] : null,
            'startDate' => $project['start_date'],
            'targetDate' => $project['target_date'],
            'createdAt' => $project['created_at'],
            'updatedAt' => $project['updated_at']
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Unique constraint violation
            http_response_code(409);
            echo json_encode(['message' => 'Project key already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Internal server error']);
        }
    }
}

function getWorkItems($conn, $projectId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM work_items WHERE project_id = ? ORDER BY created_at DESC");
        $stmt->execute([$projectId]);
        $workItems = $stmt->fetchAll();
        
        $workItems = array_map(function($item) {
            return [
                'id' => (int)$item['id'],
                'externalId' => $item['external_id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'type' => $item['type'],
                'status' => $item['status'],
                'priority' => $item['priority'],
                'projectId' => (int)$item['project_id'],
                'parentId' => $item['parent_id'] ? (int)$item['parent_id'] : null,
                'assigneeId' => $item['assignee_id'] ? (int)$item['assignee_id'] : null,
                'reporterId' => $item['reporter_id'] ? (int)$item['reporter_id'] : null,
                'estimate' => $item['estimate'],
                'startDate' => $item['start_date'],
                'endDate' => $item['end_date'],
                'completedAt' => $item['completed_at'],
                'createdAt' => $item['created_at'],
                'updatedAt' => $item['updated_at']
            ];
        }, $workItems);
        
        echo json_encode($workItems);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function updateProject($conn, $projectId) {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if project exists
        $checkStmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
        $checkStmt->execute([$projectId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['message' => 'Project not found']);
            return;
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = [];
        
        if (isset($input['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $input['name'];
        }
        if (isset($input['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $input['description'];
        }
        if (isset($input['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $input['status'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'No fields to update']);
            return;
        }
        
        $updateFields[] = "updated_at = ?";
        $params[] = date('Y-m-d H:i:s');
        $params[] = $projectId;
        
        $sql = "UPDATE projects SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Return updated project
        getProject($conn, $projectId);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}

function deleteProject($conn, $projectId) {
    // Check authentication and admin role
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['message' => 'Access denied']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['message' => 'Project not found']);
            return;
        }
        
        http_response_code(204);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Internal server error']);
    }
}
?>