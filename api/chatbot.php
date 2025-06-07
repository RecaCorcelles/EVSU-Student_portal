<?php
// filepath: c:\xampp\htdocs\student_portal\api\chatbot.php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/evsu_api_supabase.php';
require_once '../includes/chatbot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

try {
    $chatbot = new StudentPortalChatbot($_SESSION['student_id']);
    $response = $chatbot->processMessage($message);
    
    // Log the interaction
    logActivity('Chatbot Query: ' . substr($message, 0, 100), $_SESSION['student_id']);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Chatbot Error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Sorry, I encountered an error. Please try again later.',
        'success' => false
    ]);
}
?>