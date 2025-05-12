<?php

    session_start();
    require_once '../config/connection.php';
    require_once '../includes/functions.php';

    // Check if the user is logged in and has necessary permissions
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit;
    }

    // Process only POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Validate required fields
    if (empty($_POST['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Missing student ID']);
        exit;
    }

    // Extract student ID
    $id = (int)$_POST['id'];

    try {
        $conn = getConnection();
        
        // Delete student (or mark as deleted for soft delete)
        // Option 1: Hard delete
        $stmt = $conn->prepare("DELETE FROM etudiants WHERE id = ?");
        
        // Option 2: Soft delete (uncomment to use this instead)
        // $stmt = $conn->prepare("UPDATE etudiants SET status = 'deleted' WHERE id = ?");
        
        $result = $stmt->execute([$id]);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Student deleted successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Error during deletion']);
        }
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
?>