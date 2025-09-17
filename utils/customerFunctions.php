<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/customerFunctions.php
require_once __DIR__ . '/../config/connection.php';

/**
 * Get all customers
 */
function getAllCustomers($limit = null, $offset = 0) {
    global $conn;
    try {
        $sql = "SELECT id, first_name, last_name, email, phone, address, city, state, zip_code, country, created_at, updated_at FROM customers ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            return fetchAll($conn, $sql, [$limit, $offset], 'ii');
        }
        
        return fetchAll($conn, $sql);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get customer by ID
 */
function getCustomerById($id) {
    global $conn;
    try {
        $sql = "SELECT id, first_name, last_name, email, phone, address, city, state, zip_code, country, created_at, updated_at FROM customers WHERE id = ?";
        return fetchOne($conn, $sql, [$id], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get customer by email
 */
function getCustomerByEmail($email) {
    global $conn;
    try {
        $sql = "SELECT id, first_name, last_name, email, phone, address, city, state, zip_code, country, created_at, updated_at FROM customers WHERE email = ?";
        return fetchOne($conn, $sql, [$email], 's');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * Create new customer (UPDATED with password handling)
 */
function createCustomer($data) {
    global $conn;
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['error' => "Field '$field' is required"];
        }
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid email format'];
    }
    
    try {
        // Check if email already exists
        $checkSql = "SELECT id FROM customers WHERE email = ?";
        $existing = fetchOne($conn, $checkSql, [$data['email']], 's');
        
        if ($existing) {
            return ['error' => 'A customer with this email already exists.'];
        }

        // Hash the password for secure storage
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert customer with the hashed password
        // IMPORTANT: Ensure your 'customers' table has a 'password_hash' column (e.g., VARCHAR(255))
        $sql = "INSERT INTO customers (first_name, last_name, email, password_hash, phone, address, city, state, zip_code, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $passwordHash, // Store the hash, not the plain password
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['zip_code'] ?? '',
            $data['country'] ?? ''
        ];
        
        $stmt = executeQuery($conn, $sql, $params, 'ssssssssss');
        
        $customerId = getLastInsertId($conn);
        $stmt->close();
        
        return [
            'success' => true,
            'customer_id' => $customerId,
            'message' => 'Customer created successfully'
        ];
        
    } catch (Exception $e) {
        // Check for specific duplicate entry error from the database
        if ($conn->errno === 1062) {
             return ['error' => 'A customer with this email already exists.'];
        }
        return ['error' => $e->getMessage()];
    }
}
/**
 * Update customer
 */
function updateCustomer($id, $data) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid customer ID'];
    }
    
    try {
        // Check if customer exists
        $existing = getCustomerById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Customer not found'];
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        // Build dynamic update query
        if (!empty($data['first_name'])) {
            $updates[] = "first_name = ?";
            $params[] = $data['first_name'];
            $types .= 's';
        }
        
        if (!empty($data['last_name'])) {
            $updates[] = "last_name = ?";
            $params[] = $data['last_name'];
            $types .= 's';
        }
        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['error' => 'Invalid email format'];
            }
            
            // Check if email already exists (excluding current customer)
            $checkSql = "SELECT id FROM customers WHERE email = ? AND id != ?";
            $emailExists = fetchOne($conn, $checkSql, [$data['email'], $id], 'si');
            
            if ($emailExists) {
                return ['error' => 'Email already exists'];
            }
            
            $updates[] = "email = ?";
            $params[] = $data['email'];
            $types .= 's';
        }
        
        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= 's';
        }
        
        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = $data['address'];
            $types .= 's';
        }
        
        if (isset($data['city'])) {
            $updates[] = "city = ?";
            $params[] = $data['city'];
            $types .= 's';
        }
        
        if (isset($data['state'])) {
            $updates[] = "state = ?";
            $params[] = $data['state'];
            $types .= 's';
        }
        
        if (isset($data['zip_code'])) {
            $updates[] = "zip_code = ?";
            $params[] = $data['zip_code'];
            $types .= 's';
        }
        
        if (isset($data['country'])) {
            $updates[] = "country = ?";
            $params[] = $data['country'];
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['error' => 'No fields to update'];
        }
        
        // Add ID to params
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = executeQuery($conn, $sql, $params, $types);
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Customer updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete customer
 */
