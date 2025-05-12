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

    $filiere_options = [];
    $class_options = [];
    $error = '';

    try {
        $conn = getConnection();

        // Get list of majors from database
        $stmt = $conn->query("SELECT id_filiere, filiere FROM filiere");
        $filiere_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch classes from the database
        $stmt = $conn->query("SELECT id, nom FROM classes");
        $class_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }

    // Handle AJAX request separately from page rendering
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        $action = $_POST['action'];
        
        try {
            $conn = getConnection();
            
            if ($action === 'add') {
                // Add student validation
                if (empty($_POST['nom_complet']) || empty($_POST['email']) || 
                    empty($_POST['date_naissance']) || empty($_POST['filiere']) || 
                    empty($_POST['classe_id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }

                // Check if the email is valid
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                }
                
                // Verify if email already exists
                $stmt = $conn->prepare("SELECT id FROM etudiants WHERE email = ?");
                $stmt->execute([$_POST['email']]);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This email is already in use']);
                    exit;
                }
                
                // Insert new student
                $stmt = $conn->prepare("INSERT INTO etudiants (nom_complet, email, date_naissance, filiere_id, classe_id, status) 
                                        VALUES (?, ?, ?, ?, ?, 'validated')");
                $result = $stmt->execute([
                    $_POST['nom_complet'],
                    $_POST['email'],
                    $_POST['date_naissance'],
                    $_POST['filiere'],
                    $_POST['classe_id']
                ]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Student added successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error adding student']);
                }
            } elseif ($action === 'edit') {
                // Edit student validation
                if (empty($_POST['id']) || empty($_POST['nom_complet']) || 
                    empty($_POST['email']) || empty($_POST['date_naissance']) || 
                    empty($_POST['filiere']) || empty($_POST['classe_id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }
                
                // Verify if email already exists for other students
                $stmt = $conn->prepare("SELECT id FROM etudiants WHERE email = ? AND id != ?");
                $stmt->execute([$_POST['email'], $_POST['id']]);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This email is already used by another student']);
                    exit;
                }
                
                // Update student
                $stmt = $conn->prepare("UPDATE etudiants SET 
                                        nom_complet = ?, 
                                        email = ?, 
                                        date_naissance = ?, 
                                        filiere_id = ?, 
                                        classe_id = ? 
                                        WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['nom_complet'],
                    $_POST['email'],
                    $_POST['date_naissance'],
                    $_POST['filiere'],
                    $_POST['classe_id'],
                    $_POST['id']
                ]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Student information updated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error during update']);
                }
            } elseif ($action === 'delete') {
                // Delete student validation
                if (empty($_POST['id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid student ID']);
                    exit;
                }
                
                // Delete student (or set status to deleted if you prefer a soft delete)
                $stmt = $conn->prepare("DELETE FROM etudiants WHERE id = ?");
                // Alternative for soft delete:
                // $stmt = $conn->prepare("UPDATE etudiants SET status = 'deleted' WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Student deleted successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error during deletion']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Action not recognized']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - School Exam Management System</title>
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
                    <a class="nav-link active" data-bs-toggle="collapse" href="#elevesSubmenu" role="button" aria-expanded="false" aria-controls="elevesSubmenu">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                    <div class="collapse" id="elevesSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link" href="demande_inscription.php">Registration Requests</a>
                            </li>
                            <li class="nav-item active">
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

            <!-- Students Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Student Management</h1>
                    <button id="addStudentBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Student
                    </button>
                </div>

                <!-- Students Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">Student List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Full Name</th>
                                        <th>Date of Birth</th>
                                        <th>Email</th>
                                        <th>Major</th>
                                        <th>Class</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $conn->query("
                                        SELECT e.*, c.nom AS classe_nom, f.filiere AS filiere_nom
                                        FROM etudiants e
                                        JOIN classes c ON e.classe_id = c.id
                                        JOIN filiere f ON e.filiere_id = f.id_filiere
                                        WHERE e.status = 'validated' OR e.status IS NULL
                                        ORDER BY e.nom_complet
                                        ");
                                        $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($etudiants as $etudiant):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($etudiant['nom_complet']); ?></td>
                                        <td><?php echo htmlspecialchars($etudiant['date_naissance']); ?></td>
                                        <td><?php echo htmlspecialchars($etudiant['email']); ?></td>
                                        <td><?php echo htmlspecialchars($etudiant['filiere_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($etudiant['classe_nom']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info editBtn" 
                                                    data-id="<?= $etudiant['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($etudiant['nom_complet']) ?>"
                                                    data-email="<?= htmlspecialchars($etudiant['email']) ?>"
                                                    data-date="<?= htmlspecialchars($etudiant['date_naissance']) ?>"
                                                    data-filiere="<?= $etudiant['filiere_id'] ?>"
                                                    data-classe="<?= $etudiant['classe_id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger deleteBtn"
                                                    data-id="<?= $etudiant['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($etudiant['nom_complet']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='6'>Error loading students: " . $e->getMessage() . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addStudentForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_complet" class="form-label">Full Name</label>
                            <input type="text" class="form-control rounded-pill" id="nom_complet" name="nom_complet" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control rounded-pill" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control rounded-pill" id="date_naissance" name="date_naissance" max="<?php echo date('Y-d-m'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="filiere" class="form-label">Major</label>
                            <select class="form-select rounded-pill" id="filiere" name="filiere" required>
                                <option value="">Select a major</option>
                                <?php foreach ($filiere_options as $option): ?>
                                    <option value="<?php echo $option['id_filiere']; ?>">
                                    <?php echo htmlspecialchars($option['filiere']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a major.</div>
                        </div>
                        <div class="mb-3">
                            <label for="classe_id" class="form-label">Class</label>
                            <select class="form-select rounded-pill" id="classe_id" name="classe_id" required>
                                <option value="">Select a major first</option>
                            </select>
                            <div class="invalid-feedback">Please select a class.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editStudentForm">
                    <div class="modal-body">
                        <input type="hidden" id="editStudentId" name="id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Full Name</label>
                            <input type="text" class="form-control rounded-pill" id="editName" name="nom_complet" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control rounded-pill" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBirthDate" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control rounded-pill" id="editBirthDate" name="date_naissance" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFiliere" class="form-label">Major</label>
                            <select class="form-select rounded-pill" id="editFiliere" name="filiere" required>
                                <option value="">Select a major</option>
                                <?php foreach ($filiere_options as $option): ?>
                                    <option value="<?php echo $option['id_filiere']; ?>">
                                        <?php echo htmlspecialchars($option['filiere']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editClass" class="form-label">Class</label>
                            <select class="form-select rounded-pill" id="editClass" name="classe_id" required>
                                <option value="">Select a class</option>
                                <?php foreach ($class_options as $option): ?>
                                    <option value="<?php echo $option['id']; ?>">
                                        <?php echo htmlspecialchars($option['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStudentModalLabel">Delete Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this student? This action is irreversible.</p>
                    <p class="fw-bold text-center" id="deleteStudentName"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
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
        document.querySelector("form").addEventListener("submit", function(e) {
            let valid = true;

            const nom = document.getElementById("nom_complet");
            const email = document.getElementById("email");
            const dob = document.getElementById("date_naissance");

            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Nom complet
            if (nom.value.trim().length < 3) {
                nom.classList.add("is-invalid");
                valid = false;
            }

            // Email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                email.classList.add("is-invalid");
                valid = false;
            }

            // Date de naissance (doit avoir 18+)
            const birthDate = new Date(dob.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            const dayDiff = today.getDate() - birthDate.getDate();
            const fullAge = (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) ? age - 1 : age;

            if (isNaN(birthDate.getTime()) || fullAge < 18) {
                dob.classList.add("is-invalid");
                valid = false;
            }

            if (!valid) {
                e.preventDefault(); // Stop form submission
            }
        });

        // Initialize DataTable
        $(document).ready(function() {
            var studentsTable = $('#studentsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/en-GB.json"
                },
                "pageLength": 10,
                "ordering": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 5 } // Disable sorting on action column
                ]
            });
            
            // Apply search filtering
            $('#searchInput').on('keyup', function() {
                studentsTable.search(this.value).draw();
            });
            
            // Class filter
            $('#classFilter').on('change', function() {
                studentsTable.column(4).search(this.value).draw();
            });
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#searchInput').val('');
                $('#classFilter').val('');
                studentsTable.search('').columns().search('').draw();
            });
        });

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

        // Show add student modal
        document.getElementById('addStudentBtn').addEventListener('click', function() {
            var myModal = new bootstrap.Modal(document.getElementById('addStudentModal'));
            myModal.show();
        });

        // Handle add student form submission
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
                    modal.hide();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while adding the student');
            });
        });
        
        // Handle edit student buttons
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editStudentId').value = this.dataset.id;
                document.getElementById('editName').value = this.dataset.nom;
                document.getElementById('editEmail').value = this.dataset.email;
                document.getElementById('editBirthDate').value = this.dataset.date;
                document.getElementById('editFiliere').value = this.dataset.filiere;
                document.getElementById('editClass').value = this.dataset.classe;
                
                var modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
                modal.show();
            });
        });
        
        // Handle edit student form submission
        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('edit_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
                    modal.hide();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating the student');
            });
        });
        
        // Handle delete student buttons
        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteStudentName').textContent = this.dataset.nom;
                document.getElementById('confirmDeleteBtn').setAttribute('data-id', this.dataset.id);
                
                var modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
                modal.show();
            });
        });
        
        // Handle confirm delete button
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const studentId = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('id', studentId);
            
            fetch('delete_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('deleteStudentModal'));
                    modal.hide();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the student');
            });
        });
        
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


    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
</body>
</html>
