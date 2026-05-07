<?php
session_start();
require_once 'db_connect.php';

function register($username, $password, $email, $role) {
    global $pdo;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$username, $hashed_password, $email, $role]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}


function ensureProfileResumeColumn() {
    global $pdo;
    static $checked = false;

    if ($checked) {
        return;
    }

    try {
        $columns = [
            'resume_file' => "ALTER TABLE profiles ADD COLUMN resume_file VARCHAR(255) NULL",
            'profile_photo' => "ALTER TABLE profiles ADD COLUMN profile_photo VARCHAR(255) NULL"
        ];

        foreach ($columns as $column => $alterQuery) {
            $stmt = $pdo->query("SHOW COLUMNS FROM profiles LIKE " . $pdo->quote($column));

            if (!$stmt->fetch()) {
                $pdo->exec($alterQuery);
            }
        }

    } catch (PDOException $e) {
        error_log("Profile column check error: " . $e->getMessage());
    }

    $checked = true;
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: index.php");
        exit();
    }
}
?>
