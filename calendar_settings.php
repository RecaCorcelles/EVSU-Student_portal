<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';
require_once 'includes/google_calendar.php';

$student_id = $_SESSION['student_id'];

// Use enhanced session validation
requireLogin();

// Log calendar settings access
logActivity('Calendar Settings Access', $student_id);

$success_message = '';
$error_message = '';

// Handle messages from URL parameters
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'authorized':
            $success_message = "Google Calendar has been successfully authorized! You can now sync your class schedule.";
            break;
        case 'synced':
            $success_message = "Your class schedule has been successfully synced to Google Calendar!";
            break;
        case 'revoked':
            $success_message = "Google Calendar authorization has been revoked.";
            break;
    }
}

if (isset($_GET['error'])) {
    $error_message = "Error: " . htmlspecialchars($_GET['error']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $googleCalendar = new GoogleCalendarIntegration($student_id);
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'sync_schedule':
                    if (!$googleCalendar->isAuthorized()) {
                        throw new Exception("Please authorize Google Calendar access first.");
                    }
                    
                    $result = $googleCalendar->syncScheduleToCalendar();
                    
                    if ($result['success']) {
                        $success_message = "Schedule synced successfully! Created {$result['events_created']} events.";
                        if (!empty($result['errors'])) {
                            $success_message .= " Some errors occurred: " . implode(', ', $result['errors']);
                        }
                    } else {
                        $error_message = $result['message'] ?? 'Failed to sync schedule.';
                    }
                    break;
                    
                case 'revoke_access':
                    $googleCalendar->revokeAuthorization();
                    header("Location: calendar_settings.php?success=revoked");
                    exit();
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Initialize Google Calendar to check authorization status
try {
    $googleCalendar = new GoogleCalendarIntegration($student_id);
    $isAuthorized = $googleCalendar->isAuthorized();
    $authUrl = $isAuthorized ? '' : $googleCalendar->getAuthUrl();
} catch (Exception $e) {
    $error_message = "Google Calendar integration not available: " . $e->getMessage();
    $isAuthorized = false;
    $authUrl = '';
}

// Fetch student data
try {
    $studentData = getStudentEnrollmentData($student_id);
    
    global $supabaseAPI;
    $studentInfo = $supabaseAPI->getStudentInfo($student_id);
    
    if (!$studentInfo) {
        throw new Exception("Could not retrieve student information");
    }
} catch (Exception $e) {
    error_log("Calendar Settings Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load student data. Please contact support.";
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
    <title><?php echo PORTAL_NAME; ?> - Calendar Settings</title>
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

        .authorization-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .status-authorized {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .status-not-authorized {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .google-btn {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .google-btn:hover {
            background-color: #3367d6;
            color: white;
        }

        .google-btn i {
            font-size: 18px;
        }

        .calendar-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .action-card.sync { color: #28a745; }
        .action-card.revoke { color: #dc3545; }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .schedule-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .schedule-preview table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-preview th,
        .schedule-preview td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .schedule-preview th {
            background-color: #e9ecef;
            font-weight: bold;
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
            <li><a href="calendar_settings.php" class="active"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h1><i class="fab fa-google"></i> Google Calendar Integration</h1>

        <?php if ($success_message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Authorization Status -->
        <section class="content-section">
            <h2><i class="fas fa-link"></i> Connection Status</h2>
            
            <div class="authorization-status <?php echo $isAuthorized ? 'status-authorized' : 'status-not-authorized'; ?>">
                <i class="fas <?php echo $isAuthorized ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <div>
                    <strong><?php echo $isAuthorized ? 'Connected' : 'Not Connected'; ?></strong>
                    <p><?php echo $isAuthorized ? 'Your Google Calendar is connected and ready for syncing.' : 'Connect your Google Calendar to sync your class schedule.'; ?></p>
                </div>
            </div>

            <?php if (!$isAuthorized && $authUrl): ?>
                <a href="<?php echo htmlspecialchars($authUrl); ?>" class="google-btn">
                    <i class="fab fa-google"></i>
                    Connect Google Calendar
                </a>
            <?php endif; ?>
        </section>

        <!-- Calendar Actions -->
        <?php if ($isAuthorized): ?>
        <section class="content-section">
            <h2><i class="fas fa-cogs"></i> Calendar Actions</h2>
            
            <div class="calendar-actions">
                <div class="action-card sync">
                    <i class="fas fa-sync-alt"></i>
                    <h3>Sync Schedule</h3>
                    <p>Sync your current class schedule to Google Calendar</p>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="sync_schedule">
                        <button type="submit" class="btn" onclick="return confirm('This will sync your class schedule to Google Calendar. Continue?');">
                            <i class="fas fa-sync-alt"></i> Sync Now
                        </button>
                    </form>
                </div>

                <div class="action-card revoke">
                    <i class="fas fa-unlink"></i>
                    <h3>Disconnect</h3>
                    <p>Remove Google Calendar access and delete synced events</p>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="revoke_access">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('This will disconnect Google Calendar and remove all synced events. Are you sure?');">
                            <i class="fas fa-unlink"></i> Disconnect
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Schedule Preview -->
        <?php if ($studentData && !empty($studentData['schedule'])): ?>
        <section class="content-section">
            <h2><i class="fas fa-eye"></i> Schedule Preview</h2>
            <p>This is the schedule that will be synced to your Google Calendar:</p>
            
            <div class="schedule-preview">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentData['schedule'] as $sched): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sched['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sched['desc']); ?></td>
                                <td><?php echo htmlspecialchars($sched['time']); ?></td>
                                <td><?php echo htmlspecialchars($sched['room'] ?? 'TBA'); ?></td>
                                <td><?php echo htmlspecialchars($sched['instructor'] ?? 'TBA'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- How It Works -->
        <section class="content-section">
            <h2><i class="fas fa-info-circle"></i> How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center;">
                    <i class="fas fa-link" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>1. Connect</h4>
                    <p>Authorize the student portal to access your Google Calendar</p>
                </div>
                <div style="text-align: center;">
                    <i class="fas fa-calendar-plus" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>2. Sync</h4>
                    <p>Your class schedule will be automatically added to a dedicated calendar</p>
                </div>
                <div style="text-align: center;">
                    <i class="fas fa-bell" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h4>3. Remind</h4>
                    <p>Get automatic reminders 15 and 5 minutes before each class</p>
                </div>
            </div>
        </section>

    </main>
</div>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}
</script>

</body>
</html>