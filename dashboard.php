<?php
session_start();
include "db.php";

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ✅ Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admins to voting page
    header("Location: vote.php");
    exit;
}

// 🔹 Fetch stats
$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_candidates = $conn->query("SELECT COUNT(*) AS count FROM candidates")->fetch_assoc()['count'];
$total_votes = $conn->query("SELECT COUNT(*) AS count FROM votes")->fetch_assoc()['count'];

// 🔹 Fetch votes per candidate
$candidate_votes = $conn->query("
    SELECT c.name, c.position, COUNT(v.id) AS votes
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    GROUP BY c.id, c.name, c.position
    ORDER BY c.position, c.name
");

$names = [];
$votes = [];
while ($row = $candidate_votes->fetch_assoc()) {
    $names[] = $row['name'] . " (" . $row['position'] . ")";
    $votes[] = $row['votes'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NDSC E-Voting Dashboard</title>
   <link rel="icon" type="image" href="img\logo1.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #fff;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    /* Top Navbar */
    .topbar {
      background-color: #0f6d2f;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 15px;
      color: white;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .hamburger {
      font-size: 26px;
      color: white;
      cursor: pointer;
    }
    .topbar-title {
      font-size: 18px;
      font-weight: bold;
    }
    .welcome {
      font-size: 16px;
    }
    .logo {
      height: 40px;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: -250px;
      width: 250px;
      height: 100%;
      background-color: #0f6d2f;
      padding-top: 60px;
      transition: 0.3s;
      z-index: 1000;
    }
    .sidebar a {
      display: block;
      padding: 15px 20px;
      color: white;
      text-decoration: none;
      font-size: 18px;
    }
    .sidebar a:hover {
      background-color: #145a24;
    }
    .sidebar.active {
      left: 0;
    }

    .content {
      padding: 20px;
    }
  </style>
</head>
<body>
  <!-- Top Navbar -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>
      <img src="img/logo1.png" alt="Logo" class="logo">
      <div class="topbar-title">Notre Dame of Salaman College, Inc. - E-Voting System</div>
    </div>
    <div class="welcome">👋 Welcome, <?php echo $_SESSION['Full_Name']; ?></div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="users.php">👥 Users</a>
    <a href="candidates.php">🗳 Candidates</a>
    <a href="votes.php">📊 Votes</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <!-- Dashboard Content -->
  <div class="container content">
    <h2 class="mb-4">📊 E-Voting Dashboard</h2>

    <div class="row text-center mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h3><?php echo $total_users; ?></h3>
            <p>Registered Users</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <h3><?php echo $total_candidates; ?></h3>
            <p>Registered Candidates</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <h3><?php echo $total_votes; ?></h3>
            <p>Total Votes Cast</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Bar Chart -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">Votes per Candidate</div>
          <div class="card-body">
            <canvas id="barChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Pie Chart -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">Vote Distribution</div>
          <div class="card-body">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Candidate List -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">🗳 List of Candidates</div>
          <div class="card-body">
            <table class="table table-bordered table-striped">
              <thead class="table-dark">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Total Votes</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Re-run the same candidate query but with votes included
                $candidate_list = $conn->query("
                    SELECT c.id, c.name, c.position, COUNT(v.id) AS votes
                    FROM candidates c
                    LEFT JOIN votes v ON c.id = v.candidate_id
                    GROUP BY c.id, c.name, c.position
                    ORDER BY c.position, c.name
                ");

                while ($cand = $candidate_list->fetch_assoc()) { ?>
                  <tr>
                    <td><?php echo $cand['id']; ?></td>
                    <td><?php echo $cand['name']; ?></td>
                    <td><?php echo $cand['position']; ?></td>
                    <td><?php echo $cand['votes']; ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("active");
    }

    const names = <?php echo json_encode($names); ?>;
    const votes = <?php echo json_encode($votes); ?>;

    // Bar Chart
    new Chart(document.getElementById('barChart'), {
      type: 'bar',
      data: {
        labels: names,
        datasets: [{
          label: 'Votes',
          data: votes,
          backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
      },
      options: { responsive: true }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
      type: 'pie',
      data: {
        labels: names,
        datasets: [{
          data: votes,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
          ]
        }]
      },
      options: { responsive: true }
    });
  </script>
</body>
</html>
