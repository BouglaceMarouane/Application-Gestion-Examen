<?php

    require_once '../config/connection.php';

    header('Content-Type: application/json'); // Indicates to the browser (or client) that the server's response is in JSON format.

    if (!isset($_GET['filiere_id']) || empty($_GET['filiere_id'])) {
        echo json_encode([]); 
        exit;
        // --> Example: $data = ["name" => "Ali", "age" => 20]; 
        // echo json_encode($data);
        // --> Result: {"name":"Ali","age":20}
        // json_encode() transforms a PHP structure (array or object) into JSON text,
        // ready to be understood by JavaScript, APIs, AJAX, or another external system.
    }

    $filiere_id = intval($_GET['filiere_id']);

    try {
        $conn = getConnection();
        
        // Get classes for the selected major
        $stmt = $conn->prepare("SELECT id, nom FROM classes WHERE filiere_id = ? ORDER BY nom");
        $stmt->execute([$filiere_id]);
        
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($classes);
    } catch (PDOException $e) {
        // Log error but don't expose details to client
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }