<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$date = date('Y-m-d');
$attendance_status = 'Not Clocked In';
try {
    $stmt = $pdo->prepare("SELECT clock_in, clock_out FROM attendance WHERE user_id = ? AND date = ? LIMIT 1");
    $stmt->execute([$user_id, $date]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($attendance) {
        $attendance_status = $attendance['clock_in'] ? 'Clocked In at ' . date('H:i', strtotime($attendance['clock_in'])) : 'Not Clocked In';
        if ($attendance['clock_out']) {
            $attendance_status = 'Completed: Clocked Out at ' . date('H:i', strtotime($attendance['clock_out']));
        }
    }
} catch (PDOException $e) {
    error_log("Attendance Fetch Error: " . $e->getMessage());
    $attendance_status = 'Error fetching status';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clock In/Out</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="role">Role: <?php echo ucfirst($role); ?></p>
        </header>

        <section class="quick-stats">
            <div class="stat-card">
                <h4>Today's Attendance</h4>
                <p class="<?php echo strpos($attendance_status, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($attendance_status); ?>
                </p>
                <?php if ($role == 'user' && strpos($attendance_status, 'Not Clocked In') !== false): ?>
                    <a href="mark_attendance.php" class="primary button" style="margin-top: 1rem;">Clock In Now</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="nav-grid">
            <?php if ($role == 'admin'): ?>
                <div class="nav-grid">
    <div class="nav-card" onclick="location.href='manage_users.php'">
        <i class="fas fa-users"></i>
        <h3>Manage Users</h3>
        <p>View, edit, and control user access.</p>
        <a href="manage_users.php" class="button">Go</a>
    </div>
    <div class="nav-card" onclick="location.href='view_attendance.php'">
        <i class="fas fa-calendar-check"></i>
        <h3>View Attendance</h3>
        <p>Check daily attendance records.</p>
        <a href="view_attendance.php" class="button">View</a>
    </div>
    <div class="nav-card" onclick="location.href='reports.php'">
        <i class="fas fa-chart-line"></i>
        <h3>Reports</h3>
        <p>Get performance and activity reports.</p>
        <a href="reports.php" class="button">Open</a>
    </div>
    <div class="nav-card" onclick="location.href='calendar.php'">
        <i class="fas fa-calendar-alt"></i>
        <h3>Calendar</h3>
        <p>Track events and deadlines.</p>
        <a href="calendar.php" class="button">See Calendar</a>
    </div>
    <div class="nav-card" onclick="location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i>
        <h3>Logout</h3>
        <p>Securely sign out from your account.</p>
        <a href="logout.php" class="button">Logout</a>
    </div>
</div>
            <?php else: ?>
                <div class="nav-card primary-action" role="group" aria-label="Mark Attendance">
                    <i class="fas fa-clock fa-2x" aria-hidden="true"></i>
                    <h3>Mark Attendance</h3>
                    <p>Clock in or out for today.</p>
                    <a href="mark_attendance.php" class="primary button" aria-label="Go to Mark Attendance">Mark Attendance</a>
                </div>
                <div class="nav-card secondary-action" role="group" aria-label="View Attendance">
                    <i class="fas fa-clipboard-list fa-2x" aria-hidden="true"></i>
                    <h3>View Attendance</h3>
                    <p>Review your attendance history.</p>
                    <a href="attendance.php" class="primary button" aria-label="View Attendance History">View History</a>
                </div>
                <div class="nav-card secondary-action" role="group" aria-label="Calendar">
                    <i class="fas fa-calendar-alt fa-2x" aria-hidden="true"></i>
                    <h3>Calendar</h3>
                    <p>View attendance calendar.</p>
                    <a href="calendar.php" class="primary button" aria-label="View Calendar">View Calendar</a>
                </div>
                <div class="nav-card secondary-action" role="group" aria-label="Logout">
                    <i class="fas fa-sign-out-alt fa-2x" aria-hidden="true"></i>
                    <h3>Logout</h3>
                    <p>Sign out of the system.</p>
                    <a href="logout.php" class="secondary button" aria-label="Logout">Logout</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>