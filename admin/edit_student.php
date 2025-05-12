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
    // if (empty($_POST['id']) || empty($_POST['nom_complet']) || empty($_POST['email']) || 
    //     empty($_POST['date_naissance']) || empty($_POST['filiere']) || 
    //     empty($_POST['classe_id'])) {
    //     header('Content-Type: application/json');
    //     echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    //     exit;
    // }

    // Extract form data
    $id = (int)$_POST['id'];
    $full_name = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $date_of_birth = $_POST['date_naissance'];
    $major_id = (int)$_POST['filiere'];
    $class_id = (int)$_POST['classe_id'];

    try {
        $conn = getConnection();
        
        // Check if email already exists for another student
        $stmt = $conn->prepare("SELECT id FROM etudiants WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'This email is already used by another student']);
            exit;
        }
        
        // Update student
        $stmt = $conn->prepare("
            UPDATE etudiants 
            SET nom_complet = ?, 
                email = ?, 
                date_naissance = ?, 
                filiere_id = ?, 
                classe_id = ? 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $full_name,
            $email,
            $date_of_birth,
            $major_id,
            $class_id,
            $id
        ]);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Student information updated successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Error during update']);
        }
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
?>