<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Check if case ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$caseId = $_GET['id'];

// Get case details with related information
$stmt = $conn->prepare("
    SELECT c.*, 
           s.full_name as student_name,
           s.student_id as registration_number,
           s.class,
           u.full_name as reporter_name,
           u.role as reporter_role
    FROM cases c
    JOIN students s ON c.student_id = s.id
    JOIN users u ON c.reported_by = u.id
    WHERE c.id = ?
");

$stmt->execute([$caseId]);
$case = $stmt->fetch();

// Check if case exists and user has permission to view it
if (!$case || (!hasPermission('admin') && $case['reported_by'] != $_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get case history
$stmt = $conn->prepare("
    SELECT ch.*, u.full_name as updated_by_name
    FROM case_history ch
    JOIN users u ON ch.updated_by = u.id
    WHERE ch.case_id = ?
    ORDER BY ch.created_at DESC
");
$stmt->execute([$caseId]);
$history = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Case Details</h5>
                    <?php if (hasPermission('admin') || $case['reported_by'] == $_SESSION['user_id']): ?>
                    <a href="edit_case.php?id=<?php echo $caseId; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil"></i> Edit Case
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name:</th>
                                    <td><?php echo htmlspecialchars($case['student_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Registration Number:</th>
                                    <td><?php echo htmlspecialchars($case['registration_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Class:</th>
                                    <td><?php echo htmlspecialchars($case['class']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Case Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $case['status'] === 'pending' ? 'warning' : 
                                                ($case['status'] === 'resolved' ? 'success' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($case['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Reported By:</th>
                                    <td><?php echo htmlspecialchars($case['reporter_name']); ?> (<?php echo ucfirst(str_replace('_', ' ', $case['reporter_role'])); ?>)</td>
                                </tr>
                                <tr>
                                    <th>Date Reported:</th>
                                    <td><?php echo date('M d, Y', strtotime($case['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Incident Date:</th>
                                    <td><?php echo date('M d, Y', strtotime($case['incident_date'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Incident Description</h6>
                            <div class="card">
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($case['incident_description'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($case['evidence'])): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Evidence</h6>
                            <div class="card">
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($case['evidence'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($case['action_taken'])): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Action Taken</h6>
                            <div class="card">
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($case['action_taken'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($history)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Case History</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>Updated By</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $entry): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></td>
                                            <td><?php echo ucfirst($entry['action']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['updated_by_name']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($entry['details'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (hasPermission('admin') && $case['status'] === 'pending'): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Update Case Status</h6>
                            <form action="update_case_status.php" method="POST">
                                <input type="hidden" name="case_id" value="<?php echo $caseId; ?>">
                                <div class="mb-3">
                                    <label for="status" class="form-label">New Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="in_review">In Review</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="dismissed">Dismissed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="details" class="form-label">Details</label>
                                    <textarea class="form-control" id="details" name="details" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 