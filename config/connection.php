<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'infinity';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to execute prepared statement
function executeQuery($conn, $sql, $params = [], $types = '') {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat('s', count($params)); // Default to string type
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    if (!$result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt;
}

// Function to get all results from a query
function fetchAll($conn, $sql, $params = [], $types = '') {
    $stmt = executeQuery($conn, $sql, $params, $types);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Function to get single result from a query
function fetchOne($conn, $sql, $params = [], $types = '') {
    $stmt = executeQuery($conn, $sql, $params, $types);
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Function to get count from query
function getCount($conn, $sql, $params = [], $types = '') {
    $stmt = executeQuery($conn, $sql, $params, $types);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)array_values($row)[0] : 0;
}

// Function to get last insert ID
function getLastInsertId($conn) {
    return $conn->insert_id;
}

// Function to start transaction
function startTransaction($conn) {
    $conn->autocommit(false);
}

// Function to commit transaction
function commitTransaction($conn) {
    $conn->commit();
    $conn->autocommit(true);
}

// Function to rollback transaction
function rollbackTransaction($conn) {
    $conn->rollback();
    $conn->autocommit(true);
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error response helper
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// Success response helper
function successResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}
?>