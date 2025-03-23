<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Check if user is a parent
if (!hasPermission('parent')) {
    header('Location: index.php');
    exit();
}

// Get parent's children's cases
$stmt = $conn->prepare("
    SELECT c.*, 
           s.full_name as student_name,
           s.student_id,
           s.class,
           u.full_name as reporter_name
    FROM cases c
    JOIN students s ON c.student_id = s.id
    JOIN users u ON c.reported_by = u.id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cases = $stmt->fetchAll();

// Get parent's children
$stmt = $conn->prepare("
    SELECT s.* 
    FROM students s
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Children's Cases</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($children)): ?>
                        <div class="alert alert-info">
                            No children records found associated with your account.
                        </div>
                    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>My Children</h6>
                                <div class="list-group">
                                    <?php foreach ($children as $child): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($child['full_name']); ?></h6>
                                                    <small class="text-muted">
                                                        Class: <?php echo htmlspecialchars($child['class']); ?> |
                                                        ID: <?php echo htmlspecialchars($child['student_id']); ?>
                                                    </small>
                                                </div>
                                                <a href="student_report.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-primary">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (empty($cases)): ?>
                            <div class="alert alert-info">
                                No disciplinary cases found for your children.
                            </div>
                        <?php else: ?>
                            <h6>Recent Cases</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Reported By</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cases as $case): ?>
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
                                                <td><?php echo htmlspecialchars($case['reporter_name']); ?></td>
                                                <td>
                                                    <a href="view_case.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 