<?php
// filepath: c:\xampp\htdocs\student_portal\change_password.php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

// Use enhanced session validation
requireLogin();

$student_id = $_SESSION['student_id'];
$error_message = '';
$success_message = '';

// Log password change access
logActivity('Change Password Access', $student_id);

// Handle password change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security token mismatch. Please try again.";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password and confirmation do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } elseif ($current_password === $new_password) {
            $error_message = "New password must be different from your current password.";
        } else {
            // Verify current password
            if (authenticateStudentCredentials($student_id, $current_password)) {
                // Update password in Supabase
                if (setStudentPortalPassword($student_id, $new_password)) {
                    $success_message = "Password changed successfully!";
                    logActivity('Password Changed', $student_id);
                    
                    // Optionally, you could force re-login here
                    // session_destroy();
                    // header("Location: index.php?password_changed=1");
                    // exit();
                } else {
                    $error_message = "Failed to update password. Please try again.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    }
}

// Fetch student data for display
try {
    $studentData = getStudentEnrollmentData($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Change Password Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load account data. Please contact support.";
    $studentData = null;
    $studentInfo = null;
}

// Use the shared function to get profile photo
// Use the shared function to get profile photo with avatar fallback
$student_full_name = '';
if ($studentInfo) {
    $student_full_name = ($studentInfo['first_name'] ?? '') . ' ' . ($studentInfo['last_name'] ?? '');
    $student_full_name = trim($student_full_name);
}

// Get profile image with avatar fallback  
$profile_photo_path = getStudentProfileImage($student_id, $studentInfo, 40);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Change Password</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dashboard styles */
        body { 
            background-color: var(--background-light); 
        }
        
        .dashboard-header { 
            background-color: var(--primary-color); 
            color: var(--text-color-light); 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
        }
        
        .dashboard-header h1 { 
            font-size: 1.5rem; 
            margin: 0; 
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-right a { 
            color: var(--text-color-light); 
            text-decoration: none; 
            transition: opacity 0.3s ease; 
        }
        
        .header-right a:hover { 
            opacity: 0.8; 
        }
        
        .header-right .profile-pic-small { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            border: 2px solid var(--secondary-color); 
        }
        
        .dashboard-container { 
            display: flex; 
        }
        
        .sidebar { 
            width: 250px; 
            background-color: var(--secondary-color); 
            min-height: calc(100vh - 80px); 
            padding: 1.5rem; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); 
        }
        
        .sidebar h3 { 
            color: var(--primary-color); 
            margin-bottom: 1.5rem; 
            border-bottom: 1px solid var(--border-color); 
            padding-bottom: 0.5rem; 
        }
        
        .sidebar ul { 
            list-style: none; 
            padding: 0;
        }
        
        .sidebar ul li a { 
            display: block; 
            padding: 10px 15px; 
            color: var(--text-color-dark); 
            text-decoration: none; 
            border-radius: 5px; 
            margin-bottom: 0.5rem; 
            transition: background-color 0.3s ease, color 0.3s ease; 
        }
        
        .sidebar ul li a:hover, 
        .sidebar ul li a.active { 
            background-color: var(--primary-color); 
            color: var(--text-color-light); 
        }
        
        .sidebar ul li a i { 
            margin-right: 10px; 
            width: 20px; 
            text-align: center; 
        }
        
        .main-content { 
            flex: 1; 
            padding: 2rem; 
        }
        
        .content-section { 
            background-color: var(--secondary-color); 
            padding: 1.5rem 2rem; 
            border-radius: 8px; 
            margin-bottom: 2rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        
        .content-section:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        
        .content-section h2 { 
            color: var(--primary-color); 
            margin-bottom: 1.5rem; 
            border-bottom: 1px solid var(--border-color); 
            padding-bottom: 0.5rem; 
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--accent-color);
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .message {
            padding: 12px;
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
        
        .system-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #1976d2;
        }
        
        .security-tips {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-top: 1rem;
        }
        
        .security-tips h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>

<header class="dashboard-header">
    <h1><?php echo PORTAL_NAME; ?></h1>
    <div class="header-right">
        <span>Welcome, <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?>!</span>
        <a href="profile.php" title="Profile Settings">
            <img src="<?php echo $profile_photo_path; ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="profile-pic-small">
        </a>
        <a href="profile.php" title="Profile Settings"><i class="fas fa-user-cog"></i> Profile</a>
        <a href="logout.php" title="Logout" onclick="return confirmLogout();">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<div class="dashboard-container">
    <aside class="sidebar">
        <h3>Navigation</h3>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a></li>
            <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1><i class="fas fa-key"></i> Change Password</h1>

        <div class="system-info">
            <i class="fas fa-shield-alt"></i> <strong>Secure:</strong> Password changes are encrypted and synced with the EVSU system
        </div>

        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <section class="content-section">
            <h2><i class="fas fa-lock"></i> Update Your Password</h2>
            
            <form action="change_password.php" method="POST" id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required 
                           placeholder="Enter your current password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Enter your new password (minimum 6 characters)"
                           minlength="6" onkeyup="checkPasswordStrength()">
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your new password"
                           onkeyup="checkPasswordMatch()">
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </form>
            
            <!-- Security Tips -->
            <div class="security-tips">
                <h4><i class="fas fa-lightbulb"></i> Password Security Tips</h4>
                <ul>
                    <li>Use at least 6 characters (longer is better)</li>
                    <li>Include a mix of uppercase and lowercase letters</li>
                    <li>Add numbers and special characters</li>
                    <li>Don't use easily guessable information (birthdate, name, etc.)</li>
                    <li>Don't reuse passwords from other accounts</li>
                    <li>Change your password regularly</li>
                </ul>
            </div>
        </section>

        <!-- Account Information -->
        <?php if ($studentData): ?>
        <section class="content-section">
            <h2><i class="fas fa-user-shield"></i> Account Security Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 5px;">
                    <strong>Student ID:</strong><br>
                    <?php echo htmlspecialchars($student_id); ?>
                </div>
                <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 5px;">
                    <strong>Account Name:</strong><br>
                    <?php echo htmlspecialchars($studentData['name']); ?>
                </div>
                <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 5px;">
                    <strong>Email:</strong><br>
                    <?php echo htmlspecialchars($studentData['email']); ?>
                </div>
                <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 5px;">
                    <strong>Last Login:</strong><br>
                    <?php echo formatDate($_SESSION['login_time'] ?? time(), 'M j, Y g:i A'); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<script src="js/script.js"></script>
<script>
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}

function checkPasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    // Check length
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    
    // Check for numbers
    if (/\d/.test(password)) {
        strength++;
    } else {
        feedback.push('Add numbers');
    }
    
    // Check for lowercase
    if (/[a-z]/.test(password)) {
        strength++;
    } else {
        feedback.push('Add lowercase letters');
    }
    
    // Check for uppercase
    if (/[A-Z]/.test(password)) {
        strength++;
    } else {
        feedback.push('Add uppercase letters');
    }
    
    // Check for special characters
    if (/[^A-Za-z0-9]/.test(password)) {
        strength++;
    } else {
        feedback.push('Add special characters');
    }
    
    // Display strength
    let strengthText = '';
    let strengthClass = '';
    
    if (strength < 3) {
        strengthText = 'Weak';
        strengthClass = 'strength-weak';
    } else if (strength < 5) {
        strengthText = 'Medium';
        strengthClass = 'strength-medium';
    } else {
        strengthText = 'Strong';
        strengthClass = 'strength-strong';
    }
    
    strengthDiv.innerHTML = `<span class="${strengthClass}">Password strength: ${strengthText}</span>`;
    if (feedback.length > 0 && strength < 5) {
        strengthDiv.innerHTML += `<br><small>Suggestions: ${feedback.join(', ')}</small>`;
    }
}

function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirmPassword.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<span class="strength-strong">Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="strength-weak">Passwords do not match</span>';
    }
}

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    return confirm('Are you sure you want to change your password?');
});
</script>

</body>
</html>