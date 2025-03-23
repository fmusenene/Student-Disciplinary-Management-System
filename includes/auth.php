<?php
session_start();
require_once 'config/database.php';

function login($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function hasPermission($requiredRole) {
    if (!isLoggedIn()) return false;
    
    $roleHierarchy = [
        'admin' => 0,
        'headteacher' => 1,
        'deputy_ethics' => 2,
        'deputy_academics' => 2,
        'deputy_admin' => 2,
        'dean' => 3,
        'chairperson' => 3,
        'teacher' => 4,
        'parent' => 5
    ];
    
    return $roleHierarchy[$_SESSION['role']] <= $roleHierarchy[$requiredRole];
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?> 