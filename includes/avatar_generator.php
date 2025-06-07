<?php
/**
 * Avatar Generator - Create letter-based profile images
 * Alternative to external APIs for generating default avatars
 */

class AvatarGenerator {
    
    /**
     * Generate avatar image and return base64 data URL
     */
    public static function generateAvatar($name, $size = 128, $backgroundColor = null, $textColor = '#ffffff') {
        // Get initials from name
        $initials = self::getInitials($name);
        
        // Generate background color if not provided
        if (!$backgroundColor) {
            $backgroundColor = self::generateColorFromName($name);
        }
        
        // Create image
        $image = imagecreatetruecolor($size, $size);
        
        // Convert hex colors to RGB
        $bgColor = self::hexToRgb($backgroundColor);
        $txtColor = self::hexToRgb($textColor);
        
        // Set colors
        $background = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        $textColorRes = imagecolorallocate($image, $txtColor[0], $txtColor[1], $txtColor[2]);
        
        // Fill background
        imagefill($image, 0, 0, $background);
        
        // Calculate font size and position
        $fontSize = $size * 0.4;
        $fontFile = self::getFontPath();
        
        if ($fontFile && function_exists('imagettfbbox')) {
            // Use TTF font if available
            $textBox = imagettfbbox($fontSize, 0, $fontFile, $initials);
            $textWidth = $textBox[4] - $textBox[0];
            $textHeight = $textBox[1] - $textBox[7];
            
            $x = ($size - $textWidth) / 2;
            $y = ($size - $textHeight) / 2 + $textHeight;
            
            imagettftext($image, $fontSize, 0, $x, $y, $textColorRes, $fontFile, $initials);
        } else {
            // Fallback to built-in font
            $font = 5; // Large built-in font
            $textWidth = imagefontwidth($font) * strlen($initials);
            $textHeight = imagefontheight($font);
            
            $x = ($size - $textWidth) / 2;
            $y = ($size - $textHeight) / 2;
            
            imagestring($image, $font, $x, $y, $initials, $textColorRes);
        }
        
        // Convert to base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Generate avatar and save to file
     */
    public static function generateAvatarFile($name, $filename, $size = 128, $backgroundColor = null, $textColor = '#ffffff') {
        $dataUrl = self::generateAvatar($name, $size, $backgroundColor, $textColor);
        
        // Extract base64 data
        $imageData = explode(',', $dataUrl)[1];
        $imageData = base64_decode($imageData);
        
        return file_put_contents($filename, $imageData);
    }
    
    /**
     * Get initials from full name
     */
    private static function getInitials($name, $maxLength = 2) {
        $name = trim($name);
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            if (strlen($initials) < $maxLength && !empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return $initials ?: 'U'; // Default to 'U' for User
    }
    
    /**
     * Generate consistent color from name
     */
    private static function generateColorFromName($name) {
        $colors = [
            '#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e',
            '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50',
            '#f39c12', '#e67e22', '#e74c3c', '#95a5a6', '#f1c40f',
            '#d35400', '#c0392b', '#bdc3c7', '#7f8c8d'
        ];
        
        $hash = md5($name);
        $index = hexdec(substr($hash, 0, 2)) % count($colors);
        
        return $colors[$index];
    }
    
    /**
     * Convert hex color to RGB array
     */
    private static function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Get font file path (you can add custom fonts here)
     */
    private static function getFontPath() {
        // Add path to TTF font file if you have one
        // return __DIR__ . '/fonts/arial.ttf';
        return null; // Use built-in font
    }
}

/**
 * Helper function for easy avatar generation
 */
function generateUserAvatar($name, $size = 128) {
    return AvatarGenerator::generateAvatar($name, $size);
}

/**
 * Helper function to get avatar URL (using UI Avatars API)
 */
function getAvatarUrl($name, $size = 128, $background = null) {
    $name = urlencode($name);
    $url = "https://ui-avatars.com/api/?name={$name}&size={$size}&font-size=0.6&rounded=true&uppercase=true";
    
    if ($background) {
        $background = ltrim($background, '#');
        $url .= "&background={$background}&color=fff";
    } else {
        $url .= "&background=random";
    }
    
    return $url;
}
?> 