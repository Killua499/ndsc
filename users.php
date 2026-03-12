<?php
session_start();
include "db.php";

// ✅ Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch all users including course
$result = $conn->query("SELECT id, Full_Name, lrn, course, role FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User List - NDSC E-VOTING</title>
  <link rel="icon" type="image/png" href="img/logo.png">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

  <style>
    body {
      background: #f4f4f4;
      font-family: Arial, sans-serif;
    }
    .topbar {
      background-color: #0f6d2f;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 15px;
      color: white;
    }
    .logo {
      height: 40px;
      margin-right: 10px;
    }
    .container {
      background: #fff;
      padding: 20px;
      margin-top: 20px;
      border-radius: 8px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    }
    .nav-links a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-weight: bold;
    }
    .nav-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- ✅ Top Navbar (same as dashboard) -->
  <div class="topbar">
    <div class="d-flex align-items-center">
      <img src="img/logo1.png" alt="Logo" class="logo">
      <strong>Notre Dame of Salaman College, Inc. - Admin Panel</strong>
    </div>
    <div class="nav-links">
      <a href="dashboard.php">Dashboard</a>
      <a href="candidates.php">Candidates</a>
      <a href="users_list.php">Users</a>
      <a href="logout.php" class="text-warning">Logout</a>
    </div>
  </div>

  <!-- ✅ User List -->
  <div class="container">
    <h2 class="mb-4">Registered Users</h2>
    <table id="usersTable" class="display nowrap table table-striped" style="width:100%">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>LRN</th>
          <th>Course</th>
          <th>Role</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['Full_Name']) ?></td>
            <td><?= htmlspecialchars($row['lrn']) ?></td>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td><?= ucfirst($row['role']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Bootstrap + jQuery + DataTables -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

  <script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'print',
                {
                  extend: 'excelHtml5',
                  title: 'User_List'
                }
            ]
        });
    });
  </script>
</body>
</html>
