<?php

    require_once '../config/connection.php';
    require_once 'functions.php';

    // Redirect if already logged in
    if (isLoggedIn()) {
        if (isAdmin() || isTeacher()) {
            header("Location: ../admin/admin_dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    }

    $error = '';
    $success = '';
    $filiere_options = [];

    try {
        $conn = getConnection();

        // Retrieve list of streams from database
        $stmt = $conn->query("SELECT id_filiere, filiere FROM filiere");
        $filiere_options = $stmt->fetchAll();
        
        // We no longer fetch all classes here, we'll do it with AJAX
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom_complet = trim($_POST['nom_complet']);
        $email = trim($_POST['email']);
        $date_naissance = $_POST['date_naissance'];
        $filiere = $_POST['filiere'];
        $classe_id = $_POST['classe_id'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($nom_complet) || empty($email) || empty($date_naissance) || empty($filiere) || empty($classe_id) || empty($password)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Check if the email is valid
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                }

                // Check if email already exists in the students table
                $stmt = $conn->prepare("SELECT id FROM etudiants WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = "A student with this email is already registered.";
                }

                // Check if email already exists in the users table
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = "This email is already in use.";
                } else {
                    // Insert into the users table
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'etudiant', NOW())");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$nom_complet, $email, $hashed_password]);

                    $user_id = $conn->lastInsertId();

                    // Insert in student table
                    $stmt = $conn->prepare("INSERT INTO etudiants (user_id, nom_complet, date_naissance, email, filiere_id, classe_id, is_validated, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
                    $stmt->execute([$user_id, $nom_complet, $date_naissance, $email, $filiere, $classe_id]);

                    $success = "Registration successful! Your account is pending validation by the administrator.";
                }
            } catch (PDOException $e) {
                $error = "Registration error: " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .navbar {
            margin-bottom: 20px;
        }

        .card {
            background-color: #ffffff;
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }


        input.form-control,
        select.form-select {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }

        input.form-control:focus,
        select.form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(6, 57, 112, 0.25);
            border-color: #063970;
        }

        .btn-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #052c54;
            border-color: #052c54;
        }
    </style>
</head>
<body class='bg-light'>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top p-3">
        <div class="container">
            <i class="fa-solid fa-graduation-cap fa-xl" style="color: #005eff;"></i> <a class="navbar-brand fw-bold text-primary ms-2" href="index.php">ExamManager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a id='home' class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="includes/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="includes/register.php" class="btn btn-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-5 d-flex align-items-center justify-content-center min-vh-100">
        <div class="col-md-6 col-lg-6">
            <div class="card shadow mt-5 rounded-4 border-0">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4 fw-bold" style="color:#063970;">Student Registration</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-3"><?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary px-4 py-2 rounded-pill">Login</a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="nom_complet" class="form-label text-muted">Full Name</label>
                                <input type="text" class="form-control rounded-pill" id="nom_complet" name="nom_complet" required>
                                <div class="invalid-feedback">Full name must be at least 3 characters.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label text-muted">Email</label>
                                <input type="email" class="form-control rounded-pill" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label for="date_naissance" class="form-label text-muted">Date of Birth</label>
                                <input type="date" class="form-control rounded-pill" id="date_naissance" name="date_naissance" max="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback">You must be at least 18 years old.</div>
                            </div>
                            <div class="mb-3">
                                <label for="filiere" class="form-label text-muted">field of study</label>
                                <select class="form-select rounded-pill" id="filiere" name="filiere" required>
                                <option value="">Select a filière</option>
                                <?php foreach ($filiere_options as $option): ?>
                                    <option value="<?php echo $option['id_filiere']; ?>">
                                    <?php echo htmlspecialchars($option['filiere']); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a field of study.</div>
                            </div>
                            <div class="mb-3">
                                <label for="classe_id" class="form-label text-muted">Class</label>
                                <select class="form-select rounded-pill" id="classe_id" name="classe_id" required>
                                <option value="">Select a filière first</option>
                                </select>
                                <div class="invalid-feedback">Please select a class.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-muted">Password</label>
                                <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label text-muted">Confirm Password</label>
                                <input type="password" class="form-control rounded-pill" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Passwords do not match.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary rounded-pill py-2">Register</button>
                            </div>
                        </form>
                        <div class="mt-4 text-center">
                            <p class="text-muted">Already registered? <a href="login.php">Login</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            let valid = true;

            const nom = document.getElementById("nom_complet");
            const email = document.getElementById("email");
            const dob = document.getElementById("date_naissance");
            const password = document.getElementById("password");
            const confirmPassword = document.getElementById("confirm_password");

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

            // Mot de passe
            if (password.value.length < 6) {
                password.classList.add("is-invalid");
                valid = false;
            }

            // Confirmation
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add("is-invalid");
                valid = false;
            }

            if (!valid) {
                e.preventDefault(); // Stop form submission
            }
        });

        // Add event listener to filiere dropdown
        document.getElementById('filiere').addEventListener('change', function() {
            const filiereId = this.value;
            const classeDropdown = document.getElementById('classe_id');
            
            // Clear existing options
            classeDropdown.innerHTML = '<option value="">Loading classes...</option>';
            
            if (filiereId) {
                // Fetch classes for the selected filiere using AJAX
                // AJAX = Envoyer une requête → recevoir une réponse → mettre à jour la page sans tout recharger.
                fetch(`get_classes.php?filiere_id=${filiereId}`) 
                    .then(response => response.json())
                    .then(data => { // --> // data est un tableau d'objets JS
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
                classeDropdown.innerHTML = '<option value="">Select a filière first</option>';
            }
        });
    </script>
</body>
</html>