<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Minimal CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/minimal.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        [data-theme="dark"] {
            --bs-body-bg: #0f172a;
            --bs-body-color: #f1f5f9;
            --bs-card-bg: #1e293b;
            --bs-border-color: #334155;
            --bg-gradient: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }
        
        * {
            transition: all 0.2s ease;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            font-size: 14px;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 0;
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(30, 41, 59, 0.9) !important;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary) !important;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .nav-link:hover {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary) !important;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        [data-theme="dark"] .card {
            background: rgba(30, 41, 59, 0.95);
            color: #f1f5f9;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            font-size: 13px;
            padding: 0.5rem 1rem;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary);
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            padding: 0.625rem 0.75rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }
        
        [data-theme="dark"] .form-control {
            background: #334155;
            border-color: #475569;
            color: #f1f5f9;
        }
        
        .badge {
            font-size: 11px;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }
        
        .theme-toggle {
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        [data-theme="dark"] .theme-toggle {
            border-color: #475569;
        }
        
        .theme-toggle:hover {
            background: rgba(99, 102, 241, 0.1);
        }
        
        .progress {
            height: 8px;
            border-radius: 20px;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            margin: 20px;
            padding: 20px;
        }
        
        .sidebar .nav-link {
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
        }
        
        .theme-toggle {
            cursor: pointer;
            font-size: 1.2em;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin: 10px;
                padding: 15px;
            }
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        /* Password strength meter */
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: var(--danger-color); width: 25%; }
        .strength-fair { background: var(--warning-color); width: 50%; }
        .strength-good { background: var(--secondary-color); width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL . ($_SESSION['user_role'] === 'admin' ? 'admin' : 'student') . '/dashboard.php'; ?>">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['user_role'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>student/profile.php">
                                    <i class="fas fa-user me-1"></i>Profile
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/students.php">
                                    <i class="fas fa-users me-1"></i>Students
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                                <i class="fas fa-moon theme-icon"></i>
                            </button>
                        </li>
                        
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="d-flex align-items-center">
                                    <?php if (isset($_SESSION['profile_picture']) && $_SESSION['profile_picture']): ?>
                                        <img src="<?php echo BASE_URL . $_SESSION['profile_picture']; ?>" alt="Profile" 
                                             class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                             style="width: 32px; height: 32px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="d-none d-md-inline"><?php echo explode(' ', $_SESSION['user_name'] ?? 'User')[0]; ?></span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL . 'student/profile.php'; ?>">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="<?php echo BASE_URL; ?>auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main style="padding-top: 80px;"><?php 
        // Initialize theme if not set
        if (!isset($_SESSION['theme'])) {
            $_SESSION['theme'] = 'light';
        }
    ?>
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i>
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            
            // Update theme icon
            const themeIcon = document.querySelector('.theme-icon');
            if (themeIcon) {
                themeIcon.className = newTheme === 'dark' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
            
            // Save theme preference via AJAX
            fetch('<?php echo BASE_URL; ?>api/toggle-theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: newTheme })
            }).catch(error => console.error('Theme save error:', error));
        }
        
        // Initialize theme icon on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const themeIcon = document.querySelector('.theme-icon');
            if (themeIcon) {
                themeIcon.className = currentTheme === 'dark' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
        });
        
        // Auto-hide alerts
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
