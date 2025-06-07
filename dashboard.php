<?php
// filepath: c:\xampp\htdocs\student_portal\dashboard.php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

$student_id = $_SESSION['student_id'];

// Use the enhanced session validation from functions.php
requireLogin();

// Log the dashboard access
logActivity('Dashboard Access', $student_id);

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
    error_log("Dashboard Error for student $student_id: " . $e->getMessage());
    $error_message = "Unable to load student data. Please contact support.";
    $studentData = null;
    $studentGrades = [];
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

// Check if we're in mock mode for display purposes
$mockMode = $supabaseAPI->isMockMode();

// Get current academic info
$currentAcademicYear = getCurrentAcademicYear();
$currentSemester = getCurrentSemester();
$enrollmentOpen = isEnrollmentOpen();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PORTAL_NAME; ?> - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dashboard Specific Styles */
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .content-section:hover {
             transform: translateY(-3px);
             box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
        
        .refresh-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        .refresh-btn:hover {
            background-color: var(--primary-color);
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
        
        .badge-approved {
            background-color: #4caf50;
            color: white;
        }
        
        .badge-declined {
            background-color: #f44336;
            color: white;
        }
        
        .academic-info {
            background-color: #f0f8ff;
            border: 1px solid #4169e1;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 1rem;
        }
        
        .academic-info h4 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: var(--text-color-dark);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-decoration: none;
        }

        .action-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .action-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
        }

        .action-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--text-color-light);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-section h1 {
            margin: 0 0 1rem 0;
            font-size: 2rem;
        }

        .welcome-section p {
            margin: 0;
            opacity: 0.9;
        }

        /* Chatbot Widget Styles */
        .chatbot-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            z-index: 1000;
            overflow: hidden;
        }

        .chatbot-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chatbot-header i:first-child {
            margin-right: 10px;
        }

        .chatbot-body {
            height: 400px;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }

        .chatbot-body.collapsed {
            display: none;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .bot-message, .user-message {
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease;
        }

        .user-message {
            text-align: right;
        }

        .message-content {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            word-wrap: break-word;
            white-space: pre-line;
        }

        .bot-message .message-content {
            background: #e3f2fd;
            color: #1976d2;
            border-bottom-left-radius: 5px;
        }

        .user-message .message-content {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 0.7rem;
            color: #666;
            margin-top: 5px;
        }

        .chatbot-input {
            display: flex;
            padding: 15px;
            background: white;
            border-top: 1px solid #dee2e6;
        }

        .chatbot-input input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 10px 15px;
            outline: none;
        }

        .chatbot-input button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 10px;
            cursor: pointer;
        }

        .chatbot-suggestions {
            position: fixed;
            bottom: 90px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 999;
        }

        .chatbot-suggestions button {
            background: white;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .chatbot-suggestions button:hover {
            background: var(--primary-color);
            color: white;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 10px 15px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #999;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typingAnimation {
            0%, 80%, 100% { transform: scale(0); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .chatbot-widget {
                width: calc(100% - 40px);
                bottom: 10px;
                right: 20px;
                left: 20px;
            }
            
            .chatbot-suggestions {
                right: 20px;
                left: 20px;
            }
            
            .chatbot-suggestions button {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header class="dashboard-header">
    <h1><?php echo PORTAL_NAME; ?></h1>
    <div class="header-right">
        <span>Welcome, <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?>!</span>
        <a href="profile.php" title="Profile Settings">
            <img src="<?php echo $profile_photo_path; ?>" alt="Profile Picture" class="profile-pic-small">
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a></li>
            <li><a href="calendar_settings.php"><i class="fab fa-google"></i> Calendar Sync</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="system-info">
                <i class="fas fa-database"></i> <strong>Live System:</strong> Connected to EVSU Enrollment Database
                <button class="refresh-btn" onclick="location.reload();">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome Back, <?php echo htmlspecialchars($studentData['name'] ?? $student_id); ?>!</h1>
            <p>Access your academic information and manage your student portal from here.</p>
        </div>

        <!-- Academic Year Information -->
        <div class="academic-info">
            <h4><i class="fas fa-calendar-check"></i> Academic Information</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div><strong>Academic Year:</strong> <?php echo $currentAcademicYear; ?></div>
                <div><strong>Current Semester:</strong> <?php echo $currentSemester; ?></div>
                <div><strong>Enrollment Status:</strong> 
                    <span class="badge badge-<?php echo $enrollmentOpen ? 'active' : 'pending'; ?>">
                        <?php echo $enrollmentOpen ? 'Open' : 'Closed'; ?>
                    </span>
                </div>
                <div><strong>Last Login:</strong> <?php echo formatDate($_SESSION['login_time'] ?? time(), 'M j, Y g:i A'); ?></div>
            </div>
        </div>

        <?php if ($studentData): ?>
        <!-- Statistics Cards -->
        <div class="stats-grid">
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
                <span class="stat-number"><?php echo count($studentGrades ?? []); ?></span>
                <span class="stat-label">Completed Subjects</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <section class="content-section">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="quick-actions">
                <a href="subjects.php" class="action-card">
                    <i class="fas fa-book"></i>
                    <h3>View Subjects</h3>
                    <p>Check your enrolled subjects for this semester</p>
                </a>
                <a href="schedule.php" class="action-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Class Schedule</h3>
                    <p>View your weekly class schedule</p>
                </a>
                <a href="grades.php" class="action-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>View Grades</h3>
                    <p>Check your academic performance and GPA</p>
                </a>
                <a href="profile.php" class="action-card">
                    <i class="fas fa-user-edit"></i>
                    <h3>Update Profile</h3>
                    <p>Manage your profile information and settings</p>
                </a>
            </div>
        </section>

        <!-- AI Chatbot Widget -->
        <?php if (CHATBOT_ENABLED): ?>
        <div id="chatbot-widget" class="chatbot-widget">
            <div class="chatbot-header" onclick="toggleChatbot()">
                <i class="fas fa-robot"></i>
                <span>EVSU Assistant</span>
                <i class="fas fa-chevron-up" id="chatbot-toggle"></i>
            </div>
            <div class="chatbot-body" id="chatbot-body">
                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="bot-message">
                        <div class="message-content">
                            👋 Hi! I'm your EVSU Student Portal assistant. Ask me about your grades, schedule, subjects, or enrollment!
                        </div>
                        <div class="message-time"><?php echo date('g:i A'); ?></div>
                    </div>
                </div>
                <div class="chatbot-input">
                    <input type="text" id="chatbot-input-field" placeholder="Ask me anything about your academics..." />
                    <button onclick="sendMessage()" id="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Suggestions -->
        <div class="chatbot-suggestions" id="chatbot-suggestions">
            <button onclick="sendQuickMessage('What are my grades?')">📚 My Grades</button>
            <button onclick="sendQuickMessage('Show my schedule')">📅 Schedule</button>
            <button onclick="sendQuickMessage('What subjects am I taking?')">📖 Subjects</button>
            <button onclick="sendQuickMessage('Is enrollment open?')">📝 Enrollment</button>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- Add CSRF token -->
<input type="hidden" id="csrf-token" value="<?php echo generateCSRFToken(); ?>">

<script src="js/script.js"></script>
<script>
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}

// Auto-refresh every 5 minutes to keep data current
setTimeout(function() {
    location.reload();
}, 300000); // 5 minutes in milliseconds

// Chatbot script
let chatbotExpanded = false;

function toggleChatbot() {
    const body = document.getElementById('chatbot-body');
    const toggle = document.getElementById('chatbot-toggle');
    const suggestions = document.getElementById('chatbot-suggestions');
    
    chatbotExpanded = !chatbotExpanded;
    
    if (chatbotExpanded) {
        body.classList.remove('collapsed');
        toggle.className = 'fas fa-chevron-down';
        suggestions.style.display = 'flex';
    } else {
        body.classList.add('collapsed');
        toggle.className = 'fas fa-chevron-up';
        suggestions.style.display = 'none';
    }
}

function sendMessage() {
    const input = document.getElementById('chatbot-input-field');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message to chat
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    showTypingIndicator();
    
    // Send to chatbot API
    fetch('api/chatbot.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        hideTypingIndicator();
        if (data.success) {
            addMessage(data.response, 'bot');
        } else {
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        }
    })
    .catch(error => {
        hideTypingIndicator();
        addMessage('Connection error. Please check your internet connection.', 'bot');
    });
}

function sendQuickMessage(message) {
    document.getElementById('chatbot-input-field').value = message;
    sendMessage();
}

function addMessage(content, sender) {
    const messages = document.getElementById('chatbot-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = sender + '-message';
    
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    messageDiv.innerHTML = `
        <div class="message-content">${content}</div>
        <div class="message-time">${time}</div>
    `;
    
    messages.appendChild(messageDiv);
    messages.scrollTop = messages.scrollHeight;
}

function showTypingIndicator() {
    const messages = document.getElementById('chatbot-messages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'bot-message typing-indicator';
    typingDiv.id = 'typing-indicator';
    typingDiv.innerHTML = `
        <div class="message-content">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>
    `;
    messages.appendChild(typingDiv);
    messages.scrollTop = messages.scrollHeight;
}

function hideTypingIndicator() {
    const typing = document.getElementById('typing-indicator');
    if (typing) typing.remove();
}

// Handle Enter key in input
document.getElementById('chatbot-input-field').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Initialize collapsed state
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('chatbot-body').classList.add('collapsed');
    document.getElementById('chatbot-suggestions').style.display = 'none';
});
</script>

</body>
</html>