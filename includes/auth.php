<?php
session_start();
require_once 'db_connection.php';

function login($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, password, role FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: ../public/login.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: ../public/login.php");
}
?>