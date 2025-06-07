<?php
session_start();
require_once 'includes/db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['retrieve'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error_message = "Please enter your registered email.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $retrieved_password = $row['password'];
            $success_message = "Your password is: " . htmlspecialchars($retrieved_password);
        } else {
            $error_message = "No account found with that email.";
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU Student Portal - Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="register-page">
    <div class="forgot-container">
        <div class="forgot-logo">
            <img src="evsu_ormoc.jpg" alt="EVSU Logo">
        </div>

        <div class="forgot-form">
            <h2 style="text-align: center; color: #ffc100; margin-bottom: 4.5rem;">Forgot Password</h2>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form action="forgot.php" method="POST">
                <div class="form-group">
                    <label for="email" style="color: var(--secondary-color);">Enter your registered email</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <button type="submit" name="retrieve" class="btn" style="width: 100%; padding: 12px;">Retrieve Password</button>
            </form>

            <div class="login-links" style="text-align: center; margin-top: 5rem;">
                <a href="index.php" style="color: var(--secondary-color); text-decoration: none;">Back to Login</a>
            </div>
        </div>
    </div>

    <style>
        .message {
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        .message.error {
            background-color: rgb(255, 204, 204);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: rgb(204, 255, 204);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</body>
</html>
