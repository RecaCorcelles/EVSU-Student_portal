<?php
// filepath: c:\xampp\htdocs\student_portal\grades.php
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

// Log grades access
logActivity('Grades Access', $student_id);

// Fetch student data from Supabase with error handling
try {
    $studentData = getStudentEnrollmentData($student_id);
    $studentGrades = getStudentGrades($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Grades Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load grade data. Please contact support.";
    $studentData = null;
    $studentGrades = [];
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

// Calculate GPA and statistics
$totalGradePoints = 0;
$totalUnits = 0;
$completedSubjects = 0;
$failedSubjects = 0;

if (!empty($studentGrades)) {
    foreach ($studentGrades as $grade) {
        $units = $grade['units'] ?? 3;
        $finalGrade = $grade['final_grade'] ?? 0;
        
        if (is_numeric($finalGrade)) {
            $gradePoint = convertGradeToPoint($finalGrade);
            $totalGradePoints += ($gradePoint * $units);
            $totalUnits += $units;
            $completedSubjects++;
            
            if ($finalGrade < 75) {
                $failedSubjects++;
            }
        }
    }
}

$gpa = $totalUnits > 0 ? round($totalGradePoints / $totalUnits, 2) : 0;

// Get academic info
$currentAcademicYear = getCurrentAcademicYear();
$currentSemester = getCurrentSemester();

// Function to convert numeric grade to GPA point
function convertGradeToPoint($grade) {
    if ($grade >= 95) return 4.0;
    if ($grade >= 90) return 3.5;
    if ($grade >= 85) return 3.0;
    if ($grade >= 80) return 2.5;
    if ($grade >= 75) return 2.0;
    return 0.0;
}

// Function to get grade remarks
function getGradeRemarks($grade) {
    if (!is_numeric($grade)) return 'N/A';
    if ($grade >= 95) return 'Excellent';
    if ($grade >= 90) return 'Very Good';
    if ($grade >= 85) return 'Good';
    if ($grade >= 80) return 'Satisfactory';
    if ($grade >= 75) return 'Fair';
    return 'Failed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Grades</title>
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

        .stats-grid {
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

        .grade-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
        }

        .semester-header {
            font-weight: bold;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .gpa-display {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--text-color-light);
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .gpa-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .gpa-scale {
            font-size: 0.9rem;
            opacity: 0.9;
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

        .grade-excellent { color: #28a745; font-weight: bold; }
        .grade-very-good { color: #20c997; font-weight: bold; }
        .grade-good { color: #17a2b8; font-weight: bold; }
        .grade-satisfactory { color: #ffc107; font-weight: bold; }
        .grade-fair { color: #fd7e14; font-weight: bold; }
        .grade-failed { color: #dc3545; font-weight: bold; }

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
            <li><a href="grades.php" class="active"><i class="fas fa-chart-line"></i> Grades</a></li>
            <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1>Academic Records</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="system-info">
                <i class="fas fa-database"></i> <strong>Live System:</strong> Grade data from EVSU Academic Records
            </div>
        <?php endif; ?>

        <!-- Academic Information -->
        <div class="academic-info">
            <div><strong>Academic Year:</strong> <?php echo $currentAcademicYear; ?></div>
            <div><strong>Current Semester:</strong> <?php echo $currentSemester; ?></div>
            <div><strong>Student:</strong> <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?></div>
            <div><strong>Course:</strong> <?php echo htmlspecialchars($studentData['course'] ?? 'N/A'); ?></div>
        </div>

        <?php if (!empty($studentGrades)): ?>
            <!-- Academic Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($gpa, 2); ?></span>
                    <span class="stat-label">Overall GPA</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $completedSubjects; ?></span>
                    <span class="stat-label">Completed Subjects</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $totalUnits; ?></span>
                    <span class="stat-label">Total Units Earned</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $failedSubjects; ?></span>
                    <span class="stat-label">Failed Subjects</span>
                </div>
            </div>

            <!-- Grades by Semester -->
            <?php 
            $gradesBySemester = [];
            foreach ($studentGrades as $grade) {
                $semesterKey = ($grade['year'] ?? 'Unknown Year') . ' - ' . ($grade['semester'] ?? 'Unknown Semester');
                $gradesBySemester[$semesterKey][] = $grade;
            }
            ?>

            <?php foreach ($gradesBySemester as $semester => $grades): ?>
                <section class="content-section">
                    <div class="semester-header">
                        <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($semester); ?>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Units</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <?php 
                                $finalGrade = $grade['final_grade'] ?? 'N/A';
                                $gradeClass = '';
                                if (is_numeric($finalGrade)) {
                                    if ($finalGrade >= 95) $gradeClass = 'grade-excellent';
                                    elseif ($finalGrade >= 90) $gradeClass = 'grade-very-good';
                                    elseif ($finalGrade >= 85) $gradeClass = 'grade-good';
                                    elseif ($finalGrade >= 80) $gradeClass = 'grade-satisfactory';
                                    elseif ($finalGrade >= 75) $gradeClass = 'grade-fair';
                                    else $gradeClass = 'grade-failed';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['subjects']['subject_code'] ?? $grade['subject_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subjects']['subject_name'] ?? $grade['subject_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['units'] ?? 'N/A'); ?></td>
                                    <td class="<?php echo $gradeClass; ?>"><?php echo htmlspecialchars($finalGrade); ?></td>
                                    <td><?php echo htmlspecialchars($grade['remarks'] ?? getGradeRemarks($finalGrade)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>

            <!-- GPA Scale Information -->
            <section class="content-section">
                <h2><i class="fas fa-info-circle"></i> Grading Scale</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="grade-excellent">95-100: Excellent (4.0)</div>
                    <div class="grade-very-good">90-94: Very Good (3.5)</div>
                    <div class="grade-good">85-89: Good (3.0)</div>
                    <div class="grade-satisfactory">80-84: Satisfactory (2.5)</div>
                    <div class="grade-fair">75-79: Fair (2.0)</div>
                    <div class="grade-failed">Below 75: Failed (0.0)</div>
                </div>
            </section>

        <?php else: ?>
            <section class="content-section">
                <div class="no-data">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Grade Records Found</h3>
                    <p>Grades may not be available yet or there might be a system issue.</p>
                    <p>Please contact the registrar's office if you believe this is an error.</p>
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