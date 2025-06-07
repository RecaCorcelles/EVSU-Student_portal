<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Mock student data for demonstration
$demo_students = [
    [
        'student_id' => '2021-123456',
        'first_name' => 'Maria',
        'last_name' => 'Santos', 
        'name' => 'Maria Santos',
        'course' => 'BSIT'
    ],
    [
        'student_id' => '2021-789012',
        'first_name' => 'John',
        'last_name' => 'Dela Cruz',
        'name' => 'John Dela Cruz', 
        'course' => 'BSCS'
    ],
    [
        'student_id' => '2021-345678',
        'first_name' => 'Catherine',
        'last_name' => 'Lopez',
        'name' => 'Catherine Lopez',
        'course' => 'BSIT'
    ],
    [
        'student_id' => '2021-901234',
        'first_name' => 'Michael',
        'last_name' => 'Torres',
        'name' => 'Michael Torres',
        'course' => 'BSCS'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Integration Demo - EVSU Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: var(--background-light);
            font-family: Arial, sans-serif;
        }
        .demo-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .demo-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }
        .demo-section {
            background: white;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .avatar-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1rem 0;
        }
        .student-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 3px solid var(--primary-color);
        }
        .student-info h4 {
            color: var(--primary-color);
            margin: 0.5rem 0;
        }
        .student-info p {
            color: #666;
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        .integration-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .integration-info h4 {
            color: #1976d2;
            margin-top: 0;
        }
        .code-sample {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        .benefit-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .benefit-item {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }
        .benefit-item i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <header class="demo-header">
            <h1><i class="fas fa-user-circle"></i> Avatar Integration Demo</h1>
            <p>UI Avatars API Successfully Integrated into EVSU Student Portal</p>
        </header>

        <div class="demo-section">
            <h2><i class="fas fa-users"></i> Student Avatar Showcase</h2>
            <p>Here's how your students will appear with the new avatar system:</p>
            
            <div class="avatar-showcase">
                <?php foreach ($demo_students as $student): ?>
                    <div class="student-card">
                        <img src="<?php echo getStudentProfileImage($student['student_id'], $student, 80); ?>" 
                             alt="<?php echo $student['name']; ?>" 
                             class="student-avatar">
                        <div class="student-info">
                            <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-cogs"></i> How It Works</h2>
            
            <div class="integration-info">
                <h4><i class="fas fa-magic"></i> Smart Avatar Generation</h4>
                <p>The system automatically generates letter-based avatars using the student's name when no profile photo is uploaded.</p>
            </div>

            <div class="code-sample">
// In your PHP pages, this is now happening automatically:
$profile_photo_path = getStudentProfileImage($student_id, $studentInfo, 40);

// The function automatically:
// 1. Checks for uploaded profile photo
// 2. If none exists, generates avatar from student name
// 3. Uses UI Avatars API for consistent, beautiful results
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-check-circle"></i> Integration Benefits</h2>
            
            <div class="benefit-list">
                <div class="benefit-item">
                    <i class="fas fa-user-plus"></i>
                    <strong>Better UX</strong><br>
                    <small>Every student has a profile image from day one</small>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-palette"></i>
                    <strong>Consistent Design</strong><br>
                    <small>Uniform circular avatars across all pages</small>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-rocket"></i>
                    <strong>Zero Maintenance</strong><br>
                    <small>Automatic generation, no manual intervention</small>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-eye"></i>
                    <strong>Easy Recognition</strong><br>
                    <small>Letter-based avatars help identify users quickly</small>
                </div>
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-tools"></i> Technical Implementation</h2>
            
            <h4>Pages Updated:</h4>
            <ul>
                <li>✅ Dashboard - Header avatar and welcome section</li>
                <li>✅ Profile - Main photo display and header</li>
                <li>🔄 All other pages (run update script to complete)</li>
            </ul>

            <h4>Functions Added to includes/functions.php:</h4>
            <div class="code-sample">
getAvatarUrl($name, $size = 128, $background = null)
generateStudentAvatar($student_data, $size = 128)  
getStudentProfileImage($student_id, $student_data = null, $size = 128)
            </div>

            <div class="integration-info">
                <h4><i class="fas fa-play-circle"></i> Next Steps</h4>
                <ol>
                    <li>Run the update script: <code>php update_avatar_integration.php</code></li>
                    <li>Test the system by visiting different pages</li>
                    <li>Users can still upload custom profile photos which take priority</li>
                    <li>Enjoy the enhanced user experience!</li>
                </ol>
            </div>
        </div>

        <div class="demo-section">
            <h2><i class="fas fa-question-circle"></i> Avatar Examples</h2>
            
            <p>Here are some example avatar URLs being generated:</p>
            
            <div class="code-sample">
// Maria Santos
<?php echo getAvatarUrl('Maria Santos', 64); ?>

// John Dela Cruz  
<?php echo getAvatarUrl('John Dela Cruz', 64); ?>

// Catherine Lopez
<?php echo getAvatarUrl('Catherine Lopez', 64); ?>
            </div>
        </div>

        <div style="text-align: center; margin: 2rem 0;">
            <a href="dashboard.php" class="btn" style="margin: 0.5rem;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="avatar_demo.php" class="btn" style="margin: 0.5rem;">
                <i class="fas fa-palette"></i> View API Demo
            </a>
        </div>
    </div>
</body>
</html> 