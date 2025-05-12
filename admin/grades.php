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

    $pdo = getConnection();

    // Fetch all validated students
    $etudiants = $pdo->query("SELECT e.id, e.nom_complet, c.nom AS classe_nom FROM etudiants e JOIN classes c ON e.classe_id = c.id WHERE e.status = 'validated'")->fetchAll();

    // Fetch all exams
    $examens = $pdo->query("SELECT ex.id, ex.exam_title, ex.bareme, m.nom AS matiere_nom, c.nom AS classe_nom, f.filiere 
    FROM examens ex 
    JOIN matieres m ON ex.matiere_id = m.id 
    JOIN classes c ON ex.classe_id = c.id 
    JOIN filiere f ON c.filiere_id = f.id_filiere")->fetchAll();

    // Fetch notes
    $notes = $pdo->query("SELECT n.*, e.nom_complet, ex.exam_title, ex.bareme, m.nom AS matiere_nom, c.nom AS classe_nom, f.filiere 
    FROM notes n 
    JOIN etudiants e ON n.etudiant_id = e.id 
    JOIN examens ex ON n.examen_id = ex.id 
    JOIN matieres m ON ex.matiere_id = m.id 
    JOIN classes c ON n.classe_id = c.id 
    JOIN filiere f ON e.filiere_id = f.id_filiere")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes - Système de Gestion des Examens Scolaires</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
                    <a class="nav-link active" href="grades.php">
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

            <!-- Grades Content -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Grade Management</h1>
                    <button id="addGradeBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Grade
                    </button>
                </div>

                <!-- Grades Table -->
                <div class="card shadow h-100 mb-4">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">List of Grades</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Field of study</th>
                                        <th>Class</th>
                                        <th>Grade</th>
                                        <th>Comment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        foreach ($notes as $note):
                                            // Calculate percentage for visual representation (if needed)
                                            $percentage = ($note['note'] / $note['bareme']) * 100;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($note['nom_complet']) ?></td>
                                        <td><?= htmlspecialchars($note['exam_title']) ?></td>
                                        <td><?= htmlspecialchars($note['matiere_nom']) ?></td>
                                        <td><?= htmlspecialchars($note['filiere']) ?></td>
                                        <td><?= htmlspecialchars($note['classe_nom']) ?></td>
                                        <td><?= htmlspecialchars($note['note']) ?>/<?= htmlspecialchars($note['bareme']) ?></td>
                                        <td><?= htmlspecialchars($note['commentaire'] ?? '') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info editNoteBtn" 
                                                    data-id="<?= $note['id'] ?>"
                                                    data-etudiant="<?= $note['etudiant_id'] ?>"
                                                    data-examen="<?= $note['examen_id'] ?>"
                                                    data-note="<?= $note['note'] ?>"
                                                    data-commentaire="<?= htmlspecialchars($note['commentaire'] ?? '') ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger deleteNoteBtn"
                                                    data-id="<?= $note['id'] ?>"
                                                    data-etudiant="<?= htmlspecialchars($note['nom_complet']) ?>"
                                                    data-examen="<?= htmlspecialchars($note['exam_title']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                        
                                        if (empty($notes)) {
                                            echo '<tr><td colspan="8" class="text-center">Aucune note trouvée.</td></tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='8' class='text-danger'>Erreur lors du chargement des notes : " . $e->getMessage() . "</td></tr>";
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

    <!-- Add Grade Modal -->
    <div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addGradeForm" method="POST" action="actions/notes_actions.php">
        <input type="hidden" name="action" value="addNote">
        <input type="hidden" name="classe_id" id="classe_id_hidden">

        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="addGradeModalLabel">Ajouter une note</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
            <div class="mb-3">
                <label for="etudiant_id" class="form-label">Élève</label>
                <select class="form-select" id="etudiant_id" name="etudiant_id" required>
                <option value="">Sélectionnez un élève</option>
                <?php
                foreach ($etudiants as $etudiant) {
                    echo "<option value=\"{$etudiant['id']}\">{$etudiant['nom_complet']}</option>";
                }
                ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="examen_id" class="form-label">Examen</label>
                <select class="form-select" id="examen_id" name="examen_id" required>
                <option value="">Sélectionnez un examen</option>
                <?php
                foreach ($examens as $examen) {
                    echo "<option value=\"{$examen['id']}\" data-bareme=\"{$examen['bareme']}\">
                            {$examen['exam_title']} - {$examen['matiere_nom']}
                            </option>";
                }
                ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">Note <span id="baremeLabel">(sur 20)</span></label>
                <input type="number" class="form-control" id="note" name="note" min="0" max="20" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="commentaire" class="form-label">Commentaire</label>
                <textarea class="form-control" id="commentaire" name="commentaire" rows="2"></textarea>
            </div>
            </div>

            <div class="modal-footer">
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
        </form>
    </div>
    </div>

    <!-- Edit Grade Modal -->
    <div class="modal fade" id="editGradeModal" tabindex="-1" aria-labelledby="editGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editGradeForm" method="POST" action="actions/notes_actions.php">
        <input type="hidden" name="action" value="editNote">
        <input type="hidden" name="note_id" id="edit_note_id">

        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="editGradeModalLabel">Modifier la note</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Élève</label>
                <input type="text" class="form-control" id="edit_etudiant" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Examen</label>
                <input type="text" class="form-control" id="edit_examen" disabled>
            </div>

            <div class="mb-3">
                <label for="edit_note" class="form-label">Note <span id="edit_baremeLabel">(sur 20)</span></label>
                <input type="number" class="form-control" name="note" id="edit_note" min="0" max="20" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="edit_commentaire" class="form-label">Commentaire</label>
                <textarea class="form-control" name="commentaire" id="edit_commentaire" rows="2"></textarea>
            </div>
            </div>

            <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
        </form>
    </div>
    </div>

    <!-- Delete Grade Modal -->
    <div class="modal fade" id="deleteGradeModal" tabindex="-1" aria-labelledby="deleteGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="actions/notes_actions.php">
        <input type="hidden" name="action" value="deleteNote">
        <input type="hidden" name="note_id" id="delete_note_id">

        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="deleteGradeModalLabel">Supprimer la note</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer cette note ?</p>
            <p><strong>Élève :</strong> <span id="deleteGradeStudentName"></span></p>
            <p><strong>Examen :</strong> <span id="deleteGradeExamName"></span></p>
            </div>

            <div class="modal-footer">
            <button type="submit" class="btn btn-danger">Supprimer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
        </form>
    </div>
    </div>

    <!-- Alert for notifications -->
    <div class="alert-container" id="alertContainer"></div>

    <!-- jQuery (needed for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS Bundle (needed for modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            var gradesTable = $('#gradesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/en-US.json" // Changed from 'fr-FR' to 'en-US'
                },
                "pageLength": 10,
                "ordering": true,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 7 } // Disable sorting on action column (index 7)
                ]
            });
            
            // Apply search filtering
            $('#searchInput').on('keyup', function() {
                gradesTable.search(this.value).draw();
            });
            
            // Class filter
            $('#classFilter').on('change', function() {
                gradesTable.column(4).search(this.value).draw();
            });
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#searchInput').val('');
                $('#classFilter').val('');
                gradesTable.search('').columns().search('').draw();
            });

            // Add Grade button click
            $('#addGradeBtn').click(function() {
                $('#addGradeForm')[0].reset();
                $('#baremeLabel').text('(sur 20)');
                $('#addGradeModal').modal('show');
            });

            // Update bareme label when exam changes
            $('#examen_id').change(function() {
                const bareme = $(this).find('option:selected').data('bareme');
                $('#baremeLabel').text(`(sur ${bareme})`);
                $('#note').attr('max', bareme);
            });

            // Edit button click
            $('.editNoteBtn').click(function() {
                const id = $(this).data('id');
                const etudiantId = $(this).data('etudiant');
                const examenId = $(this).data('examen');
                const note = $(this).data('note');
                const commentaire = $(this).data('commentaire');
                
                $('#edit_note_id').val(id);
                $('#edit_note').val(note);
                $('#edit_commentaire').val(commentaire);
                
                $('#editGradeModal').modal('show');
            });

            // Delete button click
            $('.deleteNoteBtn').click(function() {
                $('#delete_note_id').val($(this).data('id'));
                $('#deleteGradeStudentName').text($(this).data('etudiant'));
                $('#deleteGradeExamName').text($(this).data('examen'));
                $('#deleteGradeModal').modal('show');
            });
            
            // Show alert if there's a message in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const status = urlParams.get('status');
            
            if (message && status) {
                showAlert(status, decodeURIComponent(message));
            }
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
        
        // Sidebar toggle
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