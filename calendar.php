<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch attendance data for calendar
$stmt = $pdo->prepare("SELECT date, clock_in, clock_out FROM attendance WHERE user_id = ?");
$stmt->execute([$user_id]);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($attendance as $record) {
    $title = 'Clock In: ' . date('H:i', strtotime($record['clock_in']));
    if ($record['clock_out']) {
        $title .= ', Out: ' . date('H:i', strtotime($record['clock_out']));
    }
    $events[] = [
        'title' => $title,
        'start' => $record['date'],
        'allDay' => true
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Calendar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Attendance Calendar</h2>
        <div id="calendar"></div>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
    <script>
        // Debounce function to prevent multiple rapid clicks
        function debounce(fn, ms) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn.apply(this, args), ms);
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($events); ?>,
                dateClick: debounce(function(info) {
                    var selectedDate = info.dateStr;
                    if (!selectedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        alert('Invalid date selected.');
                        return;
                    }
                    if (confirm('Mark attendance for ' + selectedDate + '?')) {
                        var action = prompt('Enter action (clock_in or clock_out):');
                        if (action === 'clock_in' || action === 'clock_out') {
                            fetch('mark_attendance.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'date=' + encodeURIComponent(selectedDate) + '&action=' + encodeURIComponent(action)
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.success) {
                                    calendar.refetchEvents();
                                    location.reload();
                                }
                            })
                            .catch(error => alert('Error: ' + error));
                        }
                    }
                }, 300) // 300ms debounce
            });
            calendar.render();
        });
    </script>
</body>
</html>