<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/api/orders.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../utils/orderFunctions.php';

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
            $status = $_GET['status'] ?? null;
            $orders = getAllOrders($limit, $offset, $status);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Orders retrieved successfully');
            break;
            
        case 'get':
            if (!$id) {
                errorResponse('Order ID is required');
            }
            $order = getOrderById($id);
            if (isset($order['error'])) {
                errorResponse($order['error']);
            }
            if (!$order) {
                errorResponse('Order not found', 404);
            }
            successResponse($order, 'Order retrieved successfully');
            break;
            
        case 'details':
            if (!$id) {
                errorResponse('Order ID is required');
            }
            $orderDetails = getOrderDetails($id);
            if (isset($orderDetails['error'])) {
                errorResponse($orderDetails['error']);
            }
            successResponse($orderDetails, 'Order details retrieved successfully');
            break;
            
        case 'items':
            if (!$id) {
                errorResponse('Order ID is required');
            }
            $items = getOrderItems($id);
            if (isset($items['error'])) {
                errorResponse($items['error']);
            }
            successResponse($items, 'Order items retrieved successfully');
            break;
            
        case 'by-status':
            $status = $_GET['status'] ?? '';
            if (empty($status)) {
                errorResponse('Status is required');
            }
            $orders = getOrdersByStatus($status);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Orders by status retrieved successfully');
            break;
            
        case 'by-date-range':
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';
            if (empty($startDate) || empty($endDate)) {
                errorResponse('Start date and end date are required');
            }
            $status = $_GET['status'] ?? null;
            $orders = getOrdersByDateRange($startDate, $endDate, $status);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Orders by date range retrieved successfully');
            break;
            
        case 'search':
            $searchTerm = $_GET['q'] ?? $_GET['search'] ?? '';
            if (empty($searchTerm)) {
                errorResponse('Search term is required');
            }
            $status = $_GET['status'] ?? null;
            $orders = searchOrders($searchTerm, $status);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Search results retrieved successfully');
            break;
            
        case 'statistics':
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $stats = getOrderStatistics($startDate, $endDate);
            if (isset($stats['error'])) {
                errorResponse($stats['error']);
            }
            successResponse($stats, 'Order statistics retrieved successfully');
            break;
            
        case 'recent':
            $limit = $_GET['limit'] ?? 10;
            $orders = getRecentOrders($limit);
            if (isset($orders['error'])) {
                errorResponse($orders['error']);
            }
            successResponse($orders, 'Recent orders retrieved successfully');
            break;
            
        case 'top-products':
            $limit = $_GET['limit'] ?? 10;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $products = getTopSellingProducts($limit, $startDate, $endDate);
            if (isset($products['error'])) {
                errorResponse($products['error']);
            }
            successResponse($products, 'Top selling products retrieved successfully');
            break;
            
        case 'monthly-sales':
            $year = $_GET['year'] ?? null;
            $salesData = getMonthlySalesData($year);
            if (isset($salesData['error'])) {
                errorResponse($salesData['error']);
            }
            successResponse($salesData, 'Monthly sales data retrieved successfully');
            break;
            
        case 'count':
            $status = $_GET['status'] ?? null;
            $count = getTotalOrdersCount($status);
            successResponse(['count' => $count], 'Orders count retrieved successfully');
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
            $result = createOrder($input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Order created successfully');
            break;
            
        case 'add-item':
            if (!$input || !isset($input['order_id']) || !isset($input['product_id']) || 
                !isset($input['quantity']) || !isset($input['price'])) {
                errorResponse('Order ID, product ID, quantity, and price are required');
            }
            $result = addOrderItem($input['order_id'], $input['product_id'], 
                                 $input['quantity'], $input['price']);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Item added to order successfully');
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
                errorResponse('Order ID is required');
            }
            if (!$input) {
                errorResponse('Request data is required');
            }
            $result = updateOrder($id, $input);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Order updated successfully');
            break;
            
        case 'update-status':
            if (!$id) {
                errorResponse('Order ID is required');
            }
            if (!$input || !isset($input['status'])) {
                errorResponse('Status is required');
            }
            $result = updateOrderStatus($id, $input['status']);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Order status updated successfully');
            break;
            
        case 'cancel':
            if (!$id) {
                errorResponse('Order ID is required');
            }
            $restoreStock = isset($input['restore_stock']) ? (bool)$input['restore_stock'] : true;
            $result = cancelOrder($id, $restoreStock);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Order cancelled successfully');
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
                errorResponse('Order ID is required');
            }
            $result = deleteOrder($id);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Order deleted successfully');
            break;
            
        case 'remove-item':
            $itemId = $_GET['item_id'] ?? null;
            if (!$id || !$itemId) {
                errorResponse('Order ID and item ID are required');
            }
            $restoreStock = isset($_GET['restore_stock']) ? (bool)$_GET['restore_stock'] : true;
            $result = removeOrderItem($id, $itemId, $restoreStock);
            if (isset($result['error'])) {
                errorResponse($result['error']);
            }
            successResponse($result, 'Item removed from order successfully');
            break;
            
        default:
            errorResponse('Invalid action');
    }
}
?>