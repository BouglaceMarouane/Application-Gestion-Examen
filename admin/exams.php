<?php

    session_start();
    require_once '../config/connection.php';
    require_once '../includes/functions.php';

    // Check if user is admin or teacher
    requireAdminOrTeacher();

    // Prevent browser caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    $error = '';
    $message = '';
    $messageType = '';

    // Handle form submissions (previously AJAX)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $conn = getConnection();
            
            // ADD EXAM
            if (isset($_POST['action']) && $_POST['action'] === 'add') {
                // Add exam validation
                if (empty($_POST['examTitle']) || empty($_POST['examSubject']) || 
                    empty($_POST['examClass']) || empty($_POST['examDate']) || 
                    empty($_POST['examStartTime']) || empty($_POST['examEndTime']) || 
                    empty($_POST['examType']) || empty($_POST['examBareme'])) {
                    $error = 'All fields are required';
                } else {
                    // Begin transaction
                    $conn->beginTransaction();
                    
                    // Insert new exam
                    $stmt = $conn->prepare("INSERT INTO examens (exam_title, matiere_id, classe_id, date_examen, 
                                            start_time, end_time, type_examen, bareme, description, created_by) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                    $result = $stmt->execute([
                        $_POST['examTitle'],
                        $_POST['examSubject'],
                        $_POST['examClass'],
                        $_POST['examDate'],
                        $_POST['examStartTime'],
                        $_POST['examEndTime'],
                        $_POST['examType'],
                        $_POST['examBareme'],
                        $_POST['examDescription']
                    ]);
                    
                    if (!$result) {
                        $conn->rollBack();
                        $error = 'Error adding exam';
                    } else {
                        $conn->commit();
                        $message = 'Exam added successfully';
                        $messageType = 'success';
                    }
                }
            } 
            // EDIT EXAM
            elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
                // Edit exam validation
                if (empty($_POST['id']) || empty($_POST['editExamTitle']) || empty($_POST['editExamSubject']) || 
                    empty($_POST['editExamClass']) || empty($_POST['editExamDate']) || 
                    empty($_POST['editExamStartTime']) || empty($_POST['editExamEndTime']) || 
                    empty($_POST['editExamType']) || empty($_POST['editExamBareme'])) {
                    $error = 'All fields are required';
                } else {
                    // Begin transaction
                    $conn->beginTransaction();
                    
                    // Update exam
                    $stmt = $conn->prepare("UPDATE examens SET 
                                            exam_title = ?, 
                                            matiere_id = ?,
                                            classe_id = ?,
                                            date_examen = ?,
                                            start_time = ?,
                                            end_time = ?,
                                            type_examen = ?,
                                            bareme = ?,
                                            description = ?
                                            WHERE id = ?");
                    $result = $stmt->execute([
                        $_POST['editExamTitle'],
                        $_POST['editExamSubject'],
                        $_POST['editExamClass'],
                        $_POST['editExamDate'],
                        $_POST['editExamStartTime'],
                        $_POST['editExamEndTime'],
                        $_POST['editExamType'],
                        $_POST['editExamBareme'],
                        $_POST['editExamDescription'],
                        $_POST['id']
                    ]);
                    
                    if (!$result) {
                        $conn->rollBack();
                        $error = 'Error updating exam';
                    } else {
                        $conn->commit();
                        $message = 'Exam updated successfully';
                        $messageType = 'success';
                    }
                }
            } 
            // DELETE EXAM
            elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
                // Delete exam validation
                if (empty($_POST['id'])) {
                    $error = 'Invalid exam ID';
                } else {
                    // Check if there are grades for this exam
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM notes WHERE examen_id = ?");
                    $stmt->execute([$_POST['id']]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $error = 'Cannot delete this exam because it has grades associated with it';
                    } else {
                        // Begin transaction
                        $conn->beginTransaction();
                        
                        // Delete exam
                        $stmt = $conn->prepare("DELETE FROM examens WHERE id = ?");
                        $result = $stmt->execute([$_POST['id']]);
                        
                        if (!$result) {
                            $conn->rollBack();
                            $error = 'Error during deletion';
                        } else {
                            $conn->commit();
                            $message = 'Exam deleted successfully';
                            $messageType = 'success';
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Get all majors, subjects and classes for dropdowns
    try {
        $conn = getConnection();
        
        // Get majors
        $stmt = $conn->query("SELECT id_filiere, filiere FROM filiere ORDER BY filiere");
        $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get subjects
        $stmt = $conn->query("SELECT id, nom FROM matieres ORDER BY nom");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get classes
        $stmt = $conn->query("SELECT c.id, c.nom, c.niveau, f.filiere 
                            FROM classes c 
                            LEFT JOIN filiere f ON c.filiere_id = f.id_filiere 
                            ORDER BY c.nom");
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = 'Error loading data: ' . $e->getMessage();
        $filieres = [];
        $subjects = [];
        $classes = [];
    }

    // Fetch exam details for edit if ID is provided via GET
    $examToEdit = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("
                SELECT e.* 
                FROM examens e
                WHERE e.id = ?
            ");
            $stmt->execute([$_GET['edit']]);
            $examToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Error loading exam: ' . $e->getMessage();
        }
    }
?>

<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management - School Exam Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body.dark-mode .navbar,
        body.dark-mode {
            background-color: #1a1a2e !important;
            color: #e6e6e6;
        }

        body.dark-mode .navbar{
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        
        body.dark-mode .sidebar,
        body.dark-mode .card,
        body.dark-mode .table,
        body.dark-mode .modal-content {
            background-color: #16213e !important;
            color: #e6e6e6 !important;
        }

        body.dark-mode .card .card-header,
        body.dark-mode .table th,
        body.dark-mode .table td{
            color: #e6e6e6 !important;
            background-color: #1f2833 !important;
            border-color: #34495e;
        }

        body.dark-mode .navbar .navbar-text,
        body.dark-mode #dateDisplay,
        body.dark-mode .nav-link,
        body.dark-mode .btn-outline-light {
            color: #e6e6e6 !important;
        }

        body.dark-mode .alert {
            background-color: #2c3e50 !important;
            color: #e6e6e6 !important;
        }

        body.dark-mode .nav-tabs .nav-link.active {
            background-color: #2c3e50 !important;
            border-color: #2c3e50 !important;
            color: #fff !important;
        }

    </style>
</head>
<body id="mainBody" class="">
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="p-4">
                <h4 class="text-white">Exams Management</h4>
                <p class="text-light" id="userTypeDisplay">Administrator</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#elevesSubmenu" role="button" aria-expanded="false" aria-controls="elevesSubmenu">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                    <div class="collapse" id="elevesSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link" href="demande_inscription.php">Registration Requests</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="students.php">Student Management</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="classes.php">
                        <i class="fas fa-graduation-cap"></i> Classes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="subjects.php">
                        <i class="fas fa-book"></i> Subjects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="exams.php">
                        <i class="fas fa-file-alt"></i> Exams
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="grades.php">
                        <i class="fas fa-clipboard-list"></i> Grades
                    </a>
                </li>
                <li class="nav-item admin-only">
                    <a class="nav-link" href="student_reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
            </ul>
            <div class="p-2">
                <button id="darkModeToggle" class="btn btn-outline-light btn-sm mb-2 w-100">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
                <a href='../includes/logout.php' id="logoutButton" class="btn btn-danger btn-sm w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Overlay -->
        <div id="sidebarOverlay"></div>

        <!-- Sidebar toggle button -->
        <button id="sidebarToggle" class="btn btn-outline-secondary m-3 d-md-none">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <span class="navbar-text ms-auto">
                        Welcome, <strong id="userNameDisplay"> <?php echo htmlspecialchars($_SESSION['name']); ?> </strong>
                    </span>
                </div>
            </nav>

            <!-- Exams Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Exam Management</h1>
                    <a href="exams.php?add=1" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Exam
                    </a>
                </div>

                <!-- Display error or success messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Add Exam Form -->
                <?php if (isset($_GET['add']) && $_GET['add'] == 1): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Add Exam</h6>
                    </div>
                    <div class="card-body">
                        <form action="exams.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="examTitle" class="form-label">Exam Title</label>
                                <input type="text" class="form-control" id="examTitle" name="examTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="examSubject" class="form-label">Subject</label>
                                <select class="form-select" id="examSubject" name="examSubject" required>
                                    <option value="">Select a subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="examClass" class="form-label">Class</label>
                                <select class="form-select" id="examClass" name="examClass" required>
                                    <option value="">Select a class</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['nom'] . ' (' . $class['niveau'] . ' - ' . $class['filiere'] . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="examDate" class="form-label">Exam Date</label>
                                <input type="date" class="form-control" id="examDate" name="examDate" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="examStartTime" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="examStartTime" name="examStartTime" required>
                                </div>
                                <div class="col">
                                    <label for="examEndTime" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="examEndTime" name="examEndTime" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="examType" class="form-label">Exam Type</label>
                                    <select class="form-select" id="examType" name="examType" required>
                                        <option value="CC">Continuous Assessment (CC)</option>
                                        <option value="EFM">Final Exam (EFM)</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="examBareme" class="form-label">Scale</label>
                                    <select class="form-select" id="examBareme" name="examBareme" required>
                                        <option value="20">20</option>
                                        <option value="40">40</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="examDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="examDescription" name="examDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Add</button>
                                <a href="exams.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Exam Form -->
                <?php if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $examToEdit): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Edit Exam</h6>
                    </div>
                    <div class="card-body">
                        <form action="exams.php" method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $examToEdit['id'] ?>">
                            <div class="mb-3">
                                <label for="editExamTitle" class="form-label">Exam Title</label>
                                <input type="text" class="form-control" id="editExamTitle" name="editExamTitle" value="<?= htmlspecialchars($examToEdit['exam_title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="editExamSubject" class="form-label">Subject</label>
                                <select class="form-select" id="editExamSubject" name="editExamSubject" required>
                                    <option value="">Select a subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= ($subject['id'] == $examToEdit['matiere_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editExamClass" class="form-label">Class</label>
                                <select class="form-select" id="editExamClass" name="editExamClass" required>
                                    <option value="">Select a class</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= ($class['id'] == $examToEdit['classe_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['nom'] . ' (' . $class['niveau'] . ' - ' . $class['filiere'] . ')') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>                     
                            <div class="mb-3">
                                <label for="editExamDate" class="form-label">Exam Date</label>
                                <input type="date" class="form-control" id="editExamDate" name="editExamDate" value="<?= $examToEdit['date_examen'] ?>" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="editExamStartTime" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="editExamStartTime" name="editExamStartTime" value="<?= substr($examToEdit['start_time'], 0, 5) ?>" required>
                                </div>
                                <div class="col">
                                    <label for="editExamEndTime" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="editExamEndTime" name="editExamEndTime" value="<?= substr($examToEdit['end_time'], 0, 5) ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="editExamType" class="form-label">Exam Type</label>
                                    <select class="form-select" id="editExamType" name="editExamType" required>
                                        <option value="CC" <?= ($examToEdit['type_examen'] == 'CC') ? 'selected' : '' ?>>Continuous Assessment (CC)</option>
                                        <option value="EFM" <?= ($examToEdit['type_examen'] == 'EFM') ? 'selected' : '' ?>>Final Exam (EFM)</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="editExamBareme" class="form-label">Scale</label>
                                    <select class="form-select" id="editExamBareme" name="editExamBareme" required>
                                        <option value="20" <?= ($examToEdit['bareme'] == 20) ? 'selected' : '' ?>>20</option>
                                        <option value="40" <?= ($examToEdit['bareme'] == 40) ? 'selected' : '' ?>>40</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editExamDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editExamDescription" name="editExamDescription" rows="3"><?= htmlspecialchars($examToEdit['description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="exams.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Delete Form (Confirmation) -->
                <?php if (isset($_GET['delete']) && is_numeric($_GET['delete'])): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger">Delete Exam</h6>
                    </div>
                    <div class="card-body">
                        <p>Are you sure you want to delete this exam? This action is irreversible.</p>
                        <?php
                        try {
                            $conn = getConnection();
                            $stmt = $conn->prepare("
                                SELECT e.exam_title, m.nom as matiere, c.nom as classe
                                FROM examens e
                                JOIN matieres m ON e.matiere_id = m.id
                                JOIN classes c ON e.classe_id = c.id
                                WHERE e.id = ?
                            ");
                            $stmt->execute([$_GET['delete']]);
                            $exam = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($exam): 
                        ?>
                        <p class="fw-bold text-center">
                            <?= htmlspecialchars($exam['exam_title']) ?> - 
                            <?= htmlspecialchars($exam['matiere']) ?> - 
                            <?= htmlspecialchars($exam['classe']) ?>
                        </p>
                        <form action="exams.php" method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $_GET['delete'] ?>">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-danger">Confirm Deletion</button>
                                <a href="exams.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                        <?php else: ?>
                        <p>Exam not found.</p>
                        <a href="exams.php" class="btn btn-secondary">Back</a>
                        <?php endif; 
                        } catch (PDOException $e) {
                            echo "<p>Error: " . $e->getMessage() . "</p>";
                            echo "<a href='exams.php' class='btn btn-secondary'>Back</a>";
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Exams Table -->
                <?php if (!isset($_GET['add']) && !isset($_GET['edit']) && !isset($_GET['delete'])): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">Exam List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="examsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Major</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Type</th>
                                        <th>Scale</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $conn = getConnection();
                                        
                                        // Fetch exams with related information
                                        $query = "
                                        SELECT e.id, e.exam_title, m.nom as matiere, c.nom as classe, 
                                               f.filiere, e.date_examen, e.start_time, e.end_time, e.type_examen, 
                                               e.bareme, e.description
                                        FROM examens e
                                        JOIN matieres m ON e.matiere_id = m.id
                                        JOIN classes c ON e.classe_id = c.id
                                        JOIN filiere f ON c.filiere_id = f.id_filiere
                                        ORDER BY e.date_examen DESC, e.start_time ASC
                                        ";
                                        
                                        $stmt = $conn->query($query);
                                        $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($examens as $examen):
                                            // Format date
                                            $date = new DateTime($examen['date_examen']);
                                            $formatted_date = $date->format('m/d/Y');
                                            
                                            // Format times
                                            $start_time = substr($examen['start_time'], 0, 5);
                                            $end_time = substr($examen['end_time'], 0, 5);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($examen['exam_title']); ?></td>
                                        <td><?php echo htmlspecialchars($examen['matiere']); ?></td>
                                        <td><?php echo htmlspecialchars($examen['classe']); ?></td>
                                        <td><?php echo htmlspecialchars($examen['filiere']); ?></td>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo $start_time . ' - ' . $end_time; ?></td>
                                        <td><?php echo $examen['type_examen']; ?></td>
                                        <td><?php echo $examen['bareme']; ?></td>
                                        <td class='col-2'>
                                            <a href="exams.php?edit=<?= $examen['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="exams.php?delete=<?= $examen['id'] ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='9'>Error loading exams: " . $e->getMessage() . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Alert for notifications -->
    <div class="alert-container position-fixed top-0 end-0 p-3" style="z-index: 1060;" id="alertContainer"></div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            var examsTable = $('#examsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/en-GB.json"
                },
                "pageLength": 10,
                "ordering": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 8 } // Disable sorting on action column
                ]
            });
            
            // Apply search filtering
            $('#searchInput').on('keyup', function() {
                examsTable.search(this.value).draw();
            });
            
            // Class filter
            $('#classFilter').on('change', function() {
                examsTable.column(4).search(this.value).draw();
            });
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#searchInput').val('');
                $('#classFilter').val('');
                examsTable.search('').columns().search('').draw();
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.getElementById('sidebarToggle');

            toggle.addEventListener('click', function () {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });

            // Swipe to close
            let startX = 0;
            sidebar.addEventListener('touchstart', function (e) {
                startX = e.touches[0].clientX;
            });

            sidebar.addEventListener('touchmove', function (e) {
                const deltaX = e.touches[0].clientX - startX;
                if (deltaX < -50) { // swipe left
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('darkModeToggle');
            const isDark = localStorage.getItem('darkMode') === 'enabled';

            if (isDark) {
                document.body.classList.add('dark-mode');
                toggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            }

            toggleBtn.addEventListener('click', function () {
                document.body.classList.toggle('dark-mode');
                const enabled = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', enabled ? 'enabled' : 'disabled');
                toggleBtn.innerHTML = enabled
                    ? '<i class="fas fa-sun"></i> Light Mode'
                    : '<i class="fas fa-moon"></i> Dark Mode';
            });
        });

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            toggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        } else {
            document.body.classList.remove('dark-mode');
            toggleBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
        }
    </script>

</body>
</html>