function deleteCustomer($id) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid customer ID'];
    }
    
    try {
        // Check if customer exists
        $existing = getCustomerById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Customer not found'];
        }
        
        // Check if customer has associated orders
        $ordersSql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
        $orderCount = getCount($conn, $ordersSql, [$id], 'i');
        
        if ($orderCount > 0) {
            return ['error' => "Cannot delete customer. They have $orderCount associated order(s)."];
        }
        
        $sql = "DELETE FROM customers WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$id], 'i');
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Customer deleted successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Search customers
 */
function searchCustomers($searchTerm) {
    global $conn;
    try {
        $searchTerm = "%$searchTerm%";
        $sql = "SELECT id, first_name, last_name, email, phone, address, city, state, zip_code, country, created_at, updated_at 
                FROM customers 
                WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY first_name ASC, last_name ASC";
        return fetchAll($conn, $sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm], 'ssss');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get customers with order statistics
 */
function getCustomersWithOrderStats($limit = null, $offset = 0) {
    global $conn;
    try {
        $sql = "SELECT c.id, c.first_name, c.last_name, c.email, c.phone, c.address, 
                c.city, c.state, c.zip_code, c.country, c.created_at, c.updated_at,
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                MAX(o.order_date) as last_order_date
                FROM customers c 
                LEFT JOIN orders o ON c.id = o.customer_id 
                GROUP BY c.id, c.first_name, c.last_name, c.email, c.phone, c.address, 
                         c.city, c.state, c.zip_code, c.country, c.created_at, c.updated_at
                ORDER BY total_spent DESC, c.created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            return fetchAll($conn, $sql, [$limit, $offset], 'ii');
        }
        
        return fetchAll($conn, $sql);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get customer orders
 */
function getCustomerOrders($customerId, $limit = null, $offset = 0) {
    global $conn;
    try {
        $sql = "SELECT id, customer_id, order_date, total_amount, status, shipping_address, notes, created_at, updated_at
                FROM orders 
                WHERE customer_id = ?
                ORDER BY order_date DESC";
        
        $params = [$customerId];
        $types = 'i';
        
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
 * Get total customers count
 */
function getTotalCustomersCount() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM customers";
        return getCount($conn, $sql);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get customers by location (city/state/country)
 */
function getCustomersByLocation($locationType, $locationValue) {
    global $conn;
    
    $validTypes = ['city', 'state', 'country'];
    if (!in_array($locationType, $validTypes)) {
        return ['error' => 'Invalid location type'];
    }
    
    try {
        $sql = "SELECT id, first_name, last_name, email, phone, address, city, state, zip_code, country, created_at, updated_at 
                FROM customers 
                WHERE $locationType = ?
                ORDER BY first_name ASC, last_name ASC";
        
        return fetchAll($conn, $sql, [$locationValue], 's');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get top customers by spending
 */
function getTopCustomersBySpending($limit = 10) {
    global $conn;
    try {
        $sql = "SELECT c.id, c.first_name, c.last_name, c.email, c.phone,
                COUNT(o.id) as total_orders,
                SUM(o.total_amount) as total_spent
                FROM customers c 
                JOIN orders o ON c.id = o.customer_id 
                GROUP BY c.id, c.first_name, c.last_name, c.email, c.phone
                ORDER BY total_spent DESC
                LIMIT ?";
        
        return fetchAll($conn, $sql, [$limit], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get customer by email for authentication purposes.
 * This function retrieves the password hash for verification.
 * @param string $email The customer's email.
 * @return array|null The customer data or null if not found.
 */
function getCustomerAuthByEmail($email) {
    global $conn;
    try {
        // This query assumes you have added a 'password_hash' column to your 'customers' table.
        $sql = "SELECT id, first_name, last_name, email, password_hash FROM customers WHERE email = ?";
        return fetchOne($conn, $sql, [$email], 's');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
?>