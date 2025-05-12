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

    // Handle AJAX request separately from page rendering
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        $action = $_POST['action'];
        
        try {
            $conn = getConnection();
            
            if ($action === 'add') {
                // Add subject validation
                if (empty($_POST['subjectName']) || empty($_POST['subjectCoefficient'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }
                
                // Check if subject already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM matieres WHERE nom = ?");
                $stmt->execute([$_POST['subjectName']]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This subject already exists']);
                    exit;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert new subject
                $isCommon = isset($_POST['isCommon']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO matieres (nom, coefficient, is_common) VALUES (?, ?, ?)");
                $result = $stmt->execute([
                    $_POST['subjectName'],
                    $_POST['subjectCoefficient'],
                    $isCommon
                ]);
                
                if (!$result) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Error adding the subject']);
                    exit;
                }
                
                $matiereId = $conn->lastInsertId();
                
                // Handle field relationships
                if ($isCommon) {
                    // If common subject, add to all fields
                    $stmt = $conn->prepare("INSERT INTO matiere_filiere (matiere_id, filiere_id) 
                                        SELECT ?, id_filiere FROM filiere");
                    $result = $stmt->execute([$matiereId]);
                } else if (isset($_POST['filieres']) && is_array($_POST['filieres'])) {
                    // Add subject to selected fields
                    $stmt = $conn->prepare("INSERT INTO matiere_filiere (matiere_id, filiere_id) VALUES (?, ?)");
                    
                    foreach ($_POST['filieres'] as $filiereId) {
                        $result = $stmt->execute([$matiereId, $filiereId]);
                        if (!$result) {
                            $conn->rollBack();
                            echo json_encode(['status' => 'error', 'message' => 'Error associating the subject with fields']);
                            exit;
                        }
                    }
                } else if (!$isCommon) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Please select at least one field or mark as a common subject']);
                    exit;
                }
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Subject successfully added']);
                
            } elseif ($action === 'edit') {
                // Edit subject validation
                if (empty($_POST['id']) || empty($_POST['editSubjectName']) || empty($_POST['editSubjectCoefficient'])) {
                    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
                    exit;
                }
                
                // Check if subject name exists for other subjects
                $stmt = $conn->prepare("SELECT COUNT(*) FROM matieres WHERE nom = ? AND id != ?");
                $stmt->execute([$_POST['editSubjectName'], $_POST['id']]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'A subject with this name already exists']);
                    exit;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Update subject
                $isCommon = isset($_POST['editIsCommon']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE matieres SET 
                                        nom = ?, 
                                        coefficient = ?,
                                        is_common = ?
                                        WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['editSubjectName'],
                    $_POST['editSubjectCoefficient'],
                    $isCommon,
                    $_POST['id']
                ]);
                
                if (!$result) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Error updating']);
                    exit;
                }
                
                // Delete existing field relationships
                $stmt = $conn->prepare("DELETE FROM matiere_filiere WHERE matiere_id = ?");
                $stmt->execute([$_POST['id']]);
                
                // Handle field relationships
                if ($isCommon) {
                    // If common subject, add to all fields
                    $stmt = $conn->prepare("INSERT INTO matiere_filiere (matiere_id, filiere_id) 
                                        SELECT ?, id_filiere FROM filiere");
                    $result = $stmt->execute([$_POST['id']]);
                } else if (isset($_POST['editFilieres']) && is_array($_POST['editFilieres'])) {
                    // Add subject to selected fields
                    $stmt = $conn->prepare("INSERT INTO matiere_filiere (matiere_id, filiere_id) VALUES (?, ?)");
                    
                    foreach ($_POST['editFilieres'] as $filiereId) {
                        $result = $stmt->execute([$_POST['id'], $filiereId]);
                        if (!$result) {
                            $conn->rollBack();
                            echo json_encode(['status' => 'error', 'message' => 'Error associating the subject with fields']);
                            exit;
                        }
                    }
                } else if (!$isCommon) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Please select at least one field or mark as a common subject']);
                    exit;
                }
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Subject information successfully updated']);
                
            } elseif ($action === 'delete') {
                // Delete subject validation
                if (empty($_POST['id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID']);
                    exit;
                }
                
                // Check if there are exams using this subject
                $stmt = $conn->prepare("SELECT COUNT(*) FROM examens WHERE matiere_id = ?");
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Cannot delete this subject because it is used in exams']);
                    exit;
                }
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Delete subject field relationships
                $stmt = $conn->prepare("DELETE FROM matiere_filiere WHERE matiere_id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if (!$result) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Error deleting relationships']);
                    exit;
                }
                
                // Delete subject
                $stmt = $conn->prepare("DELETE FROM matieres WHERE id = ?");
                $result = $stmt->execute([$_POST['id']]);
                
                if (!$result) {
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Error deleting']);
                    exit;
                }
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Subject successfully deleted']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Unrecognized action']);
            }
        } catch (PDOException $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get all fields for dropdowns
    try {
        $conn = getConnection();
        $stmt = $conn->query("SELECT id_filiere, filiere FROM filiere ORDER BY filiere");
        $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Error loading fields: ' . $e->getMessage();
        $filieres = [];
    }
?>
<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - School Exam Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
                    <a class="nav-link active" href="subjects.php">
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
            <!-- Subjects Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Subjects Management</h1>
                    <button id="addSubjectBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add a Subject
                    </button>
                </div>

                <!-- Subjects Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">List of Subjects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="subjectsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>Coefficient</th>
                                        <th>Type</th>
                                        <th>Field of Study</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $conn = getConnection();
                                        
                                        // Ensure required tables and columns exist
                                        $queries = [
                                            "ALTER TABLE matieres ADD COLUMN IF NOT EXISTS coefficient INT DEFAULT 1",
                                            "ALTER TABLE matieres ADD COLUMN IF NOT EXISTS is_common BOOLEAN DEFAULT FALSE",
                                            "CREATE TABLE IF NOT EXISTS matiere_filiere (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              matiere_id INT NOT NULL,
                                              filiere_id INT,
                                              FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
                                              FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere) ON DELETE CASCADE,
                                              UNIQUE KEY unique_matiere_filiere (matiere_id, filiere_id)
                                            )"
                                        ];
                                        
                                        foreach ($queries as $query) {
                                            try {
                                                $conn->exec($query);
                                            } catch (PDOException $e) {
                                                // Ignore errors
                                            }
                                        }

                                        // Now fetch matieres with filière information
                                        $query = "
                                        SELECT m.id, m.nom, m.coefficient, m.is_common,
                                               (SELECT COUNT(*) FROM examens e WHERE e.matiere_id = m.id) AS nb_examens,
                                               GROUP_CONCAT(f.filiere SEPARATOR ', ') AS filieres
                                        FROM matieres m
                                        LEFT JOIN matiere_filiere mf ON m.id = mf.matiere_id
                                        LEFT JOIN filiere f ON mf.filiere_id = f.id_filiere
                                        GROUP BY m.id
                                        ORDER BY m.nom
                                        ";
                                        
                                        $stmt = $conn->query($query);
                                        $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Get filière IDs for each subject
                                        $matieresFilieres = [];
                                        foreach ($matieres as $matiere) {
                                            $stmt = $conn->prepare("
                                                SELECT filiere_id 
                                                FROM matiere_filiere 
                                                WHERE matiere_id = ?
                                            ");
                                            $stmt->execute([$matiere['id']]);
                                            $matieresFilieres[$matiere['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                        }

                                        foreach ($matieres as $matiere):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($matiere['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($matiere['coefficient'] ?? '1'); ?></td>
                                        <td><?php echo $matiere['is_common'] ? 'Commune' : 'Spécifique'; ?></td>
                                        <td><?php echo $matiere['filieres'] ? htmlspecialchars($matiere['filieres']) : 'Non assignée'; ?></td>
                                        <td class='col-2'>
                                            <button class="btn btn-sm btn-info editBtn" 
                                                    data-id="<?= $matiere['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($matiere['nom']) ?>"
                                                    data-coefficient="<?= htmlspecialchars($matiere['coefficient'] ?? '1') ?>"
                                                    data-is-common="<?= $matiere['is_common'] ? '1' : '0' ?>"
                                                    data-filieres="<?= htmlspecialchars(json_encode($matieresFilieres[$matiere['id']] ?? [])) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger deleteBtn"
                                                    data-id="<?= $matiere['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($matiere['nom']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='5'>Erreur lors du chargement des matières : " . $e->getMessage() . "</td></tr>";
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add a Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addSubjectForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="subjectName" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="subjectName" name="subjectName" required>
                        </div>
                        <div class="mb-3">
                            <label for="subjectCoefficient" class="form-label">Coefficient</label>
                            <input type="number" class="form-control" id="subjectCoefficient" name="subjectCoefficient" min="1" max="10" value="1" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isCommon" name="isCommon">
                            <label class="form-check-label" for="isCommon">Common Subject (taught in all fields)</label>
                        </div>
                        <div class="mb-3" id="filieresContainer">
                            <label for="filieres" class="form-label">Relevant Fields</label>
                            <select class="form-select" id="filieres" name="filieres[]" multiple>
                                <?php foreach ($filieres as $filiere): ?>
                                <option value="<?= $filiere['id_filiere'] ?>"><?= htmlspecialchars($filiere['filiere']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select the fields relevant to this subject.</small>
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

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit a Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSubjectForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editSubjectId" name="id">
                        <div class="mb-3">
                            <label for="editSubjectName" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="editSubjectName" name="editSubjectName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSubjectCoefficient" class="form-label">Coefficient</label>
                            <input type="number" class="form-control" id="editSubjectCoefficient" name="editSubjectCoefficient" min="1" max="10" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="editIsCommon" name="editIsCommon">
                            <label class="form-check-label" for="editIsCommon">Common Subject (taught in all fields)</label>
                        </div>
                        <div class="mb-3" id="editFilieresContainer">
                            <label for="editFilieres" class="form-label">Relevant Fields</label>
                            <select class="form-select" id="editFilieres" name="editFilieres[]" multiple>
                                <?php foreach ($filieres as $filiere): ?>
                                <option value="<?= $filiere['id_filiere'] ?>"><?= htmlspecialchars($filiere['filiere']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select the fields relevant to this subject.</small>
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

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Delete a Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this subject? This action is irreversible.</p>
                    <p class="fw-bold text-center" id="deleteSubjectName"></p>
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
            var subjectsTable = $('#subjectsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/en-US.json" // Changed from 'fr-FR' to 'en-US'
                },
                "pageLength": 10,
                "ordering": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 4 } // Disable sorting on action column
                ]
            });
        });

        // Show add subject modal
        document.getElementById('addSubjectBtn').addEventListener('click', function() {
            var myModal = new bootstrap.Modal(document.getElementById('addSubjectModal'));
            myModal.show();
        });

        // Handle add subject form submission
        document.getElementById('addSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addSubjectModal'));
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
                showAlert('danger', 'An error occurred while adding the subject'); // Translated
            });
        });
        
        // Handle edit subject buttons
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editSubjectId').value = this.dataset.id;
                document.getElementById('editSubjectName').value = this.dataset.nom;
                document.getElementById('editSubjectCoefficient').value = this.dataset.coefficient;
                
                var modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                modal.show();
            });
        });
        
        // Handle edit subject form submission
        document.getElementById('editSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('editSubjectModal'));
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
                showAlert('danger', 'An error occurred while updating the subject'); // Translated
            });
        });
        
        // Handle delete subject buttons
        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteSubjectName').textContent = this.dataset.nom;
                document.getElementById('confirmDeleteBtn').setAttribute('data-id', this.dataset.id);
                
                var modal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
                modal.show();
            });
        });
        
        // Handle confirm delete button
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const subjectId = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', subjectId);
            
            fetch('subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    
                    // Close modal and reload page
                    var modal = bootstrap.Modal.getInstance(document.getElementById('deleteSubjectModal'));
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
                showAlert('danger', 'An error occurred while deleting the subject'); // Translated
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
        
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
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