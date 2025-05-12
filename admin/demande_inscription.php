<?php

    session_start();
    require_once '../config/connection.php';
    require_once '../includes/functions.php';

    // Check if user is admin or teacher
    requireAdminOrTeacher();

    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    $action = $_GET['action'] ?? 'pending';
    $success = '';
    $error = '';

    // Process POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        if (!$studentId) {
            $error = "Invalid or missing student ID.";
        } else {
            try {
                $conn = getConnection();
                if (isset($_POST['validate'])) {
                    $stmt = $conn->prepare("UPDATE etudiants SET status = 'validated', is_validated = 1 WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $success = "Student has been validated successfully.";
                } elseif (isset($_POST['reject'])) {
                    $stmt = $conn->prepare("UPDATE etudiants SET status = 'rejected', is_validated = 0 WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $success = "Registration rejected.";
                } elseif (isset($_POST['restore'])) {
                    $stmt = $conn->prepare("UPDATE etudiants SET status = 'pending' , is_validated = 0 WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $success = "Student has been re-registered.";
                } elseif (isset($_POST['delete'])) {
                    $stmt = $conn->prepare("DELETE FROM etudiants WHERE id = ?");
                    $stmt->execute([$studentId]);
                    $success = "Student has been deleted.";
                } else {
                    $error = "Action not recognized.";
                }
            } catch (PDOException $e) {
                $error = "Error during operation: " . $e->getMessage();
            }
        }
    }

    // Retrieve data
    try {
        $conn = getConnection();

        $validStatus = match($action) {
            'validated' => 'validated',
            'rejected' => 'rejected',
            default => 'pending'
        };

        $query = "
            SELECT e.*, u.email AS user_email, f.filiere, c.nom AS classe_nom
            FROM etudiants e
            JOIN users u ON e.user_id = u.id
            JOIN filiere f ON e.filiere_id = f.id_filiere
            JOIN classes c ON e.classe_id = c.id
            WHERE e.status = ?
            ORDER BY " . ($action === 'pending' ? "e.created_at DESC" : "e.nom_complet ASC");

        $stmt = $conn->prepare($query);
        $stmt->execute([$validStatus]);
        $students = $stmt->fetchAll();

        // Preload majors and classes once (if needed)
        $filiereList = $conn->query("SELECT * FROM filiere")->fetchAll();
        $classesList = $conn->query("SELECT * FROM classes")->fetchAll();

    } catch (PDOException $e) {
        $error = "Error retrieving data: " . $e->getMessage();
        $students = [];
    }

?>


<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - School Exam Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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

        
        body.dark-mode .table th,
        body.dark-mode .table td,
        body.dark-mode .form-control,
        body.dark-mode .form-select {
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
                    <a class="nav-link active" data-bs-toggle="collapse" href="#elevesSubmenu" role="button" aria-expanded="false" aria-controls="elevesSubmenu">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                    <div class="collapse" id="elevesSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link active" href="demande_inscription.php">Registration Requests</a>
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
                    <a class="nav-link" href="exams.php">
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

        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <span class="navbar-text ms-auto">
                        Welcome, <strong id="userNameDisplay"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?></strong>
                    </span>
                </div>
            </nav>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Registration Management</h1>
                    <div class="text-muted" id='dateDisplay'>
                        <i class="far fa-calendar-alt"></i> <span id="currentDate"><?php echo date('d/m/Y')?></span>
                    </div>
                </div>
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'pending' ? 'active' : ''; ?>" href="?action=pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'validated' ? 'active' : ''; ?>" href="?action=validated">Validated</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'rejected' ? 'active' : ''; ?>" href="?action=rejected">Rejected</a>
                    </li>
                </ul>


                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <?php if (empty($students)): ?>
                    <div class="alert alert-info alert-lg">
                        <?= $action === 'pending' ? "No pending registrations." : "No validated students."; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Date of Birth</th>
                                    <th>Major</th>
                                    <th>Class</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= $student['id']; ?></td>
                                        <td><?= htmlspecialchars($student['nom_complet']); ?></td>
                                        <td><?= htmlspecialchars($student['user_email']); ?></td>
                                        <td><?= $student['date_naissance']; ?></td>
                                        <td><?= htmlspecialchars($student['filiere']); ?></td>
                                        <td><?= htmlspecialchars($student['classe_nom']); ?></td>
                                        <td><?= $student['created_at']; ?></td>
                                        <td>
                                            <?php if ($action === 'pending'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                                    <button type="submit" name="validate" class="btn btn-success btn-sm col-10">Validate</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Reject this registration?');">
                                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                                    <button type="submit" name="reject" class="btn mt-2 btn-danger btn-sm col-10">Reject</button>
                                                </form>
                                            <?php elseif($action === 'validated'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                                    <button type="submit" name="delete" class="btn mt-2 btn-danger btn-sm">Delete</button>
                                                </form>
                                            <?php elseif ($action === 'rejected'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                                    <button type="submit" name="restore" class="btn btn-warning btn-sm col-8">Re-register</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                                    <button type="submit" name="delete" class="btn mt-2 btn-danger btn-sm col-8">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                        // Fetch majors and classes
                                        $filiereList = $conn->query("SELECT * FROM filiere")->fetchAll();
                                        $classesList = $conn->query("SELECT * FROM classes")->fetchAll();
                                    ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Alert for notifications -->
    <div class="alert-container" id="alertContainer"></div>
        <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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
        // Helper function to show alerts
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertElement = document.createElement('div');
            alertElement.className = `alert alert-${type} alert-dismissible fade show`;
            alertElement.role = 'alert';
            alertElement.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            alertContainer.appendChild(alertElement);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertElement.parentNode) {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Add event listener to major dropdown
        document.getElementById('filiere').addEventListener('change', function() {
            const filiereId = this.value;
            const classeDropdown = document.getElementById('classe_id');
            
            // Clear existing options
            classeDropdown.innerHTML = '<option value="">Loading classes...</option>';
            
            if (filiereId) {
                // Fetch classes for the selected major using AJAX
                fetch(`get_classes.php?filiere_id=${filiereId}`) 
                    .then(response => response.json())
                    .then(data => {
                        classeDropdown.innerHTML = '<option value="">Select a class</option>';
                        
                        if (data.length > 0) {
                            data.forEach(classe => {
                                const option = document.createElement('option');
                                option.value = classe.id;
                                option.textContent = classe.nom;
                                classeDropdown.appendChild(option);
                            });
                        } else {
                            classeDropdown.innerHTML = '<option value="">No classes available</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching classes:', error);
                        classeDropdown.innerHTML = '<option value="">Error loading classes</option>';
                    });
            } else {
                classeDropdown.innerHTML = '<option value="">Select a major first</option>';
            }
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
    </script>

</body>
</html>
