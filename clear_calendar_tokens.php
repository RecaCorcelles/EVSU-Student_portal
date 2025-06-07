<?php
/**
 * Clear Google Calendar OAuth Tokens
 * Run this script after updating OAuth scopes to force re-authorization
 */

echo "Clearing Google Calendar OAuth Tokens\n";
echo "=====================================\n\n";

$tokensDir = __DIR__ . '/tokens';

if (!is_dir($tokensDir)) {
    echo "❌ Tokens directory doesn't exist: $tokensDir\n";
    exit(1);
}

$tokenFiles = glob($tokensDir . '/google_token_*.json');

if (empty($tokenFiles)) {
    echo "✅ No Google Calendar tokens found to clear.\n";
} else {
    $cleared = 0;
    foreach ($tokenFiles as $tokenFile) {
        if (unlink($tokenFile)) {
            echo "✅ Cleared: " . basename($tokenFile) . "\n";
            $cleared++;
        } else {
            echo "❌ Failed to clear: " . basename($tokenFile) . "\n";
        }
    }
    
    echo "\n📊 Summary: Cleared $cleared token file(s)\n";
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "✅ All Google Calendar tokens have been cleared!\n\n";
echo "Next steps:\n";
echo "1. Users need to re-authorize Google Calendar access\n";
echo "2. Go to Calendar Settings in the student portal\n";
echo "3. Click 'Connect Google Calendar' again\n";
echo "4. The new authorization will have expanded permissions\n";
echo "5. Calendar sync should now work properly\n\n";
echo "The OAuth scopes have been updated to include:\n";
echo "- Full calendar management (create/edit calendars)\n";
echo "- Calendar events management (create/edit events)\n";
?> 