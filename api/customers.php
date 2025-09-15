<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/api/customers.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../utils/customerFunctions.php';


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
            $customers = getAllCustomers($limit, $offset);
            if (isset($customers['error'])) {
                errorResponse($customers['error']);
            }
            successResponse($customers, 'Customers retrieved successfully');
            break;
            
        case 'get':
            if (!$id) {
                errorResponse('Customer ID is required');
            }
            $customer = getCustomerById($id);
            if (isset($customer['error'])) {
                errorResponse($customer['error']);
            }
            if (!$customer) {
                errorResponse('Customer not found', 404);
            }
            successResponse($customer, 'Customer retrieved successfully');
            break;
            
        case 'by-email':
            $email = $_GET['email'] ?? '';
            if (empty($email)) {
                errorResponse('Email is required');
            }
            $customer = getCustomerByEmail($email);
            if (isset($customer['error'])) {
                errorResponse($customer['error']);
            }
            if (!$customer) {
                errorResponse('Customer not found', 404);
            }
            successResponse($customer, 'Customer retrieved successfully');
            break;
            
        case 'with-stats':
            $limit = $_GET['limit'] ?? null;
            $offset = $_GET['offset'] ?? 0;
            $customers = getCustomersWithOrderStats($limit, $offset);
            if (isset($customers['error'])) {
                errorResponse($customers['error']);
            }
            successResponse($customers, 'Customers with statistics retrieved successfully');
            break;
            
        case 'orders':
            if (!$id) {
                errorResponse('Customer ID is required');
            }
            $limit = $_GET['limit'] ?? null;
            $offset = $_GET['offset'] ?? 0;
            $orders = getCustomerOrders($id, $limit, $offset);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Customer orders retrieved successfully');
            break;
            
        case 'search':
            $searchTerm = $_GET['q'] ?? $_GET['search'] ?? '';
            if (empty($searchTerm)) {
                errorResponse('Search term is required');
            }
            $customers = searchCustomers($searchTerm);
            if (isset($customers['error'])) {
                errorResponse($customers['error']);
            }
            successResponse($customers, 'Search results retrieved successfully');
            break;
            
        case 'by-location':
            $locationType = $_GET['type'] ?? '';
            $locationValue = $_GET['value'] ?? '';
            if (empty($locationType) || empty($locationValue)) {
                errorResponse('Location type and value are required');
            }
            $customers = getCustomersByLocation($locationType, $locationValue);
            if (isset($customers['error'])) {
                errorResponse($customers['error']);
            }
            successResponse($customers, 'Customers by location retrieved successfully');
            break;
            
        case 'top-spenders':
            $limit = $_GET['limit'] ?? 10;
            $customers = getTopCustomersBySpending($limit);
            if (isset($customers['error'])) {
                errorResponse($customers['error']);
            }
            successResponse($customers, 'Top spending customers retrieved successfully');
            break;
            
        case 'count':
            $count = getTotalCustomersCount();
            successResponse(['count' => $count], 'Customers count retrieved successfully');
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
            $result = createCustomer($input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Customer created successfully');
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
                errorResponse('Customer ID is required');
            }
            if (!$input) {
                errorResponse('Request data is required');
            }
            $result = updateCustomer($id, $input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Customer updated successfully');
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
                errorResponse('Customer ID is required');
            }
            $result = deleteCustomer($id);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Customer deleted successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
?>