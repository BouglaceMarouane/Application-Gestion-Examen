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
    $error = '';

    try {
        $conn = getConnection();

        // Get list of majors from database
        $stmt = $conn->query("SELECT id_filiere, filiere FROM filiere");
        $filiere_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                // Add class validation
                if (empty($_POST['className']) || empty($_POST['classLevel']) || empty($_POST['filiere'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }

                $nom = $_POST['className'];
                $niveau = $_POST['classLevel']; // important
                $filiere_id = !empty($_POST['filiere']) ? $_POST['filiere'] : null;
                
                // Insert new class
                $stmt = $conn->prepare("INSERT INTO classes (nom, niveau, filiere_id) VALUES (?, ?, ?)");
                $result = $stmt->execute([
                    $nom ,
                    $niveau,
                    $filiere_id
                ]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Class added successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error adding class']);
                }
            } elseif ($action === 'edit') {
                // Edit class validation
                if (empty($_POST['id']) || empty($_POST['editClassName']) || empty($_POST['editClassLevel']) || empty($_POST['editFiliere'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }

                $editnom = $_POST['editClassName'];
                $editniveau = $_POST['editClassLevel']; // important
                $editfiliere_id = !empty($_POST['editFiliere']) ? $_POST['editFiliere'] : null;
                
                // Update class
                $stmt = $conn->prepare("UPDATE classes SET 
                                        nom = ?, 
                                        niveau = ?, 
                                        filiere_id = ? 
                                        WHERE id = ?");
                $result = $stmt->execute([
                    $editnom,
                    $editniveau,
                    $editfiliere_id,
                    $_POST['id']
                ]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Class information updated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error updating class']);
                }
            } elseif ($action === 'delete') {
                // Delete class validation
                if (empty($_POST['id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid class ID']);
                    exit;
                }
                
                // Check if there are students in this class
                $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiants WHERE classe_id = ?");
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Cannot delete this class because it contains students']);
                    exit;
                }
                
                // Delete class
                $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Class deleted successfully']);
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
    <title>Class Management - School Exam Management System</title>
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
                    <a class="nav-link active" href="classes.php">
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

            <!-- Classes Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Class Management</h1>
                    <button id="addClassBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Class
                    </button>
                </div>

                <!-- Classes Table -->
                <div class="card shadow h-100 mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">Class List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="classesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Level</th>
                                        <th>Major</th>
                                        <th>Number of Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // First, let's add filiere_id to classes table if it doesn't exist
                                        try {
                                            $conn->query("ALTER TABLE classes ADD COLUMN IF NOT EXISTS filiere_id INT DEFAULT NULL");
                                            $conn->query("ALTER TABLE classes ADD CONSTRAINT fk_classe_filiere FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere)");
                                        } catch (PDOException $e) {
                                            // Column might already exist, continue
                                        }

                                        // Now fetch classes with filiere information and student count
                                        $stmt = $conn->query("
                                        SELECT c.id, c.nom, c.niveau, c.filiere_id, f.filiere AS filiere_nom,
                                               (SELECT COUNT(*) FROM etudiants e WHERE e.classe_id = c.id) AS nb_etudiants
                                        FROM classes c
                                        LEFT JOIN filiere f ON c.filiere_id = f.id_filiere
                                        ORDER BY c.nom
                                        ");
                                        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($classes as $classe):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($classe['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($classe['niveau']); ?></td>
                                        <td><?php echo htmlspecialchars($classe['filiere_nom'] ?? 'Not assigned'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $classe['nb_etudiants'] > 0 ? 'primary' : 'secondary'; ?> p-2">
                                                <?php echo $classe['nb_etudiants']; ?> student<?php echo $classe['nb_etudiants'] > 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info editBtn" 
                                                    data-id="<?= $classe['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($classe['nom']) ?>"
                                                    data-niveau="<?= htmlspecialchars($classe['niveau']) ?>"
                                                    data-filiere="<?= $classe['filiere_id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger deleteBtn"
                                                    data-id="<?= $classe['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($classe['nom']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='5'>Error loading classes: " . $e->getMessage() . "</td></tr>";
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

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">Add Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addClassForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="className" class="form-label">Class Name</label>
                            <input type="text" class="form-control rounded-pill" id="className" name="className" required>
                        </div>
                        <div class="mb-3">
                            <label for="classLevel" class="form-label">Level</label>
                            <select class="form-select" id="classLevel" name="classLevel" required>
                                <option value="">Select a level</option>
                                <option value="1er année">1er année</option>
                                <option value="2ème année">2ème année</option>
                            </select>
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

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editClassForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editClassId" name="id">
                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Class Name</label>
                            <input type="text" class="form-control rounded-pill" id="editClassName" name="editClassName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editClassLevel" class="form-label">Level</label>
                            <select class="form-select" id="editClassLevel" name="editClassLevel" required>
                                <option value="">Select a level</option>
                                <option value="1er année">1er année</option>
                                <option value="2ème année">2ème année</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editFiliere" class="form-label">Major</label>
                            <select class="form-select rounded-pill" id="editFiliere" name="editFiliere" required>
                                <option value="">Select a major</option>
                                <?php foreach ($filiere_options as $option): ?>
                                    <option value="<?php echo $option['id_filiere']; ?>">
                                        <?php echo htmlspecialchars($option['filiere']); ?>
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

    <!-- Delete Class Modal -->
    <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassModalLabel">Delete Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this class? This action is irreversible.</p>
                    <p class="fw-bold text-center" id="deleteClassName"></p>
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
        // Initialize DataTable
        $(document).ready(function() {
            var classesTable = $('#classesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/en-GB.json"
                },
                "pageLength": 10,
                "ordering": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 4 } // Disable sorting on action column (index updated to 4)
                ]
            });
        });

        // Show add class modal
        document.getElementById('addClassBtn').addEventListener('click', function() {
            var myModal = new bootstrap.Modal(document.getElementById('addClassModal'));
            myModal.show();
        });

        // Handle add class form submission
        document.getElementById('addClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('classes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addClassModal'));
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
                showAlert('danger', 'An error occurred while adding the class');
            });
        });
        
        // Handle edit class buttons
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editClassId').value = this.dataset.id;
                document.getElementById('editClassName').value = this.dataset.nom;
                document.getElementById('editClassLevel').value = this.dataset.niveau;
                document.getElementById('editFiliere').value = this.dataset.filiere;
                
                var modal = new bootstrap.Modal(document.getElementById('editClassModal'));
                modal.show();
            });
        });
        
        // Handle edit class form submission
        document.getElementById('editClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('classes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('editClassModal'));
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
                showAlert('danger', 'An error occurred while updating the class');
            });
        });
        
        // Handle delete class buttons
        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteClassName').textContent = this.dataset.nom;
                document.getElementById('confirmDeleteBtn').setAttribute('data-id', this.dataset.id);
                
                var modal = new bootstrap.Modal(document.getElementById('deleteClassModal'));
                modal.show();
            });
        });
        
        // Handle confirm delete button
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const classId = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', classId);
            
            fetch('classes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('deleteClassModal'));
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
                showAlert('danger', 'An error occurred while deleting the class');
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
</body>
</html>
