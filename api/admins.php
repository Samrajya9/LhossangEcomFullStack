
<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/api/admins.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../utils/adminFunctions.php';

// Get request method and parse input
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Get action from URL parameter or input
$action = $_GET['action'] ?? $input['action'] ?? '';
$id = $_GET['id'] ?? $input['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $id);
            break;
        case 'POST':
            handlePostRequest($action, $input);
            break;
        case 'PUT':
            handlePutRequest($action, $id, $input);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $id);
            break;
        default:
            errorResponse('Method not allowed', 405);
    }
} catch (Exception $e) {
    errorResponse('Server error: ' . $e->getMessage(), 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $id) {
    switch ($action) {
        case 'all':
            $admins = getAllAdmins();
            if (isset($admins['error'])) {
                errorResponse($admins['error']);
            }
            successResponse($admins, 'Admins retrieved successfully');
            break;
            
        case 'get':
            if (!$id) {
                errorResponse('Admin ID is required');
            }
            $admin = getAdminById($id);
            if (isset($admin['error'])) {
                errorResponse($admin['error']);
            }
            if (!$admin) {
                errorResponse('Admin not found', 404);
            }
            successResponse($admin, 'Admin retrieved successfully');
            break;
            
        case 'current':
            $admin = getCurrentAdmin();
            if (!$admin) {
                errorResponse('Not logged in', 401);
            }
            successResponse($admin, 'Current admin retrieved successfully');
            break;
            
        case 'check-auth':
            $isLoggedIn = isLoggedIn();
            successResponse(['logged_in' => $isLoggedIn], 'Auth status checked');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $input) {
    switch ($action) {
        case 'create':
            if (!$input) {
                errorResponse('Request data is required');
            }
            $result = createAdmin($input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Admin created successfully');
            break;
            
        case 'login':
            if (!$input || !isset($input['email']) || !isset($input['password'])) {
                errorResponse('Email and password are required');
            }
            $result = loginAdmin($input['email'], $input['password']);
            if (isset($result['error'])) {
                errorResponse($result['error'], 401);
            }
            successResponse($result, 'Login successful');
            break;
            
        case 'logout':
            $result = logoutAdmin();
            successResponse($result, 'Logout successful');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($action, $id, $input) {
    switch ($action) {
        case 'update':
            if (!$id) {
                errorResponse('Admin ID is required');
            }
            if (!$input) {
                errorResponse('Request data is required');
            }
            $result = updateAdmin($id, $input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Admin updated successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($action, $id) {
    switch ($action) {
        case 'delete':
            if (!$id) {
                errorResponse('Admin ID is required');
            }
            $result = deleteAdmin($id);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Admin deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
?>