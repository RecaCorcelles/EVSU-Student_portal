<?php
// filepath: c:\xampp\htdocs\student_portal\subjects.php
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

// Log subjects access
logActivity('Subjects Access', $student_id);

// Fetch student data from Supabase with error handling
try {
    $studentData = getStudentEnrollmentData($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Subjects Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load subjects data. Please contact support.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Enrolled Subjects</title>
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

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 2rem;
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
            <li><a href="subjects.php" class="active"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a></li>
            <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1>Enrolled Subjects</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="system-info">
                <i class="fas fa-database"></i> <strong>Live System:</strong> Subject data from EVSU Enrollment Database
            </div>
        <?php endif; ?>

        <!-- Academic Information -->
        <div class="academic-info">
            <div><strong>Academic Year:</strong> <?php echo $currentAcademicYear; ?></div>
            <div><strong>Current Semester:</strong> <?php echo $currentSemester; ?></div>
            <div><strong>Student:</strong> <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?></div>
            <div><strong>Course:</strong> <?php echo htmlspecialchars($studentData['course'] ?? 'N/A'); ?></div>
        </div>

        <?php if ($studentData && !empty($studentData['subjects_enrolled'])): ?>
            <!-- Subject Statistics -->
            <div class="stats-summary">
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($studentData['subjects_enrolled']); ?></span>
                    <span class="stat-label">Total Subjects</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo array_sum(array_column($studentData['subjects_enrolled'], 'units')); ?></span>
                    <span class="stat-label">Total Units</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $studentData['year_level'] ?? 'N/A'; ?></span>
                    <span class="stat-label">Year Level</span>
                </div>
            </div>

            <!-- Subjects Table -->
            <section class="content-section">
                <h2><i class="fas fa-book"></i> Currently Enrolled Subjects</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Units</th>
                            <th>Prerequisites</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentData['subjects_enrolled'] as $subject): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($subject['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($subject['desc']); ?></td>
                                <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                <td><?php echo htmlspecialchars($subject['prerequisites'] ?? 'None'); ?></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

        <?php else: ?>
            <section class="content-section">
                <div class="no-data">
                    <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Enrolled Subjects Found</h3>
                    <p>You are not currently enrolled in any subjects for this semester.</p>
                    <p>Please contact the registrar's office or check your enrollment status.</p>
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
</script>

</body>
</html>