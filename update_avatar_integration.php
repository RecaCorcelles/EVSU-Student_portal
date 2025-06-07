<?php
/**
 * Avatar Integration Update Script
 * This script updates all pages to use the new avatar system
 */

echo "Avatar Integration Update Script\n";
echo "===============================\n\n";

$pages_to_update = [
    'grades.php',
    'subjects.php', 
    'schedule.php',
    'enrollment.php',
    'change_password.php',
    'calendar_settings.php'
];

foreach ($pages_to_update as $page) {
    if (!file_exists($page)) {
        echo "❌ File not found: $page\n";
        continue;
    }
    
    echo "📄 Processing: $page\n";
    
    $content = file_get_contents($page);
    
    // Replace the old profile photo function call
    $old_pattern = '/\$profile_photo_path = getStudentProfilePhoto\(\$student_id\);/';
    $new_replacement = '// Use the shared function to get profile photo with avatar fallback
$student_full_name = \'\';
if ($studentInfo) {
    $student_full_name = ($studentInfo[\'first_name\'] ?? \'\') . \' \' . ($studentInfo[\'last_name\'] ?? \'\');
    $student_full_name = trim($student_full_name);
}

// Get profile image with avatar fallback  
$profile_photo_path = getStudentProfileImage($student_id, $studentInfo, 40);';
    
    if (preg_match($old_pattern, $content)) {
        $content = preg_replace($old_pattern, $new_replacement, $content);
        
        if (file_put_contents($page, $content)) {
            echo "   ✅ Updated successfully\n";
        } else {
            echo "   ❌ Failed to write changes\n";
        }
    } else {
        echo "   ⚠️  Pattern not found (may already be updated)\n";
    }
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "✅ Avatar integration update completed!\n\n";
echo "📋 Summary:\n";
echo "- Updated profile photo function calls\n";
echo "- Added avatar fallback logic\n";
echo "- Users will now see letter-based avatars when no photo is uploaded\n\n";
echo "🎨 Avatar Features:\n";
echo "- Consistent colors based on name\n";
echo "- First letter(s) of name displayed\n";
echo "- Circular design matching your theme\n";
echo "- Automatic fallback for all users\n\n";
echo "🚀 Ready to use! Visit any page to see the new avatars.\n";
?> 