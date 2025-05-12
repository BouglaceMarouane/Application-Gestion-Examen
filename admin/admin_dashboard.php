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

    $pdo = getConnection();

    // Number of students
    $studentCount = $pdo->query("SELECT COUNT(*) FROM etudiants WHERE status = 'validated'")->fetchColumn();

    // Number of classes
    $classCount = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

    // Number of subjects
    $subjectCount = $pdo->query("SELECT COUNT(*) FROM matieres")->fetchColumn();

    // Number of exams
    $examCount = $pdo->query("SELECT COUNT(*) FROM examens")->fetchColumn();

    $activities = $pdo->query("
        SELECT * FROM (
            -- Student activities
            SELECT
                'student' AS type,
                CONCAT('New student registered: ', e.nom_complet, ' in class ', c.nom) AS description,
                e.created_at AS date,
                e.id AS entity_id
            FROM etudiants e
            JOIN classes c ON e.classe_id = c.id
            WHERE e.created_at IS NOT NULL
            
            UNION ALL
            
            -- Exam activities
            SELECT
                'exam' AS type,
                CONCAT('New exam created: ', ex.exam_title, ' for class ', c.nom) AS description,
                ex.date_creation AS date,
                ex.id AS entity_id
            FROM examens ex
            JOIN classes c ON ex.classe_id = c.id
            
            UNION ALL
            
            -- Note activities
            SELECT
                'note' AS type,
                CONCAT('Grade recorded: ', n.note, '/20 for ', et.nom_complet, ' in ', ex.exam_title, '') AS description,
                n.date_creation AS date,
                n.id AS entity_id
            FROM notes n
            JOIN etudiants et ON n.etudiant_id = et.id
            JOIN examens ex ON n.examen_id = ex.id
            
            UNION ALL
            
            -- Class activities - based on creation timestamp
            SELECT
                'class' AS type,
                CONCAT('Class created: ', c.nom, ' (', c.niveau, ')') AS description,
                c.date_creation AS date,
                c.id AS entity_id
            FROM classes c
            
            UNION ALL
            
            -- Matiere activities - based on subjects in the system
            SELECT
                'matiere' AS type,
                CONCAT('Subject available: ', m.nom) AS description,
                m.date_creation AS date,
                m.id AS entity_id
            FROM matieres m
            
        ) AS recent_activities
        ORDER BY date DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming exams
    $upcomingExamsStmt = $pdo->query("
        SELECT 
            e.exam_title, 
            e.date_examen AS exam_date, 
            e.start_time, 
            e.end_time,
            m.nom AS matiere, 
            c.nom AS classe
        FROM examens e
        JOIN matieres m ON e.matiere_id = m.id
        JOIN classes c ON e.classe_id = c.id
        WHERE e.date_examen >= CURDATE()
        ORDER BY e.date_examen ASC
        LIMIT 5
    ");
    $upcomingExams = $upcomingExamsStmt->fetchAll(PDO::FETCH_ASSOC);

    $recentGradesStmt = $pdo->query("
        SELECT et.nom_complet, m.nom AS matiere, c.nom AS classe, n.note, n.date_creation,
            ex.bareme, ex.type_examen
        FROM notes n
        JOIN etudiants et ON n.etudiant_id = et.id
        JOIN examens ex ON n.examen_id = ex.id
        JOIN matieres m ON ex.matiere_id = m.id
        JOIN classes c ON et.classe_id = c.id
        WHERE et.status = 'validated'
        ORDER BY n.date_creation DESC
        LIMIT 5
    ");
    $recentGrades = $recentGradesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Exam Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        body.dark-mode .card .card-body .list-group-item,       
        body.dark-mode .sidebar,
        body.dark-mode .card,
        body.dark-mode .stat-card,
        body.dark-mode .table,
        body.dark-mode .modal-content {
            background-color: #16213e !important;
            color: #e6e6e6 !important;
        }


        body.dark-mode .card .card-header,
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
<body  id="mainBody" class="">
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="p-4">
                <h4 class="text-white">Exams Management</h4>
                <p class="text-light" id="userTypeDisplay">Administrator</p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="admin_dashboard.php">
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

            <!-- Dashboard Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Dashboard</h1>
                    <div class="text-muted" id='dateDisplay'>
                        <i class="far fa-calendar-alt"></i> <span id="currentDate"><?php echo date('d/m/Y')?></span>
                    </div>
                </div>
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Students</div>
                                        <div class="h5 mb-0 font-weight-bold" id="studentCount"><?= $studentCount ?></div>
                                        <div class="text-xs mt-1">Students Enrolled</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Classes</div>
                                        <div class="h5 mb-0 font-weight-bold" id="classCount"><?= $classCount ?></div>
                                        <div class="text-xs mt-1">Active Classes</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-graduation-cap fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Subjects</div>
                                        <div class="h5 mb-0 font-weight-bold" id="subjectCount"><?= $subjectCount ?></div>
                                        <div class="text-xs mt-1">Subjects Taught</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Exams</div>
                                        <div class="h5 mb-0 font-weight-bold" id="examCount"><?= $examCount ?></div>
                                        <div class="text-xs mt-1">Scheduled Examinations</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h5 class="card-title mb-0 font-weight-bold text-primary">Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php if (!empty($activities)): ?>
                                            <?php foreach ($activities as $activity): ?>
                                                <div class="list-group-item border-0">
                                                    <div class="d-flex align-items-center">
                                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                                            <?php if ($activity['type'] == 'student'): ?>
                                                                <i class="bi bi-person"></i>
                                                            <?php elseif ($activity['type'] == 'exam'): ?>
                                                                <i class="bi bi-journal-text"></i>
                                                            <?php elseif ($activity['type'] == 'class'): ?>
                                                                <i class="bi bi-calendar3"></i>
                                                            <?php elseif ($activity['type'] == 'matiere'): ?>
                                                                <i class="bi bi-book"></i>
                                                            <?php elseif ($activity['type'] == 'note'): ?>
                                                                <i class="bi bi-award"></i>
                                                            <?php elseif ($activity['type'] == 'assignment'): ?>
                                                                <i class="bi bi-file-earmark-text"></i>
                                                            <?php elseif ($activity['type'] == 'attendance'): ?>
                                                                <i class="bi bi-person-check"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-graph-up"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                            <small><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No recent activity found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Exams -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header p-3">
                                <h5 class="card-title mb-0  font-weight-bold text-primary">Upcoming Exams</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($upcomingExams) > 0): ?>
                                    <div id="upcomingExams">
                                        <div class="list-group">
                                            <?php foreach ($upcomingExams as $exam): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($exam['exam_title']); ?></h6>
                                                            <small>
                                                                Subject: <?php echo htmlspecialchars($exam['matiere']); ?><br>
                                                                Class: <?php echo htmlspecialchars($exam['classe']); ?><br>
                                                                Date: <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?><br>
                                                                Time: <?php echo date('h:i A', strtotime($exam['start_time'])); ?> - 
                                                                    <?php echo date('h:i A', strtotime($exam['end_time'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge bg-primary">
                                                            <?php 
                                                                $start = strtotime($exam['start_time']);
                                                                $end = strtotime($exam['end_time']);
                                                                $duration = round(($end - $start) / 60);
                                                                echo $duration . ' mins';
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p>No upcoming exams</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Latest Grades -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header p-3">
                                <h5 class="card-title mb-0 font-weight-bold text-primary">Latest Grades</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recentGrades) > 0): ?>
                                    <div id="recentGrades">
                                        <ul class="list-group">
                                            <?php foreach ($recentGrades as $grade): 
                                                // Force scale based on exam type
                                                $bareme = ($grade['type_examen'] === 'EFM') ? 40 : 20;
                                                $noteSur20 = round(($grade['note'] / $bareme) * 20, 2);
                                                $badgeClass = $noteSur20 >= 15
                                                    ? 'bg-success'
                                                    : ($noteSur20 >= 10 ? 'bg-warning text-dark' : 'bg-danger');
                                            ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div class="ms-2 me-auto">
                                                        <div class="fw-bold"><?= htmlspecialchars($grade['nom_complet']) ?></div>
                                                        <small>
                                                            Class: <?= htmlspecialchars($grade['classe']) ?><br>
                                                            Subject: <?= htmlspecialchars($grade['matiere']) ?><br>
                                                            Type: <?= htmlspecialchars($grade['type_examen']) ?><br>
                                                            Date: <?= date('d M Y H:i', strtotime($grade['date_creation'])) ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge <?= $badgeClass ?> rounded-pill"
                                                        title="Equivalent to <?= $noteSur20 ?>/20">
                                                        <?= $grade['note'] ?>/<?= $bareme ?> (<?= $noteSur20 ?>/20)
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <p>No recent grades available</p>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </div>

    <!-- Alert for notifications -->
    <div class="alert-container" id="alertContainer"></div>

    <script>
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

        // Dark mode toggle functionality
        document.addEventListener("DOMContentLoaded", () => {
        // Get the dark mode toggle button
        const darkModeToggle = document.getElementById("darkModeToggle")
        const body = document.getElementById("mainBody")

        // Check if user previously enabled dark mode
        if (localStorage.getItem("darkMode") === "enabled") {
            body.classList.add("dark-mode")
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode'
        }

        // Add click event to toggle button
        darkModeToggle.addEventListener("click", () => {
            // Toggle dark mode class
            body.classList.toggle("dark-mode")

            // Save preference to localStorage
            if (body.classList.contains("dark-mode")) {
            localStorage.setItem("darkMode", "enabled")
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode'
            } else {
            localStorage.setItem("darkMode", "disabled")
            darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode'
            }
        })
        })
    </script>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
