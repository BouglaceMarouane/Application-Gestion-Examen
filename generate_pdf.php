<?php
    // Start the session and include necessary files
    session_start();
    require_once 'config/connection.php';
    require_once 'includes/functions.php';

    // Check if user is logged in as admin or teacher
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
        header("Location: index.php?error=unauthorized");
        exit;
    }

    // Get student ID from request
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

    if (!$student_id) {
        die("Student ID is required");
    }

    // Define this constant to prevent direct access to the included file
    define('INCLUDED_FILE', true);

    // Get database connection
    $pdo = getConnection();
    $conn = $pdo; // For compatibility with the included file

    // Check if TCPDF is installed
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        die("TCPDF library not found. Please install it using Composer.");
    }

    // Include the TCPDF library
    require_once __DIR__ . '/vendor/autoload.php';

    // Set the etudiant_id variable for the included file
    $etudiant_id = $student_id;

    // Include the PDF generation code
    include_once __DIR__ . '/includes/pdf_generator.php';

    // If we reach here, something went wrong with the PDF generation
    echo "Error generating PDF. Please check the logs.";
?>

<!-- 
Purpose:
  This file acts as a controller that validates input, prepares the environment,
  and delegates the PDF generation task to pdf_generator.php.
  It ensures that only authorized users can generate a report card for a specific student.
-->