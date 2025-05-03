<?php
session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT date, status FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ?");
$stmt->execute([$user_id, $start, $end]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($records as $record) {
    $events[] = [
        'title' => $record['status'],
        'start' => $record['date'],
        'color' => $record['status'] == 'Present' ? 'green' : 'red'
    ];
}

echo json_encode($events);
?>