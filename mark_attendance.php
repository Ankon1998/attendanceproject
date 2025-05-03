<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $current_date = date('Y-m-d');
    $current_time = date('Y-m-d H:i:s');

    try {
        // Check if there's an existing record for today
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ? LIMIT 1");
        $stmt->execute([$user_id, $current_date]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($action === 'clock_in') {
            if ($attendance) {
                if ($attendance['clock_in']) {
                    $message = "You have already clocked in today at " . date('H:i', strtotime($attendance['clock_in'])) . ".";
                } else {
                    // Update existing record with clock-in time
                    $stmt = $pdo->prepare("UPDATE attendance SET clock_in = ? WHERE id = ?");
                    $stmt->execute([$current_time, $attendance['id']]);
                    $message = "Clocked in successfully at " . date('H:i', strtotime($current_time)) . ".";
                }
            } else {
                // Insert new record for clock-in
                $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, clock_in) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $current_date, $current_time]);
                $message = "Clocked in successfully at " . date('H:i', strtotime($current_time)) . ".";
            }
        } elseif ($action === 'clock_out') {
            if (!$attendance || !$attendance['clock_in']) {
                $message = "You need to clock in before clocking out.";
            } elseif ($attendance['clock_out']) {
                $message = "You have already clocked out today at " . date('H:i', strtotime($attendance['clock_out'])) . ".";
            } else {
                // Update existing record with clock-out time
                $stmt = $pdo->prepare("UPDATE attendance SET clock_out = ? WHERE id = ?");
                $stmt->execute([$current_time, $attendance['id']]);
                $message = "Clocked out successfully at " . date('H:i', strtotime($current_time)) . ".";
            }
        } else {
            $message = "Invalid action.";
        }
    } catch (PDOException $e) {
        error_log("Mark Attendance Error: " . $e->getMessage());
        $message = "An error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Clock In/Out</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Mark Attendance</h2>
        <p>Welcome, <?php echo htmlspecialchars($username); ?>! (<?php echo ucfirst($role); ?>)</p>

        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="mark_attendance.php">
            <button type="submit" name="action" value="clock_in" class="primary">Clock In</button>
            <button type="submit" name="action" value="clock_out" class="primary">Clock Out</button>
        </form>

        <p><a href="dashboard.php" class="secondary button">Back to Dashboard</a></p>
    </div>
</body>
</html>