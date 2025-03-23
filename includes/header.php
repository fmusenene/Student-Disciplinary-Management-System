<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Disciplinary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: #343a40;
            padding: 10px 20px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1;
            border-radius: 10rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <h5 class="text-white">Disciplinary System</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php if (hasPermission('teacher')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'report_case.php' ? 'active' : ''; ?>" href="report_case.php">
                            <i class="bi bi-file-earmark-text"></i> Report Case
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'review_cases.php' ? 'active' : ''; ?>" href="review_cases.php">
                            <i class="bi bi-clipboard-check"></i> Review Cases
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('admin') || hasPermission('headteacher')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_records.php' ? 'active' : ''; ?>" href="student_records.php">
                            <i class="bi bi-people"></i> Student Records
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('parent')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parent_cases.php' ? 'active' : ''; ?>" href="parent_cases.php">
                            <i class="bi bi-person-lines-fill"></i> My Children's Cases
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <div class="top-navbar mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="text-white mb-0">Dashboard</h5>
                        <div class="d-flex align-items-center">
                            <!-- Notification Icon with Count -->
                            <a href="review_cases.php" class="btn btn-link text-white position-relative me-3" title="View Pending Cases">
                                <i class="bi bi-bell"></i>
                                <?php
                                // Get pending cases count
                                $stmt = $conn->prepare("
                                    SELECT COUNT(*) as count 
                                    FROM cases 
                                    WHERE status = 'pending' 
                                    AND (reported_by = ? OR ? IN ('admin', 'headteacher'))
                                ");
                                $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
                                $pendingCasesCount = $stmt->fetch()['count'];
                                if ($pendingCasesCount > 0):
                                ?>
                                <span class="notification-badge bg-danger">
                                    <?php echo $pendingCasesCount; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            
                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-link text-white dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                    <div class="d-flex align-items-center">
                                        <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                            <img src="uploads/profile_pictures/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" 
                                                 alt="Profile" class="rounded-circle me-3" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle me-3" style="font-size: 1.5rem;"></i>
                                        <?php endif; ?>
                                        <span class="me-2"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Page Content -->
                <div class="container-fluid"> 