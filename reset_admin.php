<?php
include "db.php";

// Delete all other admins, keep only "Admin User"
$conn->query("DELETE FROM users WHERE role='admin' AND Full_Name!='Admin User'");

// Reset Admin User password to admin123
$newPassword = password_hash("admin123", PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password='$newPassword' WHERE Full_Name='Admin User' AND role='admin'");

echo "✅ Admin User account reset successfully.<br>";
echo "👉 Full Name: Admin User<br>";
echo "👉 Password: admin123<br>";
?>
