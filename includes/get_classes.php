<?php

    require_once '../config/connection.php';

    header('Content-Type: application/json'); // Elle indique au navigateur (ou client) que la réponse envoyée par le serveur est au format JSON.

    if (!isset($_GET['filiere_id']) || empty($_GET['filiere_id'])) {
        echo json_encode([]); 
        exit;
        // --> exemple: $data = ["nom" => "Ali", "age" => 20]; 
        // echo json_encode($data);
        // --> resultat: {"nom":"Ali","age":20}
        // json_encode() transforme une structure PHP (tableau ou objet) en texte JSON, 
        // prêt à être compris par JavaScript, API, AJAX ou un autre système externe.
    }

    $filiere_id = intval($_GET['filiere_id']);

    try {
        $conn = getConnection();
        
        // Get classes for the selected filière
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