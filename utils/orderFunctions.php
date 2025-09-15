<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/orderFunctions.php
require_once __DIR__ . '/../config/connection.php';

/**
 * Get all orders
 */
function getAllOrders($limit = null, $offset = 0, $status = null) {
    global $conn;
    try {
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, 
                o.shipping_address, o.notes, o.created_at, o.updated_at,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id";
        
        $params = [];
        $types = '';
        
        if ($status !== null) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
        }
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get order by ID
 */
function getOrderById($id) {
    global $conn;
    try {
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, 
                o.shipping_address, o.notes, o.created_at, o.updated_at,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email, c.phone as customer_phone
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.id = ?";
        return fetchOne($conn, $sql, [$id], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get order items by order ID
 */
function getOrderItems($orderId) {
    global $conn;
    try {
        $sql = "SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.price, oi.created_at,
                p.name as product_name, p.image_url as product_image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";
        return fetchAll($conn, $sql, [$orderId], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get complete order details (order + items)
 */
function getOrderDetails($orderId) {
    $order = getOrderById($orderId);
    if (isset($order['error']) || !$order) {
        return ['error' => 'Order not found'];
    }
    
    $items = getOrderItems($orderId);
    if (isset($items['error'])) {
        return $items;
    }
    
    return [
        'order' => $order,
        'items' => $items
    ];
}

/**
 * Create new order
 */
function createOrder($data) {
    global $conn;
    
    // Validate required fields
    $required = ['customer_id', 'items'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['error' => "Field '$field' is required"];
        }
    }
    
    // Validate customer exists
    if (!is_numeric($data['customer_id'])) {
        return ['error' => 'Invalid customer ID'];
    }
    
    // Validate items array
    if (!is_array($data['items']) || empty($data['items'])) {
        return ['error' => 'Order items are required'];
    }
    
    try {
        // Check if customer exists
        $customerSql = "SELECT id FROM customers WHERE id = ?";
        $customerExists = fetchOne($conn, $customerSql, [$data['customer_id']], 'i');
        if (!$customerExists) {
            return ['error' => 'Customer not found'];
        }
        
        // Start transaction
        startTransaction($conn);
        
        $totalAmount = 0;
        $validItems = [];
        
        // Validate items and calculate total
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || !isset($item['price'])) {
                rollbackTransaction($conn);
                return ['error' => 'Each item must have product_id, quantity, and price'];
            }
            
            // Check if product exists and has sufficient stock
            $productSql = "SELECT id, name, stock_quantity, price FROM products WHERE id = ? AND is_active = 1";
            $product = fetchOne($conn, $productSql, [$item['product_id']], 'i');
            
            if (!$product) {
                rollbackTransaction($conn);
                return ['error' => "Product with ID {$item['product_id']} not found or inactive"];
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                rollbackTransaction($conn);
                return ['error' => "Insufficient stock for product: {$product['name']}"];
            }
            
            $itemTotal = $item['price'] * $item['quantity'];
            $totalAmount += $itemTotal;
            
            $validItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'product_name' => $product['name']
            ];
        }
        
        // Use provided total_amount if available, otherwise use calculated amount
        if (isset($data['total_amount']) && is_numeric($data['total_amount'])) {
            $totalAmount = $data['total_amount'];
        }
        
        // Insert order
        $orderSql = "INSERT INTO orders (customer_id, total_amount, status, shipping_address, notes) VALUES (?, ?, ?, ?, ?)";
        $orderParams = [
            $data['customer_id'],
            $totalAmount,
            $data['status'] ?? 'pending',
            $data['shipping_address'] ?? '',
            $data['notes'] ?? ''
        ];
        
        $stmt = executeQuery($conn, $orderSql, $orderParams, 'idsss');
        $orderId = getLastInsertId($conn);
        $stmt->close();
        
        // Insert order items and update product stock
        foreach ($validItems as $item) {
            // Insert order item
            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $itemParams = [$orderId, $item['product_id'], $item['quantity'], $item['price']];
            $itemStmt = executeQuery($conn, $itemSql, $itemParams, 'iiid');
            $itemStmt->close();
            
            // Update product stock
            $stockSql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stockStmt = executeQuery($conn, $stockSql, [$item['quantity'], $item['product_id']], 'ii');
            $stockStmt->close();
        }
        
        // Commit transaction
        commitTransaction($conn);
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'total_amount' => $totalAmount,
            'message' => 'Order created successfully'
        ];
        
    } catch (Exception $e) {
        rollbackTransaction($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update order
 */
function updateOrder($id, $data) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid order ID'];
    }
    
    try {
        // Check if order exists
        $existing = getOrderById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Order not found'];
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        // Build dynamic update query
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                return ['error' => 'Invalid status'];
            }
            $updates[] = "status = ?";
            $params[] = $data['status'];
            $types .= 's';
        }
        
        if (isset($data['shipping_address'])) {
            $updates[] = "shipping_address = ?";
            $params[] = $data['shipping_address'];
            $types .= 's';
        }
        
        if (isset($data['notes'])) {
            $updates[] = "notes = ?";
            $params[] = $data['notes'];
            $types .= 's';
        }
        
        if (isset($data['total_amount']) && is_numeric($data['total_amount'])) {
            $updates[] = "total_amount = ?";
            $params[] = $data['total_amount'];
            $types .= 'd';
        }
        
        if (empty($updates)) {
            return ['error' => 'No fields to update'];
        }
        
        // Add ID to params
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = executeQuery($conn, $sql, $params, $types);
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Order updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update order status
 */
function updateOrderStatus($id, $status) {
    return updateOrder($id, ['status' => $status]);
}

/**
 * Cancel order
 */
function cancelOrder($id, $restoreStock = true) {
    global $conn;
    
    try {
        $existing = getOrderById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Order not found'];
        }
        
        if ($existing['status'] === 'cancelled') {
            return ['error' => 'Order is already cancelled'];
        }
        
        if (in_array($existing['status'], ['shipped', 'delivered'])) {
            return ['error' => 'Cannot cancel order that has been shipped or delivered'];
        }
        
        // Start transaction
        startTransaction($conn);
        
        // Update order status
        $updateResult = updateOrderStatus($id, 'cancelled');
        if (isset($updateResult['error'])) {
            rollbackTransaction($conn);
            return $updateResult;
        }
        
        // Restore stock if requested
        if ($restoreStock) {
            $items = getOrderItems($id);
            if (isset($items['error'])) {
                rollbackTransaction($conn);
                return $items;
            }
            
            foreach ($items as $item) {
                $stockSql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                $stockStmt = executeQuery($conn, $stockSql, [$item['quantity'], $item['product_id']], 'ii');
                $stockStmt->close();
            }
        }
        
        // Commit transaction
        commitTransaction($conn);
        
        return [
            'success' => true,
            'message' => 'Order cancelled successfully'
        ];
        
    } catch (Exception $e) {
        rollbackTransaction($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete order (admin only - careful operation)
 */
function deleteOrder($id) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid order ID'];
    }
    
    try {
        // Check if order exists
        $existing = getOrderById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Order not found'];
        }
        
        // Start transaction
        startTransaction($conn);
        
        // Delete order items first (due to foreign key constraint)
        $deleteItemsSql = "DELETE FROM order_items WHERE order_id = ?";
        $itemsStmt = executeQuery($conn, $deleteItemsSql, [$id], 'i');
        $itemsStmt->close();
        
        // Delete order
        $deleteOrderSql = "DELETE FROM orders WHERE id = ?";
        $orderStmt = executeQuery($conn, $deleteOrderSql, [$id], 'i');
        $orderStmt->close();
        
        // Commit transaction
        commitTransaction($conn);
        
        return [
            'success' => true,
            'message' => 'Order deleted successfully'
        ];
        
    } catch (Exception $e) {
        rollbackTransaction($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Search orders
 */
function searchOrders($searchTerm, $status = null) {
    global $conn;
    try {
        $searchTerm = "%$searchTerm%";
        
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, 
                o.shipping_address, o.notes, o.created_at, o.updated_at,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR o.id LIKE ?)";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = 'ssss';
        
        if ($status !== null) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get orders by date range
 */
function getOrdersByDateRange($startDate, $endDate, $status = null) {
    global $conn;
    try {
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, 
                o.shipping_address, o.notes, o.created_at, o.updated_at,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE DATE(o.order_date) BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        $types = 'ss';
        
        if ($status !== null) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get order statistics
 */
function getOrderStatistics($startDate = null, $endDate = null) {
    global $conn;
    try {
        $whereClause = '';
        $params = [];
        $types = '';
        
        if ($startDate && $endDate) {
            $whereClause = 'WHERE DATE(order_date) BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
            $types = 'ss';
        }
        
        $sql = "SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                MIN(total_amount) as min_order_value,
                MAX(total_amount) as max_order_value
                FROM orders $whereClause";
        
        return fetchOne($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get recent orders
 */
function getRecentOrders($limit = 10) {
    global $conn;
    try {
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                ORDER BY o.order_date DESC
                LIMIT ?";
        
        return fetchAll($conn, $sql, [$limit], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get orders by status
 */
function getOrdersByStatus($status) {
    global $conn;
    try {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return ['error' => 'Invalid status'];
        }
        
        $sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, 
                o.shipping_address, o.notes, o.created_at, o.updated_at,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.status = ?
                ORDER BY o.order_date DESC";
        
        return fetchAll($conn, $sql, [$status], 's');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get total orders count
 */
function getTotalOrdersCount($status = null) {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM orders";
        $params = [];
        $types = '';
        
        if ($status !== null) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        return getCount($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get top selling products from orders
 */
function getTopSellingProducts($limit = 10, $startDate = null, $endDate = null) {
    global $conn;
    try {
        $sql = "SELECT oi.product_id, p.name as product_name, p.price as product_price,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.price * oi.quantity) as total_revenue,
                COUNT(DISTINCT oi.order_id) as orders_count
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE o.status NOT IN ('cancelled')";
        
        $params = [];
        $types = '';
        
        if ($startDate && $endDate) {
            $sql .= " AND DATE(o.order_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }
        
        $sql .= " GROUP BY oi.product_id, p.name, p.price
                ORDER BY total_quantity_sold DESC
                LIMIT ?";
        
        $params[] = $limit;
        $types .= 'i';
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get monthly sales data
 */
function getMonthlySalesData($year = null) {
    global $conn;
    try {
        if ($year === null) {
            $year = date('Y');
        }
        
        $sql = "SELECT 
                MONTH(order_date) as month,
                MONTHNAME(order_date) as month_name,
                COUNT(*) as orders_count,
                SUM(total_amount) as total_revenue
                FROM orders 
                WHERE YEAR(order_date) = ? AND status NOT IN ('cancelled')
                GROUP BY MONTH(order_date), MONTHNAME(order_date)
                ORDER BY MONTH(order_date)";
        
        return fetchAll($conn, $sql, [$year], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Add item to existing order
 */
function addOrderItem($orderId, $productId, $quantity, $price) {
    global $conn;
    
    try {
        // Check if order exists and is not cancelled/delivered
        $order = getOrderById($orderId);
        if (isset($order['error']) || !$order) {
            return ['error' => 'Order not found'];
        }
        
        if (in_array($order['status'], ['cancelled', 'delivered'])) {
            return ['error' => 'Cannot modify cancelled or delivered orders'];
        }
        
        // Check if product exists and has sufficient stock
        $productSql = "SELECT id, name, stock_quantity FROM products WHERE id = ? AND is_active = 1";
        $product = fetchOne($conn, $productSql, [$productId], 'i');
        
        if (!$product) {
            return ['error' => 'Product not found or inactive'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return ['error' => 'Insufficient stock'];
        }
        
        // Start transaction
        startTransaction($conn);
        
        // Add order item
        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $itemStmt = executeQuery($conn, $itemSql, [$orderId, $productId, $quantity, $price], 'iiid');
        $itemStmt->close();
        
        // Update product stock
        $stockSql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
        $stockStmt = executeQuery($conn, $stockSql, [$quantity, $productId], 'ii');
        $stockStmt->close();
        
        // Update order total
        $updateTotalSql = "UPDATE orders SET total_amount = total_amount + ? WHERE id = ?";
        $totalStmt = executeQuery($conn, $updateTotalSql, [$price * $quantity, $orderId], 'di');
        $totalStmt->close();
        
        // Commit transaction
        commitTransaction($conn);
        
        return [
            'success' => true,
            'message' => 'Item added to order successfully'
        ];
        
    } catch (Exception $e) {
        rollbackTransaction($conn);
        return ['error' => $e->getMessage()];
    }
}

/**
 * Remove item from existing order
 */
function removeOrderItem($orderId, $itemId, $restoreStock = true) {
    global $conn;
    
    try {
        // Check if order exists and is not cancelled/delivered
        $order = getOrderById($orderId);
        if (isset($order['error']) || !$order) {
            return ['error' => 'Order not found'];
        }
        
        if (in_array($order['status'], ['cancelled', 'delivered'])) {
            return ['error' => 'Cannot modify cancelled or delivered orders'];
        }
        
        // Get order item details
        $itemSql = "SELECT id, product_id, quantity, price FROM order_items WHERE id = ? AND order_id = ?";
        $item = fetchOne($conn, $itemSql, [$itemId, $orderId], 'ii');
        
        if (!$item) {
            return ['error' => 'Order item not found'];
        }
        
        // Start transaction
        startTransaction($conn);
        
        // Delete order item
        $deleteSql = "DELETE FROM order_items WHERE id = ?";
        $deleteStmt = executeQuery($conn, $deleteSql, [$itemId], 'i');
        $deleteStmt->close();
        
        // Restore stock if requested
        if ($restoreStock) {
            $stockSql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $stockStmt = executeQuery($conn, $stockSql, [$item['quantity'], $item['product_id']], 'ii');
            $stockStmt->close();
        }
        
        // Update order total
        $itemTotal = $item['price'] * $item['quantity'];
        $updateTotalSql = "UPDATE orders SET total_amount = total_amount - ? WHERE id = ?";
        $totalStmt = executeQuery($conn, $updateTotalSql, [$itemTotal, $orderId], 'di');
        $totalStmt->close();
        
        // Commit transaction
        commitTransaction($conn);
        
        return [
            'success' => true,
            'message' => 'Item removed from order successfully'
        ];
        
    } catch (Exception $e) {
        rollbackTransaction($conn);
        return ['error' => $e->getMessage()];
    }
}
?>