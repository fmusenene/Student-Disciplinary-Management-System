<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('admin') && !hasPermission('headteacher')) {
    header('Location: index.php');
    exit();
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$class = $_GET['class'] ?? '';
$actionType = $_GET['action_type'] ?? '';

// Build query conditions
$conditions = ["c.created_at BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($class) {
    $conditions[] = "s.class = ?";
    $params[] = $class;
}

if ($actionType) {
    $conditions[] = "a.action_type = ?";
    $params[] = $actionType;
}

$whereClause = implode(" AND ", $conditions);

// Get total cases
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_cases
    FROM cases c
    JOIN students s ON c.student_id = s.id
    LEFT JOIN actions a ON c.id = a.case_id
    WHERE $whereClause
");
$stmt->execute($params);
$totalCases = $stmt->fetchColumn();

// Get cases by action type
$stmt = $conn->prepare("
    SELECT a.action_type, COUNT(*) as count
    FROM cases c
    JOIN students s ON c.student_id = s.id
    JOIN actions a ON c.id = a.case_id
    WHERE $whereClause
    GROUP BY a.action_type
");
$stmt->execute($params);
$casesByAction = $stmt->fetchAll();

// Get cases by class
$stmt = $conn->prepare("
    SELECT s.class, COUNT(*) as count
    FROM cases c
    JOIN students s ON c.student_id = s.id
    LEFT JOIN actions a ON c.id = a.case_id
    WHERE $whereClause
    GROUP BY s.class
");
$stmt->execute($params);
$casesByClass = $stmt->fetchAll();

// Get unique classes for filter
$stmt = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Disciplinary Reports</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="action_type" class="form-label">Action Type</label>
                        <select class="form-select" id="action_type" name="action_type">
                            <option value="">All Actions</option>
                            <option value="warning" <?php echo $actionType === 'warning' ? 'selected' : ''; ?>>Warning</option>
                            <option value="counseling" <?php echo $actionType === 'counseling' ? 'selected' : ''; ?>>Counseling</option>
                            <option value="detention" <?php echo $actionType === 'detention' ? 'selected' : ''; ?>>Detention</option>
                            <option value="suspension" <?php echo $actionType === 'suspension' ? 'selected' : ''; ?>>Suspension</option>
                            <option value="expulsion" <?php echo $actionType === 'expulsion' ? 'selected' : ''; ?>>Expulsion</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Cases by Action Type</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="actionTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Cases by Class</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="classChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Summary</h6>
                            </div>
                            <div class="card-body">
                                <p>Total Cases: <?php echo $totalCases; ?></p>
                                <p>Date Range: <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                                <?php if ($class): ?>
                                    <p>Class: <?php echo htmlspecialchars($class); ?></p>
                                <?php endif; ?>
                                <?php if ($actionType): ?>
                                    <p>Action Type: <?php echo ucfirst($actionType); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Action Type Chart
const actionTypeCtx = document.getElementById('actionTypeChart').getContext('2d');
new Chart(actionTypeCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($casesByAction, 'action_type')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($casesByAction, 'count')); ?>,
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF'
            ]
        }]
    }
});

// Class Chart
const classCtx = document.getElementById('classChart').getContext('2d');
new Chart(classCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($casesByClass, 'class')); ?>,
        datasets: [{
            label: 'Number of Cases',
            data: <?php echo json_encode(array_column($casesByClass, 'count')); ?>,
            backgroundColor: '#36A2EB'
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 