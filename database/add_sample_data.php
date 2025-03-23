<?php
require_once __DIR__ . '/../config/database.php';

try {
    // First, get a parent user (assuming they exist)
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'parent' LIMIT 1");
    $stmt->execute();
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        // Create a parent user if none exists
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, full_name, role, email) 
            VALUES (?, ?, ?, 'parent', ?)
        ");
        $password = password_hash('parent123', PASSWORD_DEFAULT);
        $stmt->execute(['parent1', $password, 'Parent One', 'parent1@example.com']);
        $parentId = $conn->lastInsertId();
    } else {
        $parentId = $parent['id'];
    }

    // Get some students
    $stmt = $conn->prepare("SELECT id FROM students LIMIT 2");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Link students to parent
    $stmt = $conn->prepare("
        INSERT IGNORE INTO student_parent (student_id, parent_id) 
        VALUES (?, ?)
    ");

    foreach ($students as $student) {
        $stmt->execute([$student['id'], $parentId]);
        echo "Linked student {$student['id']} to parent {$parentId}\n";
    }

    echo "Sample data added successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 