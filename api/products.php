<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/api/products.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../utils/productFunctions.php';

// Get request method and parse input
$method = $_SERVER['REQUEST_METHOD'];
// $input = json_decode(file_get_contents('php://input'), true);

// Get action from URL parameter or input
$action = $_GET['action'] ?? $input['action'] ?? '';
$id = $_GET['id'] ?? $input['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // Input for GET is from $_GET, which is fine
            handleGetRequest($action, $id);
            break;
        case 'POST':
            // For POST with multipart/form-data, use $_POST and $_FILES
            handlePostRequest($action, $_POST, $_FILES);
            break;
        case 'PUT':
             // Note: Standard PUT requests don't populate $_POST. 
             // We will handle updates via POST method as the frontend form does.
             // This is a common practice for forms with file uploads.
            errorResponse('Use POST method with action=update for updates.', 405);
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
    // ... (Your existing GET handler code remains the same)
    switch ($action) {
        case 'all':
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            $categoryId = $_GET['category_id'] ?? null;
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] !== 'false' ? (bool)$_GET['active_only'] : null;
            $searchTerm = $_GET['q'] ?? null;

            $result = getAllProducts($limit, $offset, $categoryId, $activeOnly, $searchTerm);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            // Also get total count for pagination
            $total = getTotalProductsCount($categoryId, $activeOnly, $searchTerm);
            successResponse(['data' => $result, 'total' => $total], 'Products retrieved successfully');
            break;
            
        case 'get':
            if (!$id) { errorResponse('Product ID is required'); }
            $product = getProductById($id);
            if (isset($product['error'])) { errorResponse($product['error']); }
            if (!$product) { errorResponse('Product not found', 404); }
            successResponse($product, 'Product retrieved successfully');
            break;
          case 'count':
            $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;
            $count = getTotalProductsCount($activeOnly);
            // Ensure the response format is consistent with other count endpoints
            successResponse(['count' => $count], 'Products count retrieved successfully');
            break;
        // ... other GET cases

         case 'get_multiple':
            if (!isset($_GET['ids']) || empty($_GET['ids'])) {
                errorResponse('Product IDs are required');
                return;
            }
            // Sanitize IDs from the comma-separated string.
            $ids = array_map('intval', explode(',', $_GET['ids']));
            $products = getProductsByIds($ids);
            
            if (isset($products['error'])) {
                errorResponse($products['error']);
            }
            successResponse($products, 'Products retrieved successfully');
            break;
        default:
            errorResponse('Invalid GET action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $postData, $filesData) {
    switch ($action) {
        case 'create':
            $result = createProduct($postData, $filesData);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Product created successfully');
            break;
        
        case 'update':
            $id = $_GET['id'] ?? $postData['id'] ?? null;
            if (!$id) {
                errorResponse('Product ID is required for update');
            }
            $result = updateProduct($id, $postData, $filesData);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Product updated successfully');
            break;
            
        case 'update-stock':
            // This can remain as is if it's called with JSON
             $input = json_decode(file_get_contents('php://input'), true) ?? $postData;
            if (!isset($input['product_id']) || !isset($input['quantity'])) {
                errorResponse('Product ID and quantity are required');
            }
            $operation = $input['operation'] ?? 'set';
            $result = updateProductStock($input['product_id'], $input['quantity'], $operation);
            if (isset($result['error'])) { errorResponse($result['error']); }
            successResponse($result, 'Product stock updated successfully');
            break;
            
        default:
            errorResponse('Invalid POST action');
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($action, $id) {
    // ... (Your existing DELETE handler code remains the same)
    switch ($action) {
        case 'delete':
            if (!$id) {
                errorResponse('Product ID is required');
            }
            $result = deleteProduct($id);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Product deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
?>
?>