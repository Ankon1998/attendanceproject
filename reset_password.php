<?php
session_start();
require_once 'includes/db.php';

$message = '';
$step = $_GET['step'] ?? 'verify';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 'verify') {
        $username = $_POST['username'] ?? '';
        $answer = $_POST['answer'] ?? '';

        if (empty($username) || empty($answer)) {
            $message = "Please fill in all fields.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, security_question, security_answer FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && strtolower(trim($user['security_answer'])) === strtolower(trim($answer))) {
                    $_SESSION['reset_user_id'] = $user['id'];
                    header("Location: reset_password.php?step=reset");
                    exit;
                } else {
                    $message = "Invalid username or security answer.";
                }
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
                error_log("Verify Error: " . $e->getMessage());
            }
        }
    } elseif ($step == 'reset') {
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: reset_password.php?step=verify");
            exit;
        }

        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirm_password)) {
            $message = "Please fill in all fields.";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $message = "Password must be at least 8 characters.";
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);

                unset($_SESSION['reset_user_id']);
                $message = "Password reset successfully. <a href='login.php'>Login</a>.";
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
                error_log("Reset Error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') || strpos($message, 'Invalid') ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>
        <?php if ($step == 'verify'): ?>
            <form method="POST" action="reset_password.php?step=verify">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <?php if (isset($username) && $user): ?>
                    <label for="answer"><?php echo htmlspecialchars($user['security_question']); ?>:</label>
                    <input type="text" id="answer" name="answer" required>
                <?php else: ?>
                    <label for="answer">Security Answer:</label>
                    <input type="text" id="answer" name="answer" required>
                <?php endif; ?>
                <button type="submit" class="primary">Verify</button>
            </form>
        <?php elseif ($step == 'reset'): ?>
            <form method="POST" action="reset_password.php?step=reset">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="submit" class="primary">Reset Password</button>
            </form>
        <?php endif; ?>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>