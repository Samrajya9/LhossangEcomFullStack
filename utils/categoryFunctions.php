<?php
// file_full_path = /opt/lampp/htdocs/infinityAdmin/utils/categoryFunctions.php
require_once __DIR__ . '/../config/connection.php';


/**
 * Get all categories
 */
function getAllCategories($limit = null, $offset = 0) {
    global $conn;
    try {
        $sql = "SELECT id, name, description, created_at, updated_at FROM categories ORDER BY created_at DESC";
        
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
 * Get category by ID
 */
function getCategoryById($id) {
    global $conn;
    try {
        $sql = "SELECT id, name, description, created_at, updated_at FROM categories WHERE id = ?";
        return fetchOne($conn, $sql, [$id], 'i');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Create new category
 */
function createCategory($data) {
    global $conn;
    
    // Validate required fields
    if (empty($data['name'])) {
        return ['error' => 'Category name is required'];
    }
    
    try {
        // Check if category name already exists
        $checkSql = "SELECT id FROM categories WHERE name = ?";
        $existing = fetchOne($conn, $checkSql, [$data['name']], 's');
        
        if ($existing) {
            return ['error' => 'Category name already exists'];
        }
        
        // Insert category
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $description = $data['description'] ?? '';
        $stmt = executeQuery($conn, $sql, [$data['name'], $description], 'ss');
        
        $categoryId = getLastInsertId($conn);
        $stmt->close();
        
        return [
            'success' => true,
            'category_id' => $categoryId,
            'message' => 'Category created successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update category
 */
function updateCategory($id, $data) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid category ID'];
    }
    
    try {
        // Check if category exists
        $existing = getCategoryById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Category not found'];
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        // Build dynamic update query
        if (!empty($data['name'])) {
            // Check if name already exists (excluding current category)
            $checkSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
            $nameExists = fetchOne($conn, $checkSql, [$data['name'], $id], 'si');
            
            if ($nameExists) {
                return ['error' => 'Category name already exists'];
            }
            
            $updates[] = "name = ?";
            $params[] = $data['name'];
            $types .= 's';
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['error' => 'No fields to update'];
        }
        
        // Add ID to params
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE categories SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = executeQuery($conn, $sql, $params, $types);
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete category
 */
function deleteCategory($id) {
    global $conn;
    
    // Validate ID
    if (empty($id) || !is_numeric($id)) {
        return ['error' => 'Invalid category ID'];
    }
    
    try {
        // Check if category exists
        $existing = getCategoryById($id);
        if (isset($existing['error']) || !$existing) {
            return ['error' => 'Category not found'];
        }
        
        // Check if category has associated products
        $productsSql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $productCount = getCount($conn, $productsSql, [$id], 'i');
        
        if ($productCount > 0) {
            return ['error' => "Cannot delete category. It has $productCount associated products."];
        }
        
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$id], 'i');
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get categories with product count
 */
function getCategoriesWithProductCount() {
    global $conn;
    try {
        $sql = "SELECT c.id, c.name, c.description, c.created_at, c.updated_at, 
                COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
                ORDER BY c.created_at DESC";
        return fetchAll($conn, $sql);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Search categories
 */
function searchCategories($searchTerm) {
    global $conn;
    try {
        $searchTerm = "%$searchTerm%";
        $sql = "SELECT id, name, description, created_at, updated_at 
                FROM categories 
                WHERE name LIKE ? OR description LIKE ?
                ORDER BY name ASC";
        return fetchAll($conn, $sql, [$searchTerm, $searchTerm], 'ss');
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get total categories count
 */
function getTotalCategoriesCount() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM categories";
        return getCount($conn, $sql);
    } catch (Exception $e) {
        return 0;
    }
}
?>