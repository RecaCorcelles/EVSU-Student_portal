<?php
// filepath: c:\xampp\htdocs\student_portal\profile.php
session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

// Use enhanced session validation
requireLogin();

$student_id = $_SESSION['student_id'];
$error_message = '';
$success_message = '';

// Log profile access
logActivity('Profile Access', $student_id);

// Fetch latest student data from Supabase first
try {
    $studentData = getStudentEnrollmentData($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Profile Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load profile data. Please contact support.";
    $studentData = null;
    $studentInfo = null;
}

// Use the shared function to get profile photo with avatar fallback
$student_full_name = '';
if ($studentInfo) {
    $student_full_name = ($studentInfo['first_name'] ?? '') . ' ' . ($studentInfo['last_name'] ?? '');
    $student_full_name = trim($student_full_name);
}

// Get profile image with avatar fallback  
$profile_photo_path = getStudentProfileImage($student_id, $studentInfo, 40);

// Get additional information
$currentAcademicYear = getCurrentAcademicYear();
$currentSemester = getCurrentSemester();

// --- Handle Profile Photo Upload --- //
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_photo'])) {
    // Verify CSRF token if you're using it
    if (isset($_POST['csrf_token']) && !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security token mismatch. Please try again.";
    } elseif (isValidImageUpload($_FILES['profile_photo'])) {
        $uploadDir = 'images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Use student ID as filename to make it consistent
        $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $newFilename = $student_id . '.' . strtolower($extension);
        $destination = $uploadDir . $newFilename;

        // Delete old photo if exists (any extension)
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($extensions as $ext) {
            $oldFile = $uploadDir . $student_id . '.' . $ext;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
            $success_message = "Profile photo updated successfully!";
            logActivity('Profile Photo Updated', $student_id);
        } else {
            $error_message = "Error uploading file. Please check permissions.";
        }
    } else {
        $error_message = "Invalid file. Please upload a JPG, PNG, or GIF image under 2MB.";
    }
}
// --- End Handle Profile Photo Upload --- //
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Edit Profile</title>
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
        
        /* Profile specific styles */
        .profile-info-display p { 
            margin-bottom: 0.8rem; 
            font-size: 1.1rem; 
        }
        
        .profile-info-display strong { 
            color: var(--primary-color); 
            margin-right: 10px; 
            min-width: 150px; 
            display: inline-block;
        }
        
        .profile-photo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-photo_current {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 1rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
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
        
        .system-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #1976d2;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .info-card {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }
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
                <li><a href="grades.php" class="active"><i class="fas fa-chart-line"></i> Grades</a></li> <!-- active class changes per page -->
                <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Edit Profile</h1>

            <div class="system-info">
                <i class="fas fa-database"></i> <strong>Live System:</strong> Profile data synced with EVSU Enrollment Database
            </div>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <!-- Profile Photo Upload Section -->
            <section class="content-section profile-photo-section">
                <h2><i class="fas fa-camera"></i> Update Profile Photo</h2>
                <?php 
                // Get larger profile image for the main display
                $large_profile_photo = getStudentProfileImage($student_id, $studentInfo, 150);
                ?>
                <img src="<?php echo $large_profile_photo; ?>?t=<?php echo time(); ?>" alt="Current Profile Picture" class="profile-photo_current">

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label for="profile_photo">Choose a new photo (JPG, PNG, GIF - max <?php echo formatFileSize(MAX_UPLOAD_SIZE); ?>):</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.gif" required>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload Photo</button>
                </form>
            </section>

            <!-- Account Information Display (from Supabase) -->
            <section class="content-section profile-info-display">
                <h2><i class="fas fa-user-circle"></i> Account Information</h2>
                <?php if ($studentData && $studentInfo): ?>
                    <div class="info-grid">
                        <div class="info-card">
                            <strong>Student ID:</strong><br>
                            <?php echo htmlspecialchars($student_id); ?>
                        </div>
                        <div class="info-card">
                            <strong>Full Name:</strong><br>
                            <?php echo htmlspecialchars($studentData['name']); ?>
                        </div>
                        <div class="info-card">
                            <strong>Course:</strong><br>
                            <?php echo htmlspecialchars($studentData['course']); ?>
                        </div>
                        <div class="info-card">
                            <strong>Year Level:</strong><br>
                            <?php echo htmlspecialchars($studentData['year_level']); ?>
                        </div>
                        <div class="info-card">
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($studentData['email']); ?>
                        </div>
                        <div class="info-card">
                            <strong>Enrollment Status:</strong><br>
                            <span class="badge badge-<?php echo $studentData['enrollment_status'] ?? 'pending'; ?>">
                                <?php echo ucfirst($studentData['enrollment_status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        <div class="info-card">
                            <strong>Academic Year:</strong><br>
                            <?php echo $currentAcademicYear; ?>
                        </div>
                        <div class="info-card">
                            <strong>Current Semester:</strong><br>
                            <?php echo $currentSemester; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background-color: #f0f8ff; border-radius: 5px; border-left: 4px solid #4169e1;">
                        <p><small><i class="fas fa-info-circle"></i> <strong>Note:</strong> Personal information (Name, Course, Year Level) is managed by the EVSU Enrollment System. Contact the registrar's office for changes.</small></p>
                    </div>
                <?php else: ?>
                    <p class="no-data">Could not retrieve account details from the enrollment system. Please contact support.</p>
                <?php endif; ?>
            </section>

            <!-- Security Section -->
            <section class="content-section">
                <h2><i class="fas fa-shield-alt"></i> Account Security</h2>
                <p>Manage your account security and password settings.</p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="change_password.php" class="btn"><i class="fas fa-key"></i> Change Password</a>
                    <a href="logout.php" class="btn" style="background-color: #dc3545;" onclick="return confirmLogout();"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <p><strong>Last Login:</strong> <?php echo formatDate($_SESSION['login_time'] ?? time(), 'F j, Y g:i A'); ?></p>
                    <p><strong>Session Duration:</strong> <?php echo floor((time() - ($_SESSION['login_time'] ?? time())) / 60); ?> minutes</p>
                </div>
            </section>

        </main>
    </div>

    <script src="js/script.js"></script>
    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
    </script>
</body>
</html>