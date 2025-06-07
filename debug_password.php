<?php
// filepath: c:\xampp\htdocs\student_portal\debug_password.php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/evsu_api_supabase.php';

$student_id = $_SESSION['student_id'];

echo "<h2>Password Change Debug for Student: $student_id</h2>";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['debug_password'])) {
    $new_password = $_POST['new_password'];
    
    echo "<h3>Debug Information:</h3>";
    echo "<p>Student ID: " . htmlspecialchars($student_id) . "</p>";
    echo "<p>New Password: " . htmlspecialchars($new_password) . "</p>";
    
    global $supabaseAPI;
    
    // Check if student exists
    echo "<h4>1. Checking if student exists:</h4>";
    if ($supabaseAPI->studentExists($student_id)) {
        echo "<p style='color: green;'>✓ Student exists</p>";
        
        // Get current student info
        echo "<h4>2. Current student info:</h4>";
        $studentInfo = $supabaseAPI->getStudentInfo($student_id);
        if ($studentInfo) {
            echo "<pre>" . htmlspecialchars(json_encode($studentInfo, JSON_PRETTY_PRINT)) . "</pre>";
            
            // Test the raw API call with detailed debugging
            echo "<h4>3. Testing raw Supabase API call:</h4>";
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            echo "<p>Generated hash: " . htmlspecialchars($hashedPassword) . "</p>";
            
            $data = [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Try different approaches to identify the student
            echo "<h5>3a. Method 1: Using student_id filter</h5>";
            $params1 = ['student_id' => 'eq.' . $student_id];
            $url1 = 'https://qxcwdgcexuwjdrgfzxnd.supabase.co/rest/v1/users?' . http_build_query($params1);
            echo "<p>URL: " . htmlspecialchars($url1) . "</p>";
            
            $result1 = testSupabaseUpdate($url1, $data);
            echo "<p>Result: <pre>" . htmlspecialchars(json_encode($result1, JSON_PRETTY_PRINT)) . "</pre></p>";
            
            echo "<h5>3b. Method 2: Using id filter</h5>";
            $params2 = ['id' => 'eq.' . $studentInfo['id']];
            $url2 = 'https://qxcwdgcexuwjdrgfzxnd.supabase.co/rest/v1/users?' . http_build_query($params2);
            echo "<p>URL: " . htmlspecialchars($url2) . "</p>";
            
            $result2 = testSupabaseUpdate($url2, $data);
            echo "<p>Result: <pre>" . htmlspecialchars(json_encode($result2, JSON_PRETTY_PRINT)) . "</pre></p>";
            
            // Try to update password using helper function
            echo "<h4>4. Attempting password update using helper function:</h4>";
            $result = setStudentPortalPassword($student_id, $new_password);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Password update successful!</p>";
                
                // Verify the password was actually changed
                echo "<h4>5. Verifying password change:</h4>";
                $updatedInfo = $supabaseAPI->getStudentInfo($student_id);
                if ($updatedInfo && isset($updatedInfo['password']) && $updatedInfo['password'] !== $studentInfo['password']) {
                    echo "<p style='color: green;'>✓ Password hash changed in database</p>";
                    echo "<p>Old hash: " . htmlspecialchars(substr($studentInfo['password'], 0, 20)) . "...</p>";
                    echo "<p>New hash: " . htmlspecialchars(substr($updatedInfo['password'], 0, 20)) . "...</p>";
                } else {
                    echo "<p style='color: red;'>✗ Password hash did not change in database</p>";
                }
                
                // Test authentication with new password
                echo "<h4>6. Testing authentication with new password:</h4>";
                if (authenticateStudentCredentials($student_id, $new_password)) {
                    echo "<p style='color: green;'>✓ Authentication with new password successful</p>";
                } else {
                    echo "<p style='color: red;'>✗ Authentication with new password failed</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Password update failed</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Could not retrieve student info</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Student does not exist</p>";
    }
    
    // Show error logs from error.log file
    echo "<h4>7. Recent Error Logs:</h4>";
    $errorLogPath = 'C:\\xampp\\apache\\logs\\error.log';
    if (file_exists($errorLogPath)) {
        $logContent = file_get_contents($errorLogPath);
        $logLines = explode("\n", $logContent);
        $recentLines = array_slice($logLines, -20); // Last 20 lines
        
        echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
        echo htmlspecialchars(implode("\n", $recentLines));
        echo "</pre>";
    } else {
        echo "<p>Error log file not found at: $errorLogPath</p>";
    }
}

function testSupabaseUpdate($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF4Y3dkZ2NleHV3amRyZ2Z6eG5kIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDg3ODIyODIsImV4cCI6MjA2NDM1ODI4Mn0.PvSAK7iZwD-u6atl3r4ZvnVRfbvgC6gP8MLoG5EQcxQ',
        'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF4Y3dkZ2NleHV3amRyZ2Z6eG5kIiwicm9zZSI6ImFub24iLCJpYXQiOjE3NDg3ODIyODIsImV4cCI6MjA2NDM1ODI4Mn0.PvSAK7iZwD-u6atl3r4ZvnVRfbvgC6gP8MLoG5EQcxQ',
        'Content-Type: application/json',
        'Prefer: return=representation'
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response' => $response,
        'response_decoded' => json_decode($response, true)
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Change Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        input[type="password"] { padding: 8px; width: 200px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<form method="POST">
    <div class="form-group">
        <label>New Password to Test:</label><br>
        <input type="password" name="new_password" required>
    </div>
    <button type="submit" name="debug_password">Debug Password Change</button>
</form>

<p><a href="change_password.php">← Back to Change Password</a></p>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

</body>
</html>