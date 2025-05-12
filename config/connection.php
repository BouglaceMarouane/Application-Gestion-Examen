<?php
    // PDO connection
    function getConnection() {
        try {
            $db = new PDO("mysql:host=localhost;dbname=school_exams_dbs", "root", "");
            // Set PDO error mode to exception
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

?>