<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Generator Demo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .demo-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .avatar-item {
            text-align: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .avatar-item img {
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .code-sample {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🎨 Avatar Generator Demo</h1>
    
    <?php
    require_once 'includes/avatar_generator.php';
    
    $testNames = [
        'John Doe',
        'Maria Santos',
        'David Chen',
        'Sarah Johnson',
        'Michael Brown'
    ];
    ?>
    
    <div class="demo-section">
        <h2>📡 UI Avatars API (Recommended)</h2>
        <p>Free external API service - no server processing needed</p>
        
        <div class="avatar-grid">
            <?php foreach ($testNames as $name): ?>
                <div class="avatar-item">
                    <img src="<?= getAvatarUrl($name, 80) ?>" alt="<?= $name ?>" width="80" height="80">
                    <div><strong><?= $name ?></strong></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="code-sample">
// Usage in your PHP code:<br>
&lt;img src="&lt;?= getAvatarUrl($user['name'], 64) ?&gt;" alt="Avatar"&gt;<br><br>
// Or direct URL:<br>
$avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=128&background=random";
        </div>
    </div>
    
    <div class="demo-section">
        <h2>🏠 Local PHP Generator</h2>
        <p>Generate avatars locally using PHP GD library</p>
        
        <div class="avatar-grid">
            <?php if (extension_loaded('gd')): ?>
                <?php foreach ($testNames as $name): ?>
                    <div class="avatar-item">
                        <img src="<?= generateUserAvatar($name, 80) ?>" alt="<?= $name ?>" width="80" height="80">
                        <div><strong><?= $name ?></strong></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: red;">❌ GD extension not available. Install php-gd to use local generation.</p>
            <?php endif; ?>
        </div>
        
        <div class="code-sample">
// Usage with local generator:<br>
&lt;img src="&lt;?= generateUserAvatar($user['name'], 64) ?&gt;" alt="Avatar"&gt;<br><br>
// Or save to file:<br>
AvatarGenerator::generateAvatarFile($name, 'avatars/user_123.png', 128);
        </div>
    </div>
    
    <div class="demo-section">
        <h2>🎯 DiceBear API</h2>
        <p>Alternative API with various avatar styles</p>
        
        <div class="avatar-grid">
            <?php foreach (array_slice($testNames, 0, 3) as $name): ?>
                <div class="avatar-item">
                    <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($name) ?>&backgroundColor=random" alt="<?= $name ?>" width="80" height="80">
                    <div><strong><?= $name ?></strong></div>
                    <small>Initials Style</small>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="code-sample">
// DiceBear initials style:<br>
$avatarUrl = "https://api.dicebear.com/7.x/initials/svg?seed=" . urlencode($name);<br><br>
// Other fun styles:<br>
$avatarUrl = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($name);<br>
$avatarUrl = "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($name);
        </div>
    </div>
    
    <div class="demo-section">
        <h2>🔧 Integration Tips</h2>
        <ul>
            <li><strong>Database Field:</strong> Add a `profile_image` field to your users table</li>
            <li><strong>Fallback Logic:</strong> Show custom image if uploaded, otherwise use letter avatar</li>
            <li><strong>Caching:</strong> Consider caching generated avatars for better performance</li>
            <li><strong>Consistent Colors:</strong> Use name-based color generation for consistency</li>
        </ul>
        
        <div class="code-sample">
// Example integration in profile.php:<br>
$profileImage = !empty($user['profile_image']) <br>
&nbsp;&nbsp;&nbsp;&nbsp;? 'uploads/profiles/' . $user['profile_image']<br>
&nbsp;&nbsp;&nbsp;&nbsp;: getAvatarUrl($user['first_name'] . ' ' . $user['last_name'], 128);<br><br>
echo "&lt;img src='$profileImage' alt='Profile' class='profile-avatar'&gt;";
        </div>
    </div>
</body>
</html> 