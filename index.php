<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NDSC E-VOTING SYSTEM - Login</title>
  <link rel="icon" type="image/png" href="img/logo.png" />
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="form-box">
      <h1>Notre Dame of Salaman College, INC.</h1>
      <img src="img/logo.png" alt="Logo" class="logo">
      <p class="subtitle">NDSC E-VOTING SYSTEM</p>

      <form action="login.php" method="POST">
        <label>Full Name</label>
        <input type="text" name="Full_Name" id="fullname" placeholder="Enter Full Name" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter Password" required>
      
        <div id="lrn-field">
          <label>LRN</label>
          <input type="text" name="lrn" placeholder="Enter LRN">
        </div>
      
        <button type="submit">LOGIN</button>
        <a href="register.php" class="link">No Account? Register here.</a>
      </form>
    </div>
  </div>

  <script>
    const fullnameInput = document.getElementById("fullname");
    const lrnField = document.getElementById("lrn-field");

    fullnameInput.addEventListener("input", function() {
      if (this.value.trim().toLowerCase() === "administrator") {
        lrnField.style.display = "none"; // hide for admin
      } else {
        lrnField.style.display = "block"; // show for voters
      }
    });
  </script>
</body>
</html>
