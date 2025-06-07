<?php
session_start(); // Start session at the beginning

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

$error_message = '';
$success_message = '';

// Redirect if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $student_id = sanitize_input($_POST['student_id']);
    $password = $_POST['password'];

    if (empty($student_id) || empty($password)) {
        $error_message = "Both Student ID and Password are required.";
    } else {
        // Authenticate against Supabase
        if (authenticateStudentCredentials($student_id, $password)) {
            // Set session
            $_SESSION['student_id'] = $student_id;
            $_SESSION['login_time'] = time();
            
            // Update last login in Supabase (optional)
            global $supabaseAPI;
            $supabaseAPI->updateStudentProfile($student_id, ['updated_at' => date('Y-m-d H:i:s')]);
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid Student ID or Password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img src="evsu_ormoc.jpg" alt="EVSU Logo">
        </div>
        <div class="login-form">
            <h2>Student Portal Login</h2>

            <div class="message info" style="background-color: #e3f2fd; color: #1976d2; border: 1px solid #2196f3;">
                <strong><i class="fas fa-database"></i> Live System:</strong> Connected to EVSU Enrollment Database
            </div>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="message success">You have been logged out.</div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="student-id">Student ID</label>
                    <input type="text" id="student-id" name="student_id" required 
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                           placeholder="Enter your Student ID">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                </div>
                <button type="submit" name="login" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            
            <div class="login-info" style="margin-top: 2rem; text-align: center; color: #666; font-size: 0.9rem; line-height: 1.6;">
                <p><i class="fas fa-info-circle"></i> <strong>Student Portal Access</strong></p>
                <p>Use your Student ID and password from the EVSU Enrollment System to log in.</p>
                <p>For account issues, please contact the registrar's office.</p>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <style>
        .message {
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.info {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #2196f3;
        }
        .login-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
        }
    </style>
</body>
</html>