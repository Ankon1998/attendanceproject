<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Default date range: last 7 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
    $start_date = date('Y-m-d', strtotime('-6 days'));
    $end_date = date('Y-m-d');
}

// Fetch attendance data
if ($role == 'user') {
    $stmt = $pdo->prepare("
        SELECT date, clock_in, clock_out
        FROM attendance
        WHERE user_id = ? AND date BETWEEN ? AND ?
        ORDER BY date
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($role == 'admin') {
    $stmt = $pdo->query("
        SELECT u.username, a.date, a.clock_in, a.clock_out
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id
        WHERE u.role = 'user' AND (a.date BETWEEN '$start_date' AND '$end_date' OR a.date IS NULL)
        ORDER BY u.username, a.date
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
    if (!$clock_out) return 0; // Return 0 for chart data
    $in = new DateTime($clock_in);
    $out = new DateTime($clock_out);
    $interval = $in->diff($out);
    $hours = $interval->h + ($interval->i / 60);
    return round($hours, 2);
}

// Prepare chart data
$chart_labels = [];
$chart_data = [];
if ($role == 'user') {
    $current_date = new DateTime($start_date);
    $end = new DateTime($end_date);
    while ($current_date <= $end) {
        $date_str = $current_date->format('Y-m-d');
        $chart_labels[] = $date_str;
        $hours = 0;
        foreach ($attendance_records as $record) {
            if ($record['date'] == $date_str) {
                $hours = calculate_hours($record['clock_in'], $record['clock_out']);
                break;
            }
        }
        $chart_data[] = $hours;
        $current_date->modify('+1 day');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Attendance Reports</h2>
        <form method="GET" action="reports.php" class="date-filter">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
            <button type="submit">Filter</button>
        </form>

        <?php if ($role == 'user'): ?>
            <h3>Your Hours Worked</h3>
            <?php if (empty($attendance_records)): ?>
                <p class="error">No attendance records found for the selected period.</p>
            <?php else: ?>
                <canvas id="hoursChart" style="max-height: 400px;"></canvas>
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
                                <td><?php echo calculate_hours($record['clock_in'], $record['clock_out']) ?: '0'; ?> hours</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif ($role == 'admin'): ?>
            <h3>All Users' Hours Worked</h3>
            <?php if (empty($user_attendance)): ?>
                <p class="error">No attendance records found for the selected period.</p>
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
                                    <td><?php echo calculate_hours($record['clock_in'], $record['clock_out']) ?: '0'; ?> hours</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
    <?php if ($role == 'user' && !empty($attendance_records)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('hoursChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Hours Worked',
                            data: <?php echo json_encode($chart_data); ?>,
                            backgroundColor: 'rgba(56, 161, 105, 0.6)', // Green from style.css
                            borderColor: 'rgba(56, 161, 105, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Hours' }
                            },
                            x: {
                                title: { display: true, text: 'Date' }
                            }
                        }
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>