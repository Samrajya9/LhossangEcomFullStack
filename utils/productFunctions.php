<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/productFunctions.php
require_once __DIR__ . '/../config/connection.php';


/**
 * Handles image upload, validation, and moving.
 * @param array $fileData - The file data from $_FILES['image'].
 * @param string|null $oldImagePath - The path of the old image to delete on update.
 * @return array - ['success' => true, 'path' => '...'] or ['error' => '...']
 */
function handleImageUpload($fileData, $oldImagePath = null) {
    if (!isset($fileData) || $fileData['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'No image uploaded or an upload error occurred.'];
    }

    // Absolute path to the uploads folder
    $uploadDir = '/opt/lampp/htdocs/infinityAdmin/uploads/products/';

    // Ensure the directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // File validation
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['images/jpeg', 'images/png', 'images/jpg'];
    
    if ($fileData['size'] > $maxSize) {
        return ['error' => 'Image file is too large. Maximum size is 5MB.'];
    }
    
    $fileMimeType = mime_content_type($fileData['tmp_name']);
    if (!in_array($fileMimeType, $allowedTypes)) {
        return ['error' => 'Invalid image format. Only JPG and PNG are allowed.'];
    }

    // Generate unique filename
    $fileExtension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;
    $destination = $uploadDir . $newFileName;

    // Log the paths for debugging
    error_log("TMP FILE: " . $fileData['tmp_name']);
    error_log("DESTINATION: " . $destination);

    // Move the uploaded file
    if (move_uploaded_file($fileData['tmp_name'], $destination)) {
        // Delete old image if it exists
        if ($oldImagePath) {
            $fullOldPath = '/opt/lampp/htdocs/infinityAdmin/' . ltrim($oldImagePath, '/');
            if (file_exists($fullOldPath) && is_file($fullOldPath)) {
                unlink($fullOldPath);
            }
        }
        // Return relative path for database
        return ['success' => true, 'path' => '/uploads/products/' . $newFileName];
    } else {
        error_log("Failed to move uploaded file from " . $fileData['tmp_name'] . " to " . $destination);
        return ['error' => 'Failed to move uploaded file.'];
    }
}



/**
 * Get all products (UPDATED with search functionality)
 */
function getAllProducts($limit = null, $offset = 0, $categoryId = null, $activeOnly = false, $searchTerm = null) {
    global $conn;
    try {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.category_id, 
                p.image_url, p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if ($categoryId !== null) {
            $conditions[] = "p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        if ($activeOnly) {
            $conditions[] = "p.is_active = 1";
        }
        
        // ADDED: Handle search term
        if ($searchTerm !== null && !empty($searchTerm)) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTermWildcard = "%" . $searchTerm . "%";
            $params[] = $searchTermWildcard;
            $params[] = $searchTermWildcard;
            $types .= 'ss';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
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
 * Get product by ID
 */
function getProductById($id) {
    global $conn;
    try {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.category_id, 
                p.image_url, p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?";
        return fetchOne($conn, $sql, [$id], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Create new product
 */
function createProduct($data, $files) {
    global $conn;
    
    // ... (your existing validation for name, price, etc.)
    
    $data['image_url'] = null; // Default to null

    // Handle image upload if a file is provided
    if (isset($files['image']) && $files['image']['error'] == UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($files['image']);
        if (isset($uploadResult['error'])) {
            return $uploadResult; // Return the upload error
        }
        $data['image_url'] = $uploadResult['path'];
    }
    
    try {
        $sql = "INSERT INTO products (name, description, price, category_id, image_url, stock_quantity, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['category_id'] ?? null,
            $data['image_url'], // Use the new path
            $data['stock_quantity'] ?? 0,
            $data['is_active'] ?? 1
        ];
        
        $stmt = executeQuery($conn, $sql, $params, 'ssdisii');
        $productId = getLastInsertId($conn);
        $stmt->close();
        
        return ['success' => true, 'id' => $productId];
        
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}
function getProductsByIds($ids) {
    global $conn;

    // Ensure IDs are provided and are in an array.
    if (empty($ids) || !is_array($ids)) {
        return [];
    }
    
    // Create placeholders for the IN clause.
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    // Define the type string for parameters ('i' for each integer ID).
    $types = str_repeat('i', count($ids));
    
    try {
        $sql = "SELECT p.id, p.name, p.price, p.image_url, c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id IN ($placeholders)";
        
        return fetchAll($conn, $sql, $ids, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update product
 */
function updateProduct($id, $data, $files) {
    global $conn;
    
    try {
        $existingProduct = getProductById($id);
        if (!$existingProduct) {
            return ['error' => 'Product not found'];
        }
        
        $oldImagePath = $existingProduct['image_url'];
        $data['image_url'] = $oldImagePath; // Keep old image by default

        // Handle new image upload if provided
        if (isset($files['image']) && $files['image']['error'] == UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($files['image'], $oldImagePath);
            if (isset($uploadResult['error'])) {
                return $uploadResult; // Return upload error
            }
            $data['image_url'] = $uploadResult['path'];
        }
        
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_url = ?, stock_quantity = ?, is_active = ? WHERE id = ?";
        
        $params = [
            $data['name'] ?? $existingProduct['name'],
            $data['description'] ?? $existingProduct['description'],
            $data['price'] ?? $existingProduct['price'],
            $data['category_id'] ?? $existingProduct['category_id'],
            $data['image_url'],
            $data['stock_quantity'] ?? $existingProduct['stock_quantity'],
            $data['is_active'] ?? $existingProduct['is_active'],
            $id
        ];
        
        $stmt = executeQuery($conn, $sql, $params, 'ssdisiii');
        $stmt->close();
        
        return ['success' => true, 'message' => 'Product updated successfully'];
        
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Delete product
 */
function deleteProduct($id) {
    global $conn;
    try {
        $product = getProductById($id);
        if (!$product) {
            return ['error' => 'Product not found'];
        }

        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$id], 'i');
        $stmt->close();
        
        // After successful deletion from DB, delete the image file
        if ($product['image_url']) {
            $fullImagePath = __DIR__ . '/../' . $product['image_url'];
            if (file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }
        }
        
        return ['success' => true, 'message' => 'Product deleted successfully'];
    } catch (Exception $e) {
        // Handle foreign key constraint error specifically
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
             return ['error' => 'Cannot delete product. It is part of an existing order.'];
        }
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Get products by category
 */
function getProductsByCategory($categoryId, $activeOnly = true) {
    global $conn;
    try {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.category_id, 
                p.image_url, p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ?";
        
        $params = [$categoryId];
        $types = 'i';
        
        if ($activeOnly) {
            $sql .= " AND p.is_active = 1";
        }
        
        $sql .= " ORDER BY p.name ASC";
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get low stock products
 */
function getLowStockProducts($threshold = 10) {
    global $conn;
    try {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.category_id, 
                p.image_url, p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.stock_quantity <= ? AND p.is_active = 1
                ORDER BY p.stock_quantity ASC";
        
        return fetchAll($conn, $sql, [$threshold], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get total products count (UPDATED with search functionality)
 */
function getTotalProductsCount($categoryId = null, $activeOnly = false, $searchTerm = null) {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM products p";
        
        $conditions = [];
        $params = [];
        $types = '';

        if ($categoryId !== null) {
            $conditions[] = "p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        if ($activeOnly) {
            $conditions[] = "p.is_active = 1";
        }

        // ADDED: Handle search term
        if ($searchTerm !== null && !empty($searchTerm)) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTermWildcard = "%" . $searchTerm . "%";
            $params[] = $searchTermWildcard;
            $params[] = $searchTermWildcard;
            $types .= 'ss';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        return getCount($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Toggle product active status
 */
function toggleProductStatus($id) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid product ID'];
    }
    
    try {
        // Check if product exists
        $existing = getProductById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Product not found'];
        }
        
        $newStatus = $existing['is_active'] ? 0 : 1;
        
        $sql = "UPDATE products SET is_active = ? WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$newStatus, $id], 'ii');
        $stmt->close();
        
        return [
            'success' => true,
            'is_active' => (bool)$newStatus,
            'message' => 'Product status updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Search products
 */
function searchProducts($searchTerm, $categoryId = null, $activeOnly = false) {
    global $conn;
    try {
        $searchTerm = "%$searchTerm%";
        
        $sql = "SELECT p.id, p.name, p.description, p.price, p.category_id, 
                p.image_url, p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                c.name as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE (p.name LIKE ? OR p.description LIKE ?)";
        
        $params = [$searchTerm, $searchTerm];
        $types = 'ss';
        
        if ($categoryId !== null) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        if ($activeOnly) {
            $sql .= " AND p.is_active = 1";
        }
        
        $sql .= " ORDER BY p.name ASC";
        
        return fetchAll($conn, $sql, $params, $types);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update product stock
 */
function updateProductStock($id, $quantity, $operation = 'set') {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid product ID'];
    }
    
    // Validate quantity
    if (!is_numeric($quantity) || $quantity < 0) {
        return ['error' => 'Quantity must be a valid positive number'];
    }
    
    // Validate operation
    $validOperations = ['set', 'add', 'subtract'];
    if (!in_array($operation, $validOperations)) {
        return ['error' => 'Invalid operation. Must be: set, add, or subtract'];
    }
    
    try {
        // Check if product exists
        $existing = getProductById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Product not found'];
        }
        
        $sql = '';
        $params = [];
        $types = '';
        
        switch ($operation) {
            case 'add':
                $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                $params = [$quantity, $id];
                $types = 'ii';
                break;
            case 'subtract':
                // Check if enough stock is available
                if ($existing['stock_quantity'] < $quantity) {
                    return ['error' => 'Insufficient stock quantity'];
                }
                $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $params = [$quantity, $id];
                $types = 'ii';
                break;
            case 'set':
            default:
                $sql = "UPDATE products SET stock_quantity = ? WHERE id = ?";
                $params = [$quantity, $id];
                $types = 'ii';
                break;
        }
        
        $stmt = executeQuery($conn, $sql, $params, $types);
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Product stock updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
?>