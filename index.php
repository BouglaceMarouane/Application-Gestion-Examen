<!DOCTYPE html>
<html lang="en fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Exam Management System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding-top: 70px;
            margin: 0;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            background-color: #fff;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }

        /* Feature icons */
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-icon i {
            font-size: 28px;
        }

        /* Card styles */
        .info-card {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.1);
        }

        .info-card .card-body {
            padding: 1.5rem;
        }
        
        .info-card .icon {
        font-size: 2rem;
        opacity: 0.8;
        }

        /* Form styles */
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Gold hover effect for all text links in footer */
        .hover-gold:hover {
            color: gold !important;
            transition: color 0.3s ease;
        }

        .contacts a:hover{
            color:rgb(238, 255, 0);
        }

        /* Back to Top Button */
        #backToTop {
            width: 50px;
            height: 50px;
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        :root {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
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

    <!-- Hero Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-3" style='color:#063970 !important;'>School Exam Management System</h1>
                    <p class="lead text-muted mb-4">A comprehensive solution for educational institutions to manage exams, track student performance, and streamline teacher workflows.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="includes/register.php" class="btn btn-primary btn-lg">
                            Get Started <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <a href="includes/login.php" class="btn btn-outline-primary btn-lg">Login</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/img/27799766.png" alt="School Exam Management" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <h2 class="text-center mb-5" style='color:#063970;'>Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card info-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="h5">Exam Scheduling</h3>
                            <p class="text-muted">Easily schedule exams, assign rooms, and manage timetables with conflict detection.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card info-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h3 class="h5">Performance Tracking</h3>
                            <p class="text-muted">Track student performance with detailed analytics, grade distribution, and progress reports.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card info-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="h5">Teacher Management</h3>
                            <p class="text-muted">Manage teacher assignments, subject allocations, and workload distribution efficiently.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5 bg-light" id="about">
        <div class="container">
            <h2 class="text-center mb-5" style='color:#063970 !important;'>Why Choose Our System?</h2>
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card info-card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="h4 mb-3">For Administrators</h3>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Centralized management of all exam-related activities</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Automated scheduling to avoid conflicts</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Comprehensive reporting and analytics</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Secure role-based access control</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card info-card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="h4 mb-3">For Teachers</h3>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Easy exam creation and question management</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Simplified grading and result publication</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Performance insights for each student</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Reduced administrative workload</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 text-white" style='background-color:#063970 !important;'>
        <div class="container text-center">
            <h2 class="mb-3">Ready to transform your exam management?</h2>
            <p class="lead mb-4">Join hundreds of educational institutions already using our platform.</p>
            <div class="d-flex justify-content-center flex-wrap gap-2">
                <a href="includes/register.php" class="btn btn-primary btn-lg">
                    Register Now <i class="fas fa-arrow-right ms-2"></i>
                </a>
                <a href="includes/login.php" class="btn btn-outline-light btn-lg">Login</a>
            </div>
        </div>
    </section>

    <!-- Toast container at bottom-left -->
    <div class="position-fixed bottom-0 start-0 p-3" style="z-index: 11">
        <div id="contactToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    Thank you for your message. We will get back to you soon!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <section class="py-5" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="text-center mb-4" style='color:#063970 !important;'>Contact Us</h2>
                    <div class="card info-card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="5" required></textarea>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary rounded-circle" style="position: fixed; bottom: 20px; right: 20px; display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Footer -->
    <footer class="text-white py-4 mb-2-sm" style='background-color:#063970 !important;'>
        <div class="container">
            <div class="row contacts g-4">
                <div class="col-lg-4 hover-gold">
                    <h5>ExamManager</h5>
                    <p class="text-light">A comprehensive solution for educational institutions to manage exams, track student performance, and streamline teacher workflows.</p>
                </div>
                <div class="col-lg-2 col-md-4 hover-gold">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none mb-2 text-light hover-gold">Home</a></li>
                        <li><a href="#features" class="text-decoration-none mb-2 text-light hover-gold">Features</a></li>
                        <li><a href="#about" class="text-decoration-none mb-2 text-light hover-gold">About</a></li>
                        <li><a href="#contact" class="text-decoration-none text-light hover-gold">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 hover-gold">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none mb-2 text-light hover-gold">Terms of Service</a></li>
                        <li><a href="#" class="text-decoration-none mb-2 text-light hover-gold">Privacy Policy</a></li>
                        <li><a href="#" class="text-decoration-none text-light hover-gold">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4 hover-gold">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled text-light">
                        <li><i class="fas fa-envelope mb-2 me-2 hover-gold"></i><a class="text-decoration-none text-light hover-gold" target='_blank' href="mailto:bouglacemarouane@gmail.com">bouglacemarouane@gmail.com</a></li>
                        <li><i class="fas fa-phone mb-2 me-2 hover-gold"></i><a class="text-decoration-none text-light hover-gold" target='_blank' href="tel:+212 620667050">+212 620667050</a></li>
                        <li><i class="fas fa-map-marker-alt me-2 hover-gold"></i><a class="text-decoration-none text-light hover-gold" target='_blank' href="https://www.google.fr/maps/place/City+of+Trades+and+Skills+Rabat+Sal%C3%A9+Kenitra/@33.8401147,-6.9409077,16.26z/data=!4m12!1m5!3m4!2zMzPCsDUwJzE2LjEiTiA2wrA1NScyOS4wIlc!8m2!3d33.8378056!4d-6.9247222!3m5!1s0xda70f9e2957c387:0xb6a5d68422b16ad!8m2!3d33.8382991!4d-6.9363259!16s%2Fg%2F11k3fc_b33?entry=ttu&g_ep=EgoyMDI1MDUwMy4wIKXMDSoJLDEwMjExNDUzSAFQAw%3D%3D"> G23 Ennajah, Tamesna</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0 hover-gold">&copy; <span id="currentYear"></span> ExamManager. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set current year in footer
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
        // Trigger the Toast
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Show Bootstrap toast
            const toastElement = document.getElementById('contactToast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();

            this.reset();
        });

        // Back to Top Button
        const backToTopButton = document.getElementById('backToTop');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 200) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>

