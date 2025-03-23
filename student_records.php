<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('teacher') && !hasPermission('admin') && !hasPermission('headteacher')) {
    header('Location: index.php');
    exit();
}

// Get filter parameters
$class = $_GET['class'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$conditions = ["c.id IS NOT NULL"];
$params = [];

if ($class) {
    $conditions[] = "s.class = ?";
    $params[] = $class;
}

if ($search) {
    $conditions[] = "(s.full_name LIKE ? OR s.student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(" AND ", $conditions);

// Get students with cases
$stmt = $conn->prepare("
    SELECT DISTINCT 
        s.id,
        s.student_id,
        s.full_name,
        s.class,
        COUNT(c.id) as total_cases,
        MAX(c.incident_date) as last_incident,
        MAX(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as has_pending_cases
    FROM students s
    JOIN cases c ON s.id = c.student_id
    WHERE $whereClause
    GROUP BY s.id, s.student_id, s.full_name, s.class
    ORDER BY s.class, s.full_name
");
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get unique classes for filter
$stmt = $conn->query("
    SELECT DISTINCT s.class 
    FROM students s 
    JOIN cases c ON s.id = c.student_id 
    ORDER BY s.class
");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Student Records with Disciplinary Cases</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $class === $c ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search by Name or ID</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="student_records.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>

                <?php if (empty($students)): ?>
                    <div class="alert alert-info">No students found with disciplinary cases.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Total Cases</th>
                                    <th>Last Incident</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class']); ?></td>
                                        <td><?php echo $student['total_cases']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['last_incident'])); ?></td>
                                        <td>
                                            <?php if ($student['has_pending_cases']): ?>
                                                <span class="badge bg-warning">Has Pending Cases</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">All Cases Resolved</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="student_report.php?student_id=<?php echo urlencode($student['student_id']); ?>" 
                                               class="btn btn-sm btn-primary">
                                                View Details
                                            </a>
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
</div>

<?php require_once 'includes/footer.php'; ?> 