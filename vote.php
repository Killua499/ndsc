<?php
session_start();
include "db.php";

// ✅ Only voters can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    header("Location: index.php");
    exit;
}

$voter_id = $_SESSION['user_id'];

// ✅ Handle voting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote_candidate'])) {
    $candidate_id = intval($_POST['candidate_id']);

    // Get candidate position
    $pos_query = $conn->prepare("SELECT position FROM candidates WHERE id = ?");
    $pos_query->bind_param("i", $candidate_id);
    $pos_query->execute();
    $pos_result = $pos_query->get_result();
    $position = $pos_result->fetch_assoc()['position'] ?? null;
    $pos_query->close();

    if ($position) {
        // Check if voter already voted for this position
        $check = $conn->prepare("SELECT * FROM votes WHERE voter_id = ? AND position = ?");
        $check->bind_param("is", $voter_id, $position);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $voter_id, $candidate_id, $position);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }

    header("Location: vote.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cast Your Vote - NDSC E-Voting</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      background-color: #f9f9f9;
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
    .candidate-img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 3px solid #ddd;
      background: #fff;
    }
  </style>
</head>
<body>

  <!-- ✅ Top Navbar -->
  <div class="topbar">
    <div class="d-flex align-items-center">
      <img src="img/logo1.png" alt="Logo" class="logo">
      <strong>Notre Dame of Salaman College E-Voting</strong>
    </div>
    <div>👋 Hi, <?php echo htmlspecialchars($_SESSION['Full_Name']); ?> | 
      <a href="logout.php" class="text-warning">Logout</a></div>
  </div>

  <div class="container my-4">
    <h2 class="mb-4">🗳 Cast Your Vote</h2>

    <?php
    $positions = $conn->query("SELECT DISTINCT position FROM candidates ORDER BY position ASC");

    if ($positions->num_rows > 0) {
      while ($pos = $positions->fetch_assoc()) {
        $position = $pos['position'];

        // Check if this voter already voted for this position
        $check_vote = $conn->prepare("SELECT candidate_id FROM votes WHERE voter_id = ? AND position = ?");
        $check_vote->bind_param("is", $voter_id, $position);
        $check_vote->execute();
        $vote_result = $check_vote->get_result();
        $already_voted = $vote_result->num_rows > 0;
        $chosen_candidate = $already_voted ? $vote_result->fetch_assoc()['candidate_id'] : null;
        $check_vote->close();

        echo "<div class='card shadow mb-4'>";
        echo "<div class='card-header bg-success text-white'><strong>$position</strong></div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";

        // Fetch candidates for this position
        $cand_list = $conn->prepare("SELECT id, name, image FROM candidates WHERE position = ? ORDER BY name ASC");
        $cand_list->bind_param("s", $position);
        $cand_list->execute();
        $cand_result = $cand_list->get_result();

        if ($cand_result->num_rows > 0) {
          while ($cand = $cand_result->fetch_assoc()) {
            // ✅ Fix image path
            $photo = "img/default.png"; // fallback
            if (!empty($cand['image'])) {
                // if DB only stores filename
                if (file_exists("uploads/" . $cand['image'])) {
                    $photo = "uploads/" . $cand['image'];
                }
                // if DB stores full path
                elseif (file_exists($cand['image'])) {
                    $photo = $cand['image'];
                }
            }

            echo "
              <div class='col-md-3 mb-3'>
                <div class='card text-center h-100'>
                  <img src='{$photo}' class='candidate-img mx-auto mt-3' alt='Candidate'>
                  <div class='card-body'>
                    <h6 class='card-title'>{$cand['name']}</h6>";
            
            if ($already_voted) {
                if ($cand['id'] == $chosen_candidate) {
                    echo "<span class='badge bg-success'>✔ Your Vote</span>";
                }
                echo "<button class='btn btn-secondary btn-sm mt-2' disabled>Vote</button>";
            } else {
                echo "
                    <form method='POST'>
                      <input type='hidden' name='candidate_id' value='{$cand['id']}'>
                      <button type='submit' name='vote_candidate' class='btn btn-primary btn-sm mt-2'>Vote</button>
                    </form>
                ";
            }

            echo "  </div>
                </div>
              </div>
            ";
          }
        } else {
          echo "<p class='text-muted'>No candidates yet for $position.</p>";
        }

        echo "</div>";

        if ($already_voted) {
          echo "<p class='text-success mt-3'>✔ You already voted for this position.</p>";
        }

        echo "</div></div>";
      }
    } else {
      echo "<p class='text-center text-muted'>No positions/candidates available yet.</p>";
    }
    ?>
  </div>
</body>
</html>
