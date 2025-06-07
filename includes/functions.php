<?php
// filepath: c:\xampp\htdocs\student_portal\includes\functions.php
/**
 * Common utility functions for EVSU Student Portal
 */

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Display formatted messages
 */
function display_message($message, $type = 'success') {
    $class = ($type === 'error') ? 'error' : 'success';
    echo "<div class=\"message $class\">" . htmlspecialchars($message) . "</div>";
}

/**
 * Generate a random string for tokens, OTPs, etc.
 */
function generateRandomString($length = 6, $type = 'numeric') {
    switch ($type) {
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'alphanumeric':
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alpha':
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        default:
            $characters = '0123456789';
    }
    
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate student ID format (adjust pattern as needed)
 */
function validateStudentId($studentId) {
    // Example: validates format like "2024-00001" or similar
    return preg_match('/^[0-9]{4}-[0-9]{5}$/', $studentId) || 
           preg_match('/^[A-Z0-9]{8,12}$/', $studentId);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user session is valid and not expired
 */
function isValidSession() {
    if (!isset($_SESSION['student_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check if session has expired
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isValidSession()) {
        header("Location: index.php?expired=1");
        exit();
    }
}

/**
 * Log actions and errors
 */
function logActivity($action, $studentId = null, $details = '') {
    if (!ENABLE_DEBUG_LOGGING) {
        return;
    }
    
    $logMessage = date('Y-m-d H:i:s') . " - ";
    $logMessage .= "Action: $action";
    
    if ($studentId) {
        $logMessage .= " - Student: $studentId";
    }
    
    if ($details) {
        $logMessage .= " - Details: $details";
    }
    
    $logMessage .= " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $logMessage .= PHP_EOL;
    
    error_log($logMessage, 3, __DIR__ . '/../logs/activity.log');
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'F j, Y') {
    if (empty($date)) return 'N/A';
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

/**
 * Format time for display
 */
function formatTime($time, $format = 'g:i A') {
    if (empty($time)) return 'N/A';
    
    try {
        $timeObj = new DateTime($time);
        return $timeObj->format($format);
    } catch (Exception $e) {
        return 'Invalid Time';
    }
}

/**
 * Calculate academic year from date
 */
function getCurrentAcademicYear() {
    $currentDate = date('Y-m-d');
    $academicYearStart = date('Y') . '-08-01'; // August 1st
    
    if ($currentDate >= $academicYearStart) {
        return date('Y') . '-' . (date('Y') + 1);
    } else {
        return (date('Y') - 1) . '-' . date('Y');
    }
}

/**
 * Get current semester based on date
 */
function getCurrentSemester() {
    $month = (int)date('n');
    
    if ($month >= 8 && $month <= 12) {
        return '1st Semester';
    } elseif ($month >= 1 && $month <= 5) {
        return '2nd Semester';
    } else {
        return 'Summer';
    }
}

/**
 * Check if enrollment is still open
 */
function isEnrollmentOpen() {
    $currentDate = date('Y-m-d');
    return $currentDate <= ENROLLMENT_DEADLINE;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting check
 */
function checkRateLimit($identifier, $limit = API_RATE_LIMIT, $window = 60) {
    $key = 'rate_limit_' . md5($identifier);
    $file = sys_get_temp_dir() . "/$key";
    
    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true) ?: [];
    }
    
    $now = time();
    $attempts = array_filter($attempts, function($time) use ($now, $window) {
        return ($now - $time) < $window;
    });
    
    if (count($attempts) >= $limit) {
        return false;
    }
    
    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts));
    
    return true;
}

/**
 * Convert file size to human readable format
 */
function formatFileSize($bytes, $decimals = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $decimals) . ' ' . $units[$i];
}

/**
 * Check if uploaded file is valid image
 */
function isValidImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, ALLOWED_IMAGE_TYPES);
}

/**
 * Generate unique filename for uploads
 */
function generateUniqueFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Get student profile photo path - checks for local uploaded photo, then generates avatar
 */
function getStudentProfilePhoto($student_id, $student_name = null) {
    $uploadDir = 'images/uploads/';
    $extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Check for uploaded profile photo first
    foreach ($extensions as $ext) {
        $photoPath = $uploadDir . $student_id . '.' . $ext;
        if (file_exists($photoPath)) {
            return $photoPath;
        }
    }
    
    // If no uploaded photo, generate avatar using student name
    if ($student_name) {
        return getAvatarUrl($student_name);
    }
    
    // Fallback to default avatar
    return 'images/default_avatar.png';
}

/**
 * Get avatar URL using UI Avatars API
 */
function getAvatarUrl($name, $size = 128, $background = null) {
    $name = urlencode(trim($name));
    $url = "https://ui-avatars.com/api/?name={$name}&size={$size}&font-size=0.6&rounded=true&uppercase=true";
    
    if ($background) {
        $background = ltrim($background, '#');
        $url .= "&background={$background}&color=fff";
    } else {
        $url .= "&background=random";
    }
    
    return $url;
}

/**
 * Generate avatar based on student information
 */
function generateStudentAvatar($student_data, $size = 128) {
    // Try to get full name from student data
    $name = '';
    
    if (isset($student_data['first_name']) && isset($student_data['last_name'])) {
        $name = $student_data['first_name'] . ' ' . $student_data['last_name'];
    } elseif (isset($student_data['name'])) {
        $name = $student_data['name'];
    } elseif (isset($student_data['student_name'])) {
        $name = $student_data['student_name'];
    } else {
        $name = 'Student User';
    }
    
    return getAvatarUrl($name, $size);
}

/**
 * Get profile image with enhanced fallback logic
 */
function getStudentProfileImage($student_id, $student_data = null, $size = 128) {
    // First check for uploaded photo
    $uploadedPhoto = getStudentProfilePhoto($student_id);
    
    // If it's not the default avatar, return the uploaded photo
    if ($uploadedPhoto !== 'images/default_avatar.png') {
        return $uploadedPhoto;
    }
    
    // Generate avatar from student data
    if ($student_data) {
        return generateStudentAvatar($student_data, $size);
    }
    
    // Last resort - generic avatar
    return getAvatarUrl('Student User', $size);
}
?>