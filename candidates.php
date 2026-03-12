<?php
session_start();
include "db.php";

// Protect page: only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role']; 
$voter_id = $_SESSION['user_id'];

// ✅ Handle adding candidate (admin only, with image upload)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_candidate']) && $role === "admin") {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/candidates/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        }
    }

    if (!empty($name) && !empty($position)) {
        $stmt = $conn->prepare("INSERT INTO candidates (name, position, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $position, $imagePath);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: candidates.php");
    exit;
}

// ✅ Handle editing candidate (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_candidate']) && $role === "admin") {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);

    // Check if new image uploaded
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/candidates/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        }
    }

    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE candidates SET name=?, position=?, image=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $position, $imagePath, $id);
    } else {
        $stmt = $conn->prepare("UPDATE candidates SET name=?, position=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $position, $id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: candidates.php");
    exit;
}

// ✅ Handle deleting candidate (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_candidate']) && $role === "admin") {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM votes WHERE candidate_id=$id");
    $stmt = $conn->prepare("DELETE FROM candidates WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: candidates.php");
    exit;
}

// ✅ Handle voting (voter only, prevent double votes per position)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote_candidate']) && $role === "voter") {
    $candidate_id = intval($_POST['candidate_id']);

    $pos_query = $conn->prepare("SELECT position FROM candidates WHERE id = ?");
    $pos_query->bind_param("i", $candidate_id);
    $pos_query->execute();
    $pos_result = $pos_query->get_result();
    $position = $pos_result->fetch_assoc()['position'];
    $pos_query->close();

    $check = $conn->prepare("SELECT * FROM votes WHERE voter_id = ? AND position = ?");
    $check->bind_param("is", $voter_id, $position);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $voter_id, $candidate_id, $position);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "<script>alert('⚠️ You already voted for $position!');</script>";
    }
    header("Location: candidates.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Candidate List - NDSC E-Voting System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f9f9f9; font-family: Arial, sans-serif; }
    .topbar { background-color: #0f6d2f; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; color: white; }
    .logo { height: 40px; margin-right: 10px; }
    .candidate-img { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; }
  </style>
</head>
<body>
  <!-- Top Navbar -->
  <div class="topbar">
    <div class="d-flex align-items-center">
      <img src="img/logo1.png" alt="Logo" class="logo">
      <strong>Notre Dame of Salaman College, Inc. - Candidates</strong>
    </div>
    <div>👋 Welcome, <?php echo $_SESSION['Full_Name']; ?> | <a href="dashboard.php" class="text-white">Back to Dashboard</a></div>
  </div>

<div class="container">

  <?php if ($role === "admin"): ?>
    <!-- ✅ Admin: Add Candidate Form -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white">Add Candidate</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="row g-2">
            <div class="col-md-4">
              <input type="text" name="name" class="form-control" placeholder="Candidate Name" required>
            </div>
            <div class="col-md-3">
              <input type="text" name="position" class="form-control" placeholder="Position" required>
            </div>
            <div class="col-md-3">
              <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <div class="col-md-2">
              <button type="submit" name="add_candidate" class="btn btn-success w-100">Add</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ✅ Admin: Candidate Table with Edit/Delete -->
    <h3>📋 Candidate List</h3>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Photo</th>
          <th>ID</th>
          <th>Name</th>
          <th>Position</th>
          <th>Total Votes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $candidate_list = $conn->query("
            SELECT c.id, c.name, c.position, c.image, COUNT(v.id) AS votes
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            GROUP BY c.id, c.name, c.position, c.image
            ORDER BY c.position, c.name
        ");

        if ($candidate_list->num_rows > 0) {
          while ($cand = $candidate_list->fetch_assoc()) {
            $photo = $cand['image'] ? $cand['image'] : 'img/default.png';
            echo "<tr>
                    <td><img src='{$photo}' class='candidate-img'></td>
                    <td>{$cand['id']}</td>
                    <td>{$cand['name']}</td>
                    <td>{$cand['position']}</td>
                    <td>{$cand['votes']}</td>
                    <td>
                      <form method='POST' enctype='multipart/form-data' style='display:inline-block;'>
                        <input type='hidden' name='id' value='{$cand['id']}'>
                        <input type='text' name='name' value='{$cand['name']}' required>
                        <input type='text' name='position' value='{$cand['position']}' required>
                        <input type='file' name='image' accept='image/*'>
                        <button type='submit' name='edit_candidate' class='btn btn-warning btn-sm'>Edit</button>
                      </form>
                      <form method='POST' style='display:inline-block;' onsubmit='return confirm(\"Delete this candidate?\");'>
                        <input type='hidden' name='id' value='{$cand['id']}'>
                        <button type='submit' name='delete_candidate' class='btn btn-danger btn-sm'>Delete</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center'>No candidates registered yet.</td></tr>";
        }
        ?>
      </tbody>
    </table>

 <?php else: ?>
  <!-- ✅ Voter: Candidate List -->
  <h2 class="mb-4">🗳 Registered Candidates</h2>
  <?php
  $positions = $conn->query("SELECT DISTINCT position FROM candidates ORDER BY position ASC");

  if ($positions->num_rows > 0) {
    while ($pos = $positions->fetch_assoc()) {
      $position = $pos['position'];
      echo "<h4 class='mt-4 text-success'>📌 $position</h4>";
      echo "<div class='row'>";

      $candidate_list = $conn->prepare("
        SELECT c.id, c.name, c.image, COUNT(v.id) AS votes
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        WHERE c.position = ?
        GROUP BY c.id, c.name, c.image
        ORDER BY c.name ASC
      ");
      $candidate_list->bind_param("s", $position);
      $candidate_list->execute();
      $result = $candidate_list->get_result();

      if ($result->num_rows > 0) {
        while ($cand = $result->fetch_assoc()) {
          // ✅ Ensure valid image path
          $photo = (!empty($cand['image']) && file_exists($cand['image']))
                   ? $cand['image']
                   : 'img/default.png';

          echo "
            <div class='col-md-3 mb-3'>
              <div class='card text-center'>
                <img src='{$photo}' 
                     alt='Candidate Photo' 
                     class='mx-auto mt-3'
                     style='width:120px; height:120px; border-radius:50%; object-fit:cover;'>
                <div class='card-body'>
                  <h5 class='card-title'>{$cand['name']}</h5>
                  <span class='badge bg-dark mb-2'>{$cand['votes']} votes</span>
                  <form method='POST'>
                    <input type='hidden' name='candidate_id' value='{$cand['id']}'>
                    <button type='submit' name='vote_candidate' class='btn btn-sm btn-primary'>Vote</button>
                  </form>
                </div>
              </div>
            </div>
          ";
        }
      } else {
        echo "<p class='text-muted'>No candidates yet for $position.</p>";
      }
      echo "</div>";
    }
  } else {
    echo "<p class='text-center text-muted'>No candidates registered yet.</p>";
  }
  ?>
<?php endif; ?>


</div>
</body>
</html>
