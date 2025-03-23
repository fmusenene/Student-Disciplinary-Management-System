<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('teacher') && !hasPermission('admin') && !hasPermission('headteacher')) {
    header('Location: index.php');
    exit();
}

$studentId = $_GET['student_id'] ?? '';
$error = '';
$student = null;
$cases = [];

if (empty($studentId)) {
    $error = 'Please provide a student ID';
} else {
    // Get student details
    $stmt = $conn->prepare("
        SELECT s.*, u.full_name as parent_name, u.email as parent_email
        FROM students s
        LEFT JOIN users u ON s.parent_id = u.id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        $error = 'Student not found. Please check the student ID and try again.';
    } else {
        // Get all cases for this student
        $stmt = $conn->prepare("
            SELECT c.*, 
                   u.full_name as reporter_name,
                   a.action_type,
                   a.description as action_description,
                   a.deadline as action_deadline,
                   au.full_name as action_by_name
            FROM cases c
            JOIN users u ON c.reported_by = u.id
            LEFT JOIN actions a ON c.id = a.case_id
            LEFT JOIN users au ON a.assigned_by = au.id
            WHERE c.student_id = ?
            ORDER BY c.incident_date DESC
        ");
        $stmt->execute([$student['id']]);
        $cases = $stmt->fetchAll();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Student Disciplinary Report</h5>
                <a href="student_records.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Student Records
                </a>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                        <div class="mt-3">
                            <a href="student_records.php" class="btn btn-primary">View All Student Records</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Student Search Form -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student ID/Registration Number</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>

                <?php if ($student): ?>
                    <!-- Student Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Student Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                    <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Parent Name:</strong> <?php echo $student['parent_name'] ? htmlspecialchars($student['parent_name']) : 'Not assigned'; ?></p>
                                    <p><strong>Parent Email:</strong> <?php echo $student['parent_email'] ? htmlspecialchars($student['parent_email']) : 'Not assigned'; ?></p>
                                    <p><strong>Parent Phone:</strong> <span class="text-muted">Phone number feature coming soon</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disciplinary Cases -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Disciplinary Cases</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cases)): ?>
                                <p class="text-muted">No disciplinary cases found for this student.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Incident</th>
                                                <th>Reported By</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                                <th>Action By</th>
                                                <th>Deadline</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cases as $case): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($case['incident_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($case['incident_description']); ?></td>
                                                    <td><?php echo htmlspecialchars($case['reporter_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $case['status'] === 'pending' ? 'warning' : 
                                                                ($case['status'] === 'resolved' ? 'success' : 'info'); 
                                                        ?>">
                                                            <?php echo ucfirst($case['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($case['action_type']): ?>
                                                            <span class="badge bg-<?php 
                                                                echo $case['action_type'] === 'warning' ? 'info' : 
                                                                    ($case['action_type'] === 'counseling' ? 'primary' : 
                                                                    ($case['action_type'] === 'detention' ? 'warning' : 
                                                                    ($case['action_type'] === 'suspension' ? 'danger' : 'dark'))); 
                                                            ?>">
                                                                <?php echo ucfirst($case['action_type']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $case['action_by_name'] ? htmlspecialchars($case['action_by_name']) : '-'; ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($case['action_deadline']) {
                                                            $deadline = strtotime($case['action_deadline']);
                                                            $now = time();
                                                            $daysLeft = ceil(($deadline - $now) / (60 * 60 * 24));
                                                            
                                                            echo date('M d, Y', $deadline);
                                                            if ($daysLeft > 0) {
                                                                echo " <span class='badge bg-success'>$daysLeft days left</span>";
                                                            } elseif ($daysLeft < 0) {
                                                                echo " <span class='badge bg-danger'>Overdue</span>";
                                                            }
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 