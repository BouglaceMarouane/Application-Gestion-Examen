<?php

    session_start();
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
    // Check if the database connection is established
    if (!getConnection()) {
        die("Database connection failed.");
    }else {
        // Check if the connection is successful
        try {
            $conn = getConnection();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Check if admin exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        // Create admin user
        $admin_name = "admin";
        $admin_email = "admin@gmail.com";
        $admin_password = "admin123";
        
        // Make sure we're using PASSWORD_DEFAULT for hashing
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        echo "Admin user created successfully!<br>";
    } else {
        echo "Admin user already exists.<br>";
    }

    $email = '';
    $error = '';

    // Cookie de rappel
    if (isset($_COOKIE['remember_email'])) {
        $email = $_COOKIE['remember_email'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        if ($remember) {
            setcookie('remember_email', $email, time() + (7 * 24 * 60 * 60), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            }
            // Check if the email already exists
            $stmt = $conn->prepare("
                SELECT u.*, e.is_validated, e.status 
                FROM users u
                LEFT JOIN etudiants e ON u.id = e.user_id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "Email address not found.";
            } else {
                // If password is not hashed (for legacy users), hash it and update
                if (!password_verify($password, $user['password']) && $user['password'] === $password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user['id']]);
                    $user['password'] = $hashed_password; // Update in current session
                }

                if (password_verify($password, $user['password'])) {
                    if ($user['role'] === 'etudiant') {
                        if (isset($user['status']) && $user['status'] === 'rejected') {
                            $error = "Your student account has been rejected by the administration.";
                        } elseif (!$user['is_validated']) {
                            $error = "Your student account has not yet been validated.";
                        } else {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['name'] = $user['name'];
                            $_SESSION['role'] = $user['role'];

                            header("Location: etudiant_profile.php");
                            exit();
                        }
                    } elseif ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                        // Admin or teacher login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];

                        header("Location: ../admin/admin_dashboard.php");
                        exit();
                    } else {
                    $error = "Unauthorized role.";
                    }
                } else {
                    $error = "Password invalide.";
                }

            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion : " . $e->getMessage();
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
    <!-- Bootstrap CSS -->
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
            <div class="card shadow rounded-4 border-0">
                <div class="card-body p-5">
                    <h2 class="text-center fw-bold mb-4" style="color:#063970;">Login</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label text-muted">Email</label>
                            <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label text-muted">Password</label>
                            <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                        </div>
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo $email ? 'checked' : ''; ?>>
                            <label class="form-check-label text-muted" for="remember">Remember me</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill py-2">Login</button>
                        </div>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="text-muted">Not registered yet? <a href="register.php">Create an account</a></p>
                    </div>
                    <div class="mt-3 text-center">
                        <small class="text-muted d-block">
                            <strong>Default credentials:</strong><br>
                            Email: admin@gmail.com<br>
                            Password: admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
