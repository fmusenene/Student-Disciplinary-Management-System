<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Get user's role-specific information
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get pending cases count for admin
$pendingCases = 0;
if (hasPermission('admin')) {
    $stmt = $conn->query("SELECT COUNT(*) FROM cases WHERE status = 'pending'");
    $pendingCases = $stmt->fetchColumn();
}

// Get user's notifications
$stmt = $conn->prepare("
    SELECT n.*, c.id as case_id, s.full_name as student_name 
    FROM notifications n 
    JOIN cases c ON n.case_id = c.id 
    JOIN students s ON c.student_id = s.id 
    WHERE n.user_id = ? AND n.is_read = 0 
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Get recent cases based on user role
$recentCases = [];
if (hasPermission('admin')) {
    $stmt = $conn->query("
        SELECT c.*, s.full_name as student_name, u.full_name as reporter_name 
        FROM cases c 
        JOIN students s ON c.student_id = s.id 
        JOIN users u ON c.reported_by = u.id 
        WHERE c.status != 'reviewed'
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $recentCases = $stmt->fetchAll();
} elseif (hasPermission('teacher')) {
    $stmt = $conn->prepare("
        SELECT c.*, s.full_name as student_name 
        FROM cases c 
        JOIN students s ON c.student_id = s.id 
        WHERE c.reported_by = ? AND c.status != 'reviewed'
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentCases = $stmt->fetchAll();
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h5>
            </div>
            <div class="card-body">
                <?php if (hasPermission('admin')): ?>
                <div class="alert alert-info">
                    You have <?php echo $pendingCases; ?> pending cases to review.
                </div>
                <?php endif; ?>
                
                <h6>Recent Cases</h6>
                <?php if (empty($recentCases)): ?>
                    <p class="text-muted">No recent cases found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCases as $case): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['student_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($case['incident_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $case['status'] === 'pending' ? 'warning' : 
                                                ($case['status'] === 'resolved' ? 'success' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($case['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_case.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Notifications</h5>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted">No new notifications.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                        <a href="view_case.php?id=<?php echo $notification['case_id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Case: <?php echo htmlspecialchars($notification['student_name']); ?></h6>
                                <small><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    // Get total count of pending cases
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM cases 
                        WHERE status = 'pending' 
                        AND (reported_by = ? OR ? IN ('admin', 'headteacher'))
                    ");
                    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
                    $totalCases = $stmt->fetch()['total'];
                    
                    if ($totalCases > 5):
                    ?>
                    <div class="text-center mt-3">
                        <a href="review_cases.php" class="btn btn-primary btn-sm">
                            Show More Cases (<?php echo $totalCases - 5; ?> more)
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 