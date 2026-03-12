<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Full_Name = trim($_POST['Full_Name']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $lrn       = trim($_POST['lrn']);
    $course    = $_POST['course'];
    $role      = "voter"; // ✅ Always voter

    // Check if Full_Name or LRN exists
    $check = $conn->prepare("SELECT * FROM users WHERE full_name=? OR lrn=?");
    $check->bind_param("ss", $Full_Name, $lrn);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Full Name or LRN already exists!'); window.location='register.php';</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, password, lrn, course, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $Full_Name, $password, $lrn, $course, $role);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful! Please login.'); window.location='index.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NDSC E-VOTING SYSTEM - Register</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="img/logo.png">
</head>
<body>
  <div class="container">
    <div class="form-box">
      <h2>Registration Form</h2>
      <img src="img/logo.png" alt="Logo" class="logo">
      <p class="subtitle">NDSC E-VOTING SYSTEM</p>

      <form action="register.php" method="POST">
        <label>Full Name</label>
        <input type="text" name="Full_Name" placeholder="Enter Full Name" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter Password" required>
      
        <label>LRN</label>
        <input type="text" name="lrn" placeholder="Enter LRN" required>

        <label>Course</label>
        <select name="course" required>
          <option value="BSIT">BSIT</option>
          <option value="BSBA">BSBA</option>
          <option value="BSCRIM">BSCRIM</option>
          <option value="BSED">BSED</option>
          <option value="BEED">BEED</option>
        </select>

        <!-- 🚫 Removed Role Selection -->
      
        <button type="submit">REGISTER</button>
        <a href="index.php" class="link">Back to Login</a>
      </form>
    </div>
  </div>
</body>
</html>
