<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

$error_message = '';
$success_message = '';

// Check if coming from registration
if (!isset($_SESSION['temp_student_id'])) {
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $student_id = $_SESSION['temp_student_id'];
        
        // Set password in Supabase
        if (setStudentPortalPassword($student_id, $password)) {
            // Clear temporary session data
            unset($_SESSION['temp_student_id']);
            unset($_SESSION['temp_birthdate']);
            
            $success_message = "Portal account created successfully! You can now login.";
            
            // Redirect to login after 2 seconds
            header("Refresh: 2; url=index.php");
        } else {
            $error_message = "Failed to create portal account. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Set Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="register-page">

    <div class="register-container">
        <div class="register-logo">
            <img src="evsu_ormoc.jpg" alt="EVSU Logo">
        </div>

        <div class="register-form">
            <h2 style="text-align: center; color: var(--primary-color);">Set Your Password</h2>
            <p style="text-align: center; margin-bottom: 1.5rem; color: #555;">
                Create a secure password for your portal account.
            </p>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (empty($success_message)): ?>
            <form action="password.php" method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" placeholder="At least 8 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="8" placeholder="Re-enter your password">
                </div>

                <button type="submit" name="set_password" class="btn" style="width: 100%; padding: 12px;">Create Portal Account</button>
            </form>
            <?php endif; ?>

            <div class="login-links" style="text-align: center; margin-top: 1.5rem;">
                <a href="register.php" style="color: var(--primary-color); text-decoration: none;">← Back to Registration</a>
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
