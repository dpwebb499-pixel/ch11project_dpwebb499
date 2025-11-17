<?php
// index.php
// Simple page demonstrating connection + calling view and stored procedures.
// Replace DB credentials below with your AMPPS credentials.

$db_host = 'localhost';
$db_user = 'root';     // <-- replace
$db_pass = 'mysql';     // <-- replace
$db_name = 'taus_data';

// Connect (mysqli)
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Handle insert form submission
$insert_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firstName'], $_POST['lastName'], $_POST['email'])) {
    $first = trim($_POST['firstName']);
    $last  = trim($_POST['lastName']);
    $email = trim($_POST['email']);

    // Call stored procedure sp_insertStudent with prepared statement
    $stmt = $mysqli->prepare("CALL sp_insertStudent(?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $first, $last, $email);
        if ($stmt->execute()) {
            $insert_msg = "Student inserted. If procedure returned an ID it may be shown below.";
            // After calling a stored procedure, if it returns results you may need to fetch them.
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $insert_msg .= " New ID: " . ($row['new_studentID'] ?? 'N/A');
            }
            // flush multi-results
            while ($mysqli->more_results()) {
                $mysqli->next_result();
                $extra = $mysqli->use_result();
                if ($extra instanceof mysqli_result) { $extra->free(); }
            }
        } else {
            $insert_msg = "Execute failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $insert_msg = "Prepare failed: " . $mysqli->error;
    }
}

// Fetch all students using stored procedure sp_getStudents
$students = [];
if ($res = $mysqli->query("CALL sp_getStudents()")) {
    while ($r = $res->fetch_assoc()) { $students[] = $r; }
    $res->free();
    // flush extra result sets if any
    while ($mysqli->more_results()) {
        $mysqli->next_result();
        $extra = $mysqli->use_result();
        if ($extra instanceof mysqli_result) { $extra->free(); }
    }
}

// Fetch vw_student_classes (view)
$enrollments = [];
$q = "SELECT * FROM vw_student_classes ORDER BY studentID";
if ($res2 = $mysqli->query($q)) {
    while ($row = $res2->fetch_assoc()) { $enrollments[] = $row; }
    $res2->free();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CH11 Project - MySQL / phpMyAdmin Demo</title>
  <link rel="stylesheet" href="styles/main.css">
</head>
<body>
  <main class="container">
    <h1>CH11 Project — Database & Stored Procedures</h1>
    <p class="author">Your name: Dylan Webb</p>
    <p class="date">Date: <?php echo date('F j, Y'); ?></p>

    <section>
      <h2>Students (from sp_getStudents)</h2>
      <?php if ($insert_msg): ?>
        <div class="notice"><?php echo htmlspecialchars($insert_msg); ?></div>
      <?php endif; ?>

      <?php if (count($students) === 0): ?>
        <p>No students found.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>ID</th><th>First</th><th>Last</th><th>Email</th></tr></thead>
          <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?php echo htmlspecialchars($s['studentID']); ?></td>
              <td><?php echo htmlspecialchars($s['firstName']); ?></td>
              <td><?php echo htmlspecialchars($s['lastName']); ?></td>
              <td><?php echo htmlspecialchars($s['email']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <section>
      <h2>Enrollments (view: vw_student_classes)</h2>
      <table>
        <thead><tr><th>Student</th><th>Email</th><th>Class</th><th>Location</th></tr></thead>
        <tbody>
        <?php
          if (count($enrollments) === 0) {
            echo '<tr><td colspan="4">No enrollment data.</td></tr>';
          } else {
            foreach ($enrollments as $e) {
              $student = htmlspecialchars($e['firstName'] . ' ' . $e['lastName']);
              $email   = htmlspecialchars($e['email']);
              $class   = htmlspecialchars($e['className'] ?? '—');
              $loc     = htmlspecialchars($e['location'] ?? '—');
              echo "<tr><td>{$student}</td><td>{$email}</td><td>{$class}</td><td>{$loc}</td></tr>";
            }
          }
        ?>
        </tbody>
      </table>
    </section>

    <section>
      <h2>Insert new student (calls sp_insertStudent)</h2>
      <form method="post" class="insert-form">
        <label>First name: <input type="text" name="firstName" required></label><br>
        <label>Last name: <input type="text" name="lastName" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <button type="submit">Insert Student</button>
      </form>
    </section>

    <footer>
      <p>Project: ch11project_USERNAME — replace USERNAME with your school username</p>
    </footer>
  </main>
</body>
</html>
