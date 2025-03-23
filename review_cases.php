<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('admin')) {
    header('Location: index.php');
    exit();
}

$success = $error = '';

// Handle case review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseId = $_POST['case_id'] ?? '';
    $actionType = $_POST['action_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    
    if (empty($caseId) || empty($actionType) || empty($description) || empty($deadline)) {
        $error = 'All fields are required';
    } else {
        try {
            $conn->beginTransaction();
            
            // Update case status
            $stmt = $conn->prepare("UPDATE cases SET status = 'reviewed' WHERE id = ?");
            $stmt->execute([$caseId]);
            
            // Insert action
            $stmt = $conn->prepare("
                INSERT INTO actions (case_id, assigned_by, action_type, description, deadline)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$caseId, $_SESSION['user_id'], $actionType, $description, $deadline]);
            
            // Create notifications for relevant users
            $notifyRoles = ['chairperson', 'deputy_ethics', 'deputy_academics', 'deputy_admin', 'dean', 'headteacher'];
            $stmt = $conn->prepare("
                INSERT INTO notifications (case_id, user_id, message)
                SELECT ?, id, ?
                FROM users
                WHERE role IN (" . implode(',', array_fill(0, count($notifyRoles), '?')) . ")
            ");
            
            $message = "Case #" . $caseId . " has been reviewed and action assigned: " . $actionType;
            $params = array_merge([$caseId, $message], $notifyRoles);
            $stmt->execute($params);
            
            $conn->commit();
            $success = 'Case reviewed and action assigned successfully';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error reviewing case: ' . $e->getMessage();
        }
    }
}

// Get pending cases
$stmt = $conn->query("
    SELECT c.*, s.full_name as student_name, s.class, u.full_name as reporter_name
    FROM cases c
    JOIN students s ON c.student_id = s.id
    JOIN users u ON c.reported_by = u.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
");
$pendingCases = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Review Disciplinary Cases</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (empty($pendingCases)): ?>
                    <p class="text-muted">No pending cases to review.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Case ID</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Reported By</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingCases as $case): ?>
                                <tr>
                                    <td>#<?php echo $case['id']; ?></td>
                                    <td><?php echo htmlspecialchars($case['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($case['class']); ?></td>
                                    <td><?php echo htmlspecialchars($case['reporter_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($case['incident_date'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $case['id']; ?>">
                                            Review
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Review Modal -->
                                <div class="modal fade" id="reviewModal<?php echo $case['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Review Case #<?php echo $case['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Student:</strong> <?php echo htmlspecialchars($case['student_name']); ?></p>
                                                <p><strong>Class:</strong> <?php echo htmlspecialchars($case['class']); ?></p>
                                                <p><strong>Incident Date:</strong> <?php echo date('M d, Y', strtotime($case['incident_date'])); ?></p>
                                                <p><strong>Description:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($case['incident_description'])); ?></p>
                                                
                                                <?php if ($case['evidence_file']): ?>
                                                    <p>
                                                        <strong>Evidence:</strong>
                                                        <a href="uploads/evidence/<?php echo htmlspecialchars($case['evidence_file']); ?>" target="_blank">
                                                            View Evidence
                                                        </a>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <form method="POST">
                                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="action_type" class="form-label">Action Type</label>
                                                        <select class="form-select" id="action_type" name="action_type" required>
                                                            <option value="">Select Action</option>
                                                            <option value="warning">Warning</option>
                                                            <option value="counseling">Counseling</option>
                                                            <option value="detention">Detention</option>
                                                            <option value="suspension">Suspension</option>
                                                            <option value="expulsion">Expulsion</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="description" class="form-label">Action Description</label>
                                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="deadline" class="form-label">Deadline</label>
                                                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-primary">Assign Action</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 