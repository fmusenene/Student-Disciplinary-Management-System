<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user has admin permission
if (!hasPermission('admin')) {
    header('Location: index.php');
    exit();
}

// Check if required data is provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['case_id']) || !isset($_POST['status']) || !isset($_POST['details'])) {
    header('Location: index.php');
    exit();
}

$caseId = $_POST['case_id'];
$newStatus = $_POST['status'];
$details = $_POST['details'];

// Validate status
$validStatuses = ['in_review', 'resolved', 'dismissed'];
if (!in_array($newStatus, $validStatuses)) {
    $_SESSION['error'] = 'Invalid status provided';
    header('Location: view_case.php?id=' . $caseId);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Update case status
    $stmt = $conn->prepare("
        UPDATE cases 
        SET status = ?, 
            action_taken = CASE 
                WHEN action_taken IS NULL THEN ? 
                ELSE CONCAT(action_taken, '\n\n', ?) 
            END
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $details, $details, $caseId]);

    // Add to case history
    $stmt = $conn->prepare("
        INSERT INTO case_history (case_id, action, details, updated_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $caseId,
        'status_update',
        "Status changed to: " . ucfirst(str_replace('_', ' ', $newStatus)) . "\nDetails: " . $details,
        $_SESSION['user_id']
    ]);

    // Create notification for the case reporter
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, case_id, message, is_read)
        SELECT reported_by, id, ?, 0
        FROM cases
        WHERE id = ?
    ");
    $notificationMessage = "Case #" . $caseId . " status has been updated to " . ucfirst(str_replace('_', ' ', $newStatus));
    $stmt->execute([$notificationMessage, $caseId]);

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Case status updated successfully';
    
    // Redirect based on new status
    if ($newStatus === 'in_review') {
        header('Location: review_cases.php');
    } else {
        header('Location: view_case.php?id=' . $caseId);
    }
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    $_SESSION['error'] = 'Error updating case status: ' . $e->getMessage();
    header('Location: view_case.php?id=' . $caseId);
    exit();
}
?> 