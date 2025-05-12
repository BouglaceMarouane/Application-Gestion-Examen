<?php
    // Check if user is logged in
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Check if user is admin
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    // Check if user is student
    function isTeacher() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
    }

    // Check if user is student
    function isStudent() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant';
    }

    // Redirect if not logged in
    function requireLogin() {
        if (!isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
    
    // Redirect if not admin or teacher
    function requireAdminOrTeacher() {
        requireLogin();
        if (!isAdmin() && !isTeacher()) {
            header("Location: ../index.php");
            exit();
        }
    }

    // Redirect if not student
    function requireStudent() {
        requireLogin();
        if (!isStudent()) {
            header("Location: ../index.php");
            exit();
        }
    }

    function getStudentInfo($conn, $student_id) {
        $sql = "SELECT e.id, e.nom_complet, e.date_naissance, e.email,
                    c.nom as classe_nom, f.filiere as filiere_nom
                FROM etudiants e 
                LEFT JOIN classes c ON e.classe_id = c.id
                LEFT JOIN filiere f ON e.filiere_id = f.id_filiere
                WHERE e.id = :student_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function getStudentResults($conn, $student_id) {
        $sql = "SELECT n.id, n.note, n.commentaire, e.exam_title, e.date_examen, 
                    e.type_examen, e.bareme, m.nom as matiere, m.coefficient,
                    c.nom as classe 
                FROM notes n 
                JOIN examens e ON n.examen_id = e.id 
                JOIN matieres m ON e.matiere_id = m.id 
                JOIN classes c ON e.classe_id = c.id 
                WHERE n.etudiant_id = :student_id 
                ORDER BY e.date_examen DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getStudentAverageBySubject($conn, $student_id) {
        $sql = "SELECT m.nom as matiere, m.coefficient, AVG(n.note / e.bareme * 20) as moyenne, 
                    COUNT(n.id) as nb_notes 
                FROM notes n 
                JOIN examens e ON n.examen_id = e.id 
                JOIN matieres m ON e.matiere_id = m.id 
                WHERE n.etudiant_id = :student_id 
                GROUP BY m.id 
                ORDER BY m.nom";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


?>