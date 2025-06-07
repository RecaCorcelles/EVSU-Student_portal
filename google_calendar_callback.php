<?php
/**
 * Google Calendar OAuth Callback Handler
 * This file handles the OAuth callback from Google
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php?error=not_logged_in");
    exit();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/google_calendar.php';

$student_id = $_SESSION['student_id'];

// Check if we received an authorization code
if (!isset($_GET['code'])) {
    $error = isset($_GET['error']) ? $_GET['error'] : 'No authorization code received';
    header("Location: calendar_settings.php?error=" . urlencode($error));
    exit();
}

try {
    // Initialize Google Calendar integration
    $googleCalendar = new GoogleCalendarIntegration($student_id);
    
    // Handle the OAuth callback
    $success = $googleCalendar->handleCallback($_GET['code']);
    
    if ($success) {
        // Log the successful authorization
        logActivity('Google Calendar Authorized', $student_id);
        
        // Redirect with success message
        header("Location: calendar_settings.php?success=authorized");
    } else {
        header("Location: calendar_settings.php?error=authorization_failed");
    }
    
} catch (Exception $e) {
    error_log("Google Calendar OAuth Error: " . $e->getMessage());
    header("Location: calendar_settings.php?error=" . urlencode($e->getMessage()));
}

exit();
?> 