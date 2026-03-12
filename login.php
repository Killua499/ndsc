<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Full_Name = trim($_POST['Full_Name']);
    $password  = $_POST['password'];
    $lrn       = trim($_POST['lrn']);

    // === ADMIN LOGIN (ignore LRN) ===
    $stmt = $conn->prepare("SELECT * FROM users WHERE Full_Name = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param("s", $Full_Name);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['Full_Name'] = $user['Full_Name']; // ✅ fixed column name
            $_SESSION['role']      = $user['role'];

            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('❌ Invalid admin password!'); window.location='index.php';</script>";
            exit;
        }
    }

    // === VOTER LOGIN (requires LRN) ===
    $stmt = $conn->prepare("SELECT * FROM users WHERE Full_Name = ? AND lrn = ? AND role = 'voter' LIMIT 1");
    $stmt->bind_param("ss", $Full_Name, $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['Full_Name'] = $user['Full_Name']; // ✅ fixed column name
            $_SESSION['role']      = $user['role'];

            header("Location: vote.php");
            exit;
        } else {
            echo "<script>alert('❌ Invalid voter password!'); window.location='index.php';</script>";
            exit;
        }
    }

    // If nothing matched
    echo "<script>alert('❌ Invalid login credentials!'); window.location='index.php';</script>";
}
?>
