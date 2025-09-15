
<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/adminFunctions.php
require_once __DIR__ . '/../config/connection.php';

/**
 * Get all admins
 */
function getAllAdmins() {
    global $conn;
    try {
        $sql = "SELECT id, username, email, created_at, updated_at FROM admin ORDER BY created_at DESC";
        return fetchAll($conn, $sql);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get admin by ID
 */
function getAdminById($id) {
    global $conn;
    try {
        $sql = "SELECT id, username, email, created_at, updated_at FROM admin WHERE id = ?";
        return fetchOne($conn, $sql, [$id], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get admin by email (for login)
 */
function getAdminByEmail($email) {
    global $conn;
    try {
        $sql = "SELECT id, username, email, password FROM admin WHERE email = ?";
        return fetchOne($conn, $sql, [$email], 's');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Create new admin
 */
function createAdmin($data) {
    global $conn;
    
    // Validate required fields
    $required = ['username', 'email', 'password'];
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
        // Check if username or email already exists
        $checkSql = "SELECT id FROM admin WHERE username = ? OR email = ?";
        $existing = fetchOne($conn, $checkSql, [$data['username'], $data['email']], 'ss');
        
        if ($existing) {
            return ['error' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert admin
        $sql = "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)";
        $stmt = executeQuery($conn, $sql, [$data['username'], $data['email'], $hashedPassword], 'sss');
        
        $adminId = getLastInsertId($conn);
        $stmt->close();
        
        return [
            'success' => true,
            'admin_id' => $adminId,
            'message' => 'Admin created successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update admin
 */
function updateAdmin($id, $data) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid admin ID'];
    }
    
    try {
        // Check if admin exists
        $existing = getAdminById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Admin not found'];
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        // Build dynamic update query
        if (!empty($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
            $types .= 's';
        }
        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['error' => 'Invalid email format'];
            }
            $updates[] = "email = ?";
            $params[] = $data['email'];
            $types .= 's';
        }
        
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['error' => 'No fields to update'];
        }
        
        // Add ID to params
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE admin SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = executeQuery($conn, $sql, $params, $types);
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Admin updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete admin
 */
function deleteAdmin($id) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid admin ID'];
    }
    
    try {
        // Check if admin exists
        $existing = getAdminById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Admin not found'];
        }
        
        $sql = "DELETE FROM admin WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$id], 'i');
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Admin deleted successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Admin login function
 */
function loginAdmin($email, $password) {
    global $conn;
    
    if (empty($email) || empty($password)) {
        return ['error' => 'Email and password are required'];
    }
    
    try {
        $admin = getAdminByEmail($email);
        
        if (isset($admin['error'])) {
            return $admin;
        }
        
        if (!$admin) {
            return ['error' => 'Invalid email or password'];
        }
        
        if (password_verify($password, $admin['password'])) {
            // Start session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['logged_in'] = true;
            $_SESSION['id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['is_admin']=true;
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email']
                ]
            ];
        } else {
            return ['error' => 'Invalid email or password'];
        }
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Check if admin is logged in
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get current admin info
 */
function getCurrentAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'] ?? '',
            'email' => $_SESSION['admin_email'] ?? ''
        ];
    }
    return null;
}

/**
 * Admin logout
 */
function logoutAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}
?>