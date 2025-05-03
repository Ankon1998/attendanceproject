<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$selected_user_id = null;
$attendance_records = [];
$message = '';

if ($role == 'admin') {
    // Fetch all users with role 'user' for dropdown
    $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'user' ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle user selection
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
        $selected_user_id = (int)$_POST['user_id'];
        if ($selected_user_id) {
            // Fetch selected user's attendance
            $stmt = $pdo->prepare("
                SELECT date, clock_in, clock_out
                FROM attendance
                WHERE user_id = ?
                ORDER BY date DESC
            ");
            $stmt->execute([$selected_user_id]);
            $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = "Please select a valid user.";
        }
    }
} else {
    // Fetch user's own attendance
    $stmt = $pdo->prepare("
        SELECT date, clock_in, clock_out
        FROM attendance
        WHERE user_id = ?
        ORDER BY date DESC
    ");
    $stmt->execute([$user_id]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate hours worked
function calculate_hours($clock_in, $clock_out) {
    if (!$clock_out) return 'N/A';
    $in = new DateTime($clock_in);
    $out = new DateTime($clock_out);
    $interval = $in->diff($out);
    $hours = $interval->h + ($interval->i / 60);
    return round($hours, 2) . ' hours';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>View Attendance</h2>
        <?php if ($message): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($role == 'admin'): ?>
            <form method="POST" action="attendance.php" class="user-select">
                <label for="user_id">Select User:</label>
                <select id="user_id" name="user_id" onchange="this.form.submit()">
                    <option value="">-- Select a User --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $selected_user_id == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($selected_user_id && empty($attendance_records)): ?>
                <p class="error">No attendance records found for this user.</p>
            <?php elseif ($selected_user_id): ?>
                <h3>Attendance for <?php echo htmlspecialchars($users[array_search($selected_user_id, array_column($users, 'id'))]['username']); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['date']); ?></td>
                                <td><?php echo date('H:i:s', strtotime($record['clock_in'])); ?></td>
                                <td><?php echo $record['clock_out'] ? date('H:i:s', strtotime($record['clock_out'])) : 'N/A'; ?></td>
                                <td><?php echo calculate_hours($record['clock_in'], $record['clock_out']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php else: ?>
            <h3>Your Attendance</h3>
            <?php if (empty($attendance_records)): ?>
                <p class="error">No attendance records found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['date']); ?></td>
                                <td><?php echo date('H:i:s', strtotime($record['clock_in'])); ?></td>
                                <td><?php echo $record['clock_out'] ? date('H:i:s', strtotime($record['clock_out'])) : 'N/A'; ?></td>
                                <td><?php echo calculate_hours($record['clock_in'], $record['clock_out']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>