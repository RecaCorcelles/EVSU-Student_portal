<?php
// filepath: c:\xampp\htdocs\student_portal\register.php
session_start();
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $student_id = sanitize_input($_POST['student_id']);
    $birthdate = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['birthdate'])));

    if (empty($student_id) || empty($birthdate)) {
        $error_message = "All fields are required.";
    } else {
        // Check if student exists in Supabase enrollment system
        if (doesStudentIdExistInSystem($student_id)) {
            // Get student info to check if already has portal access (password set)
            global $supabaseAPI;
            $studentInfo = $supabaseAPI->getStudentInfo($student_id);
            
            if ($studentInfo && !empty($studentInfo['password'])) {
                $error_message = "This Student ID already has portal access. Please use the login page.";
            } else {
                // Verify birthdate matches (if available in their system)
                if (isset($studentInfo['birthdate'])) {
                    $storedBirthdate = date('Y-m-d', strtotime($studentInfo['birthdate']));
                    if ($storedBirthdate !== $birthdate) {
                        $error_message = "Birthdate does not match our records.";
                    } else {
                        $_SESSION['temp_student_id'] = $student_id;
                        $_SESSION['temp_birthdate'] = $birthdate;
                        header("Location: password.php");
                        exit();
                    }
                } else {
                    // If no birthdate in system, proceed anyway
                    $_SESSION['temp_student_id'] = $student_id;
                    $_SESSION['temp_birthdate'] = $birthdate;
                    header("Location: password.php");
                    exit();
                }
            }
        } else {
            $error_message = "Invalid Student ID. This ID is not found in the EVSU Enrollment System.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="register-page">

    <div class="register-container">
        <div class="register-logo">
            <img src="evsu_ormoc.jpg" alt="EVSU Logo">
        </div>

        <div class="register-form">
            <h2 style="text-align: center; color: var(--primary-color);">Register Portal Account</h2>
            <p style="text-align: center; margin-bottom: 1.5rem; color: #555;">
                Create your login credentials. Your Student ID must be registered in the EVSU Enrollment System.
            </p>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="student-id">Student ID</label>
                    <input type="text" id="student-id" name="student_id" required 
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                           placeholder="e.g., 2023-00001">
                </div>

                <div class="form-group">
                    <label for="birthdate">Date of Birth</label>
                    <input type="text" id="birthdate" name="birthdate" required
                        value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>"
                        placeholder="Select your birth date">
                </div>

                <button type="submit" name="register" class="btn" style="width: 100%; padding: 12px;">Verify & Continue</button>
            </form>

            <div class="login-links" style="text-align: center; margin-top: 1.5rem;">
                <span>Already registered? </span>
                <a href="index.php" style="color: var(--primary-color); text-decoration: none;">Login Here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#birthdate", {
            dateFormat: "Y-m-d",
            allowInput: true,
            position: "above"
        });
    </script>
    
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
        
        /* Flatpickr calendar size adjustments */
        .flatpickr-calendar {
            font-size: 11px !important;
            width: auto !important;
            padding: 6px !important;
            max-width: 320px !important;
            box-sizing: border-box;
        }
        
        .flatpickr-day {
            padding: 4px 5px !important;
            font-size: 11px !important;
        }
        
        .flatpickr-time,
        .flatpickr-footer {
            display: none !important;
        }
    </style>
</body>
</html>
