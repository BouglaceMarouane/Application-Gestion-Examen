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
    if (empty($_POST['nom_complet']) || empty($_POST['email']) || 
        empty($_POST['date_naissance']) || empty($_POST['filiere']) || 
        empty($_POST['classe_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Extract form data
    $nom_complet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $date_naissance = $_POST['date_naissance'];
    $id_filiere = (int)$_POST['filiere'];
    $classe_id = (int)$_POST['classe_id'];

    try {
        $conn = getConnection();
        $conn->beginTransaction();
        
        // Check if email already exists in students
        $stmt = $conn->prepare("SELECT id FROM etudiants WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'This email is already used by another student']);
            exit;
        }
        
        // Check if email already exists in users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'This email is already used by another user']);
            exit;
        }
        
        // Generate a password (you might want to send this to the student)
        $password = generateRandomPassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // First, create a user in the users table
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, created_at) 
            VALUES (?, ?, ?, 'student', NOW())
        ");
        
        $stmt->execute([
            $nom_complet,
            $email,
            $hashed_password
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // Then, insert the student with the user_id reference
        $stmt = $conn->prepare("
            INSERT INTO etudiants (user_id, nom_complet, email, date_naissance, filiere_id, classe_id, is_validated, created_at, status) 
            VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW(), 'validated')
        ");
        
        $result = $stmt->execute([
            $user_id,
            $nom_complet,
            $email,
            $date_naissance,
            $id_filiere,
            $classe_id
        ]);
        
        if ($result) {
            $conn->commit();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'message' => 'Student added successfully',
                'password' => $password // You may want to send this by email instead of returning it directly
            ]);
        } else {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Error adding the student']);
        }
        
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }

    /**
     * Generate a random password
     */
    function generateRandomPassword($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
?>