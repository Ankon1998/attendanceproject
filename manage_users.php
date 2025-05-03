<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$edit_user_id = $_GET['edit_id'] ?? null;

// Handle form submission (add/edit user)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = $_POST['security_answer'] ?? '';

    if (empty($username) || empty($email) || empty($security_question) || empty($security_answer) || (empty($password) && !$edit_user_id)) {
        $message = "Please fill in all required fields.";
    } elseif (!in_array($role, ['user', 'admin'])) {
        $message = "Invalid role selected.";
    } else {
        try {
            if ($edit_user_id) {
                // Update existing user
                $sql = "UPDATE users SET username = ?, email = ?, role = ?, security_question = ?, security_answer = ?";
                $params = [$username, $email, $role, $security_question, $security_answer];
                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id = ?";
                $params[] = $edit_user_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = "User updated successfully.";
            } else {
                // Add new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role, $security_question, $security_answer]);
                $message = "User added successfully.";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            error_log("User Manage Error: " . $e->getMessage());
        }
    }
}

// Fetch user for editing
$edit_user = null;
if ($edit_user_id) {
    $stmt = $pdo->prepare("SELECT id, username, email, role, security_question, security_answer FROM users WHERE id = ?");
    $stmt->execute([$edit_user_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, role, security_question FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>
        <h3><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
        <form method="POST" action="manage_users.php<?php echo $edit_user ? '?edit_id=' . $edit_user['id'] : ''; ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
            <label for="password">Password: <?php echo $edit_user ? '(Leave blank to keep unchanged)' : ''; ?></label>
            <input type="password" id="password" name="password">
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="user" <?php echo ($edit_user && $edit_user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
            <label for="security_question">Security Question:</label>
            <input type="text" id="security_question" name="security_question" value="<?php echo htmlspecialchars($edit_user['security_question'] ?? ''); ?>" required>
            <label for="security_answer">Security Answer:</label>
            <input type="text" id="security_answer" name="security_answer" value="<?php echo htmlspecialchars($edit_user['security_answer'] ?? ''); ?>" required>
            <button type="submit" class="primary"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
        </form>
        <h3>Existing Users</h3>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Security Question</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['security_question'] ?? 'N/A'); ?></td>
                        <td><a href="manage_users.php?edit_id=<?php echo $user['id']; ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>