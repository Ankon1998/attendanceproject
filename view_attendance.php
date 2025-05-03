<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch user’s attendance (for regular users)
if ($role == 'user') {
    $stmt = $pdo->prepare("SELECT a.id, a.date, a.clock_in, a.clock_out FROM attendance a WHERE a.user_id = ? ORDER BY a.date DESC");
    $stmt->execute([$user_id]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all users’ attendance (for admins)
if ($role == 'admin') {
    $stmt = $pdo->query("
        SELECT a.id, u.username, a.date, a.clock_in, a.clock_out
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id
        WHERE u.role = 'user'
        ORDER BY u.username, a.date DESC
    ");
    $all_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by username
    $user_attendance = [];
    foreach ($all_attendance as $record) {
        $username = $record['username'];
        if (!isset($user_attendance[$username])) {
            $user_attendance[$username] = [];
        }
        if ($record['date']) {
            $user_attendance[$username][] = $record;
        }
    }
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
        <?php if ($role == 'user'): ?>
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
        <?php elseif ($role == 'admin'): ?>
            <h3>All Users' Attendance</h3>
            <?php if (empty($user_attendance)): ?>
                <p class="error">No attendance records found.</p>
            <?php else: ?>
                <?php foreach ($user_attendance as $username => $records): ?>
                    <h4><?php echo htmlspecialchars($username); ?></h4>
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
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($record['clock_in'])); ?></td>
                                    <td><?php echo $record['clock_out'] ? date('H:i:s', strtotime($record['clock_out'])) : 'N/A'; ?></td>
                                    <td><?php echo calculate_hours($record['clock_in'], $record['clock_out']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>