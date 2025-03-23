<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('teacher')) {
    header('Location: index.php');
    exit();
}

$success = $error = '';

// Initialize form data
$formData = [
    'student_name' => '',
    'student_id' => '',
    'student_class' => '',
    'incident_date' => '',
    'incident_description' => '',
    'parent_name' => '',
    'parent_phone' => '',
    'parent_email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'student_name' => $_POST['student_name'] ?? '',
        'student_id' => $_POST['student_id'] ?? '',
        'student_class' => $_POST['student_class'] ?? '',
        'incident_date' => $_POST['incident_date'] ?? '',
        'incident_description' => $_POST['incident_description'] ?? '',
        'parent_name' => $_POST['parent_name'] ?? '',
        'parent_phone' => $_POST['parent_phone'] ?? '',
        'parent_email' => $_POST['parent_email'] ?? ''
    ];
    
    if (empty($formData['student_name']) || empty($formData['student_id']) || empty($formData['student_class']) || 
        empty($formData['incident_description']) || empty($formData['incident_date']) || empty($formData['parent_name']) || 
        empty($formData['parent_phone'])) {
        $error = 'Required fields are missing';
    } else {
        try {
            $conn->beginTransaction();
            
            // First, check if student exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $stmt->execute([$formData['student_id']]);
            $student = $stmt->fetch();
            
            // Handle parent user
            $parentId = null;
            if (!empty($formData['parent_name'])) {
                // First check if parent exists by email
                $stmt = $conn->prepare("
                    SELECT id FROM users 
                    WHERE email = ? AND role = 'parent'
                ");
                $stmt->execute([$formData['parent_email']]);
                $parent = $stmt->fetch();
                
                if (!$parent) {
                    // Create new parent user only if they don't exist
                    // Generate a more unique username using timestamp
                    $username = 'parent_' . strtolower(str_replace(' ', '_', $formData['parent_name'])) . '_' . time();
                    $password = password_hash($formData['parent_phone'], PASSWORD_DEFAULT); // Use phone number as initial password
                    
                    // Insert parent without phone number
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, password, email, full_name, role)
                        VALUES (?, ?, ?, ?, 'parent')
                    ");
                    $stmt->execute([
                        $username,
                        $password,
                        $formData['parent_email'],
                        $formData['parent_name']
                    ]);
                    $parentId = $conn->lastInsertId();
                } else {
                    $parentId = $parent['id'];
                    
                    // Update parent's email if provided and different from current
                    if (!empty($formData['parent_email'])) {
                        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ? AND email != ?");
                        $stmt->execute([$formData['parent_email'], $parentId, $formData['parent_email']]);
                    }
                }
            }
            
            if (!$student) {
                // Create new student if doesn't exist
                $stmt = $conn->prepare("
                    INSERT INTO students (student_id, full_name, class, parent_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$formData['student_id'], $formData['student_name'], $formData['student_class'], $parentId]);
                $studentId = $conn->lastInsertId();
            } else {
                $studentId = $student['id'];
                
                // Update student's parent if not set
                $stmt = $conn->prepare("UPDATE students SET parent_id = ? WHERE id = ? AND parent_id IS NULL");
                $stmt->execute([$parentId, $studentId]);
            }
            
            // Insert case
            $stmt = $conn->prepare("
                INSERT INTO cases (student_id, reported_by, incident_description, incident_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$studentId, $_SESSION['user_id'], $formData['incident_description'], $formData['incident_date']]);
            $caseId = $conn->lastInsertId();
            
            // Handle file upload if present
            if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/evidence/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
                $fileName = $caseId . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetPath)) {
                    $stmt = $conn->prepare("UPDATE cases SET evidence_file = ? WHERE id = ?");
                    $stmt->execute([$fileName, $caseId]);
                }
            }
            
            // Create notifications for relevant users
            $notifyRoles = ['admin', 'chairperson', 'deputy_ethics', 'deputy_academics', 'deputy_admin', 'dean', 'headteacher'];
            $stmt = $conn->prepare("
                INSERT INTO notifications (case_id, user_id, message)
                SELECT ?, id, ?
                FROM users
                WHERE role IN (" . implode(',', array_fill(0, count($notifyRoles), '?')) . ")
            ");
            
            $message = "New disciplinary case reported for student: " . $formData['student_name'] . " (" . $formData['student_id'] . ")";
            $params = array_merge([$caseId, $message], $notifyRoles);
            $stmt->execute($params);
            
            $conn->commit();
            $success = 'Case reported successfully';
            
            // Clear form data on success
            $formData = array_fill_keys(array_keys($formData), '');
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error reporting case: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Report Disciplinary Case</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="student_name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="student_name" name="student_name" 
                               value="<?php echo htmlspecialchars($formData['student_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID/Registration Number</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               value="<?php echo htmlspecialchars($formData['student_id']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_class" class="form-label">Class</label>
                        <input type="text" class="form-control" id="student_class" name="student_class" 
                               value="<?php echo htmlspecialchars($formData['student_class']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="incident_date" class="form-label">Incident Date</label>
                        <input type="date" class="form-control" id="incident_date" name="incident_date" 
                               value="<?php echo htmlspecialchars($formData['incident_date']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="incident_description" class="form-label">Incident Description</label>
                        <textarea class="form-control" id="incident_description" name="incident_description" 
                                  rows="4" required><?php echo htmlspecialchars($formData['incident_description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="evidence" class="form-label">Evidence (Optional)</label>
                        <input type="file" class="form-control" id="evidence" name="evidence">
                        <div class="form-text">Supported formats: PDF, DOC, DOCX, JPG, PNG</div>
                    </div>

                    <hr>
                    <h6>Parent Information</h6>
                    
                    <div class="mb-3">
                        <label for="parent_name" class="form-label">Parent Name</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name" 
                               value="<?php echo htmlspecialchars($formData['parent_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_phone" class="form-label">Parent Phone Number</label>
                        <input type="tel" class="form-control" id="parent_phone" name="parent_phone" 
                               value="<?php echo htmlspecialchars($formData['parent_phone']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_email" class="form-label">Parent Email (Optional)</label>
                        <input type="email" class="form-control" id="parent_email" name="parent_email" 
                               value="<?php echo htmlspecialchars($formData['parent_email']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 