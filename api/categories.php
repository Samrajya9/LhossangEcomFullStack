<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../utils/categoryFunctions.php';

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
            $limit = $_GET['limit'] ?? null;
            $offset = $_GET['offset'] ?? 0;
            $categories = getAllCategories($limit, $offset);
            if (isset($categories['error'])) {
                errorResponse($categories['error']);
            }
            successResponse($categories, 'Categories retrieved successfully');
            break;
            
        case 'get':
            if (!$id) {
                errorResponse('Category ID is required');
            }
            $category = getCategoryById($id);
            if (isset($category['error'])) {
                errorResponse($category['error']);
            }
            if (!$category) {
                errorResponse('Category not found', 404);
            }
            successResponse($category, 'Category retrieved successfully');
            break;
            
        case 'with-products':
            $categories = getCategoriesWithProductCount();
            if (isset($categories['error'])) {
                errorResponse($categories['error']);
            }
            successResponse($categories, 'Categories with product count retrieved successfully');
            break;
            
        case 'search':
            $searchTerm = $_GET['q'] ?? $_GET['search'] ?? '';
            if (empty($searchTerm)) {
                errorResponse('Search term is required');
            }
            $categories = searchCategories($searchTerm);
            if (isset($categories['error'])) {
                errorResponse($categories['error']);
            }
            successResponse($categories, 'Search results retrieved successfully');
            break;
            
        case 'count':
            $count = getTotalCategoriesCount();
            successResponse(['count' => $count], 'Categories count retrieved successfully');
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
            $result = createCategory($input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Category created successfully');
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
                errorResponse('Category ID is required');
            }
            if (!$input) {
                errorResponse('Request data is required');
            }
            $result = updateCategory($id, $input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Category updated successfully');
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
                errorResponse('Category ID is required');
            }
            $result = deleteCategory($id);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Category deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
?>
