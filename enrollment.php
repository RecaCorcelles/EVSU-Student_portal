<?php
// filepath: c:\xampp\htdocs\student_portal\enrollment.php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

$student_id = $_SESSION['student_id'];

// Use enhanced session validation
requireLogin();

// Log enrollment access
logActivity('Enrollment Access', $student_id);

// Handle enrollment actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_subject':
                if (isset($_POST['subject_code']) && !empty($_POST['subject_code'])) {
                    // This would integrate with your enrollment system
                    $success_message = "Subject enrollment request submitted. Please wait for approval.";
                    logActivity('Subject Add Request: ' . $_POST['subject_code'], $student_id);
                } else {
                    $error_message = "Please select a subject to add.";
                }
                break;
                
            case 'drop_subject':
                if (isset($_POST['subject_code']) && !empty($_POST['subject_code'])) {
                    // This would integrate with your enrollment system
                    $success_message = "Subject drop request submitted. Please wait for approval.";
                    logActivity('Subject Drop Request: ' . $_POST['subject_code'], $student_id);
                } else {
                    $error_message = "Please select a subject to drop.";
                }
                break;
        }
    }
}

// Fetch student data from Supabase with error handling
try {
    $studentData = getStudentEnrollmentData($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Enrollment Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load enrollment data. Please contact support.";
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

// Get current academic info
$currentAcademicYear = getCurrentAcademicYear();
$currentSemester = getCurrentSemester();
$enrollmentOpen = isEnrollmentOpen();

// Mock available subjects for enrollment (would come from your system)
$availableSubjects = [
    ['code' => 'CS101', 'name' => 'Introduction to Computer Science', 'units' => 3, 'schedule' => 'MWF 8:00-9:00 AM'],
    ['code' => 'MATH101', 'name' => 'College Algebra', 'units' => 3, 'schedule' => 'TTH 10:00-11:30 AM'],
    ['code' => 'ENG101', 'name' => 'English Composition', 'units' => 3, 'schedule' => 'MWF 9:00-10:00 AM'],
    ['code' => 'PHYS101', 'name' => 'General Physics', 'units' => 4, 'schedule' => 'TTH 1:00-3:00 PM'],
    ['code' => 'HIST101', 'name' => 'Philippine History', 'units' => 3, 'schedule' => 'MWF 2:00-3:00 PM'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Enrollment</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Inherit dashboard styles */
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--background-light);
            color: var(--primary-color);
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #777;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #ddd;
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

        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #721c24;
        }

        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #155724;
        }

        .academic-info {
            background-color: #f0f8ff;
            border: 1px solid #4169e1;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .enrollment-status {
            text-align: center;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            font-weight: bold;
        }

        .enrollment-open {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .enrollment-closed {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-active {
            background-color: #4caf50;
            color: white;
        }

        .badge-pending {
            background-color: #ff9800;
            color: white;
        }

        .badge-available {
            background-color: #2196f3;
            color: white;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: var(--accent-color);
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-small {
            padding: 4px 8px;
            font-size: 0.8rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }

        .enrollment-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--text-color-light);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
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
            <li><a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a></li>
            <li><a href="enrollment.php" class="active"><i class="fas fa-clipboard-list"></i> Enrollment</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1>Enrollment Management</h1>

        <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message) && !empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="system-info">
            <i class="fas fa-database"></i> <strong>Live System:</strong> Enrollment data from EVSU Academic System
        </div>

        <!-- Academic Information -->
        <div class="academic-info">
            <div><strong>Academic Year:</strong> <?php echo $currentAcademicYear; ?></div>
            <div><strong>Current Semester:</strong> <?php echo $currentSemester; ?></div>
            <div><strong>Student:</strong> <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?></div>
            <div><strong>Course:</strong> <?php echo htmlspecialchars($studentData['course'] ?? 'N/A'); ?></div>
        </div>

        <!-- Enrollment Status -->
        <div class="enrollment-status <?php echo $enrollmentOpen ? 'enrollment-open' : 'enrollment-closed'; ?>">
            <i class="fas fa-<?php echo $enrollmentOpen ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php if ($enrollmentOpen): ?>
                Enrollment is currently OPEN for <?php echo $currentSemester . ' ' . $currentAcademicYear; ?>
            <?php else: ?>
                Enrollment is currently CLOSED. Please contact the registrar's office for assistance.
            <?php endif; ?>
        </div>

        <?php if ($studentData): ?>
        <!-- Enrollment Statistics -->
        <div class="stats-summary">
            <div class="stat-card">
                <span class="stat-number"><?php echo count($studentData['subjects_enrolled'] ?? []); ?></span>
                <span class="stat-label">Enrolled Subjects</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo array_sum(array_column($studentData['subjects_enrolled'] ?? [], 'units')); ?></span>
                <span class="stat-label">Total Units</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $studentData['year_level'] ?? 'N/A'; ?></span>
                <span class="stat-label">Year Level</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo ucfirst($studentData['enrollment_status'] ?? 'pending'); ?></span>
                <span class="stat-label">Status</span>
            </div>
        </div>

        <?php if ($enrollmentOpen): ?>
        <!-- Enrollment Actions -->
        <div class="enrollment-actions">
            <!-- Add Subject -->
            <section class="content-section">
                <h2><i class="fas fa-plus-circle"></i> Add Subject</h2>
                <form method="POST" action="enrollment.php">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="form-group">
                        <label for="add_subject">Select Subject to Add:</label>
                        <select name="subject_code" id="add_subject" required>
                            <option value="">-- Select a Subject --</option>
                            <?php foreach ($availableSubjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['code']); ?>">
                                    <?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['name'] . ' (' . $subject['units'] . ' units)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-plus"></i> Request Add Subject</button>
                </form>
            </section>

            <!-- Drop Subject -->
            <section class="content-section">
                <h2><i class="fas fa-minus-circle"></i> Drop Subject</h2>
                <form method="POST" action="enrollment.php">
                    <input type="hidden" name="action" value="drop_subject">
                    <div class="form-group">
                        <label for="drop_subject">Select Subject to Drop:</label>
                        <select name="subject_code" id="drop_subject" required>
                            <option value="">-- Select a Subject --</option>
                            <?php if (!empty($studentData['subjects_enrolled'])): ?>
                                <?php foreach ($studentData['subjects_enrolled'] as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['code']); ?>">
                                        <?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['desc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-minus"></i> Request Drop Subject</button>
                </form>
            </section>
        </div>
        <?php endif; ?>

        <!-- Current Enrolled Subjects -->
        <section class="content-section">
            <h2><i class="fas fa-list-check"></i> Currently Enrolled Subjects</h2>
            <?php if (!empty($studentData['subjects_enrolled'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Units</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <?php if ($enrollmentOpen): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentData['subjects_enrolled'] as $subject): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($subject['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($subject['desc']); ?></td>
                                <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                <td><?php echo htmlspecialchars($subject['schedule'] ?? 'TBA'); ?></td>
                                <td><span class="badge badge-active">Enrolled</span></td>
                                <?php if ($enrollmentOpen): ?>
                                <td>
                                    <form method="POST" action="enrollment.php" style="display: inline;">
                                        <input type="hidden" name="action" value="drop_subject">
                                        <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject['code']); ?>">
                                        <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to drop this subject?');">
                                            <i class="fas fa-times"></i> Drop
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Enrolled Subjects</h3>
                    <p>You are not currently enrolled in any subjects for this semester.</p>
                    <?php if ($enrollmentOpen): ?>
                        <p>Use the enrollment form above to add subjects.</p>
                    <?php else: ?>
                        <p>Enrollment is currently closed. Please contact the registrar's office.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Available Subjects -->
        <section class="content-section">
            <h2><i class="fas fa-book-open"></i> Available Subjects</h2>
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <?php if ($enrollmentOpen): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableSubjects as $subject): ?>
                        <?php 
                        $isEnrolled = false;
                        if (!empty($studentData['subjects_enrolled'])) {
                            foreach ($studentData['subjects_enrolled'] as $enrolled) {
                                if ($enrolled['code'] === $subject['code']) {
                                    $isEnrolled = true;
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($subject['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                            <td><?php echo htmlspecialchars($subject['units']); ?></td>
                            <td><?php echo htmlspecialchars($subject['schedule']); ?></td>
                            <td>
                                <?php if ($isEnrolled): ?>
                                    <span class="badge badge-active">Enrolled</span>
                                <?php else: ?>
                                    <span class="badge badge-available">Available</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($enrollmentOpen): ?>
                            <td>
                                <?php if (!$isEnrolled): ?>
                                    <form method="POST" action="enrollment.php" style="display: inline;">
                                        <input type="hidden" name="action" value="add_subject">
                                        <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($subject['code']); ?>">
                                        <button type="submit" class="btn btn-small">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-active">Enrolled</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php else: ?>
            <div class="content-section">
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Enrollment Data Unavailable</h3>
                    <p>Could not retrieve enrollment information from the system.</p>
                    <p>Please contact the registrar's office if you believe this is an error.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Important Notes -->
        <section class="content-section">
            <h2><i class="fas fa-info-circle"></i> Important Notes</h2>
            <ul style="line-height: 1.6;">
                <li><strong>Enrollment Period:</strong> Make sure to complete your enrollment within the specified dates.</li>
                <li><strong>Subject Prerequisites:</strong> Ensure you have completed all prerequisite subjects before enrolling.</li>
                <li><strong>Unit Limits:</strong> Check with your academic advisor regarding minimum and maximum unit loads.</li>
                <li><strong>Add/Drop Period:</strong> Changes to enrollment are typically allowed during the first week of classes.</li>
                <li><strong>Payment:</strong> Complete your tuition payment to finalize your enrollment.</li>
                <li><strong>Support:</strong> Contact the registrar's office for any enrollment-related concerns.</li>
            </ul>
        </section>

    </main>
</div>

<script src="js/script.js"></script>
<script>
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}

// Auto-refresh every 5 minutes during enrollment period
<?php if ($enrollmentOpen): ?>
setTimeout(function() {
    location.reload();
}, 300000); // 5 minutes
<?php endif; ?>
</script>

</body>
</html>