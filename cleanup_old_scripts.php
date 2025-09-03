<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Cleanup Old PHP Scripts</h1>";
echo "<hr>";

// List of old scripts to remove
$oldScripts = [
    'submit_job_application.php',
    'submit_job_application_new.php',
    'submit_job_application_working.php',
    'submit_job_application_with_files.php',
    'submit_job_application_with_files_updated.php',
    'submit_job_application_debug.php',
    'submit_job_application_fixed.php'
];

// Keep only the working script
$workingScript = 'submit_job_application_final.php';

echo "<h2>1. Current Script Status</h2>";
echo "<p><strong>Working Script:</strong> $workingScript</p>";

echo "<h2>2. Removing Old Scripts</h2>";
$removedCount = 0;
$errors = [];

foreach ($oldScripts as $script) {
    if (file_exists($script)) {
        echo "<p>🗑️ Removing: $script</p>";
        
        // Backup before deletion (optional)
        $backupName = $script . '.backup.' . date('Y-m-d_H-i-s');
        if (copy($script, $backupName)) {
            echo "<p style='color: green;'>✅ Backup created: $backupName</p>";
        }
        
        // Delete the old script
        if (unlink($script)) {
            echo "<p style='color: green;'>✅ Successfully removed: $script</p>";
            $removedCount++;
        } else {
            echo "<p style='color: red;'>❌ Failed to remove: $script</p>";
            $errors[] = "Failed to remove: $script";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Script not found: $script</p>";
    }
}

echo "<h2>3. Verification</h2>";
echo "<p><strong>Scripts removed:</strong> $removedCount</p>";

if (empty($errors)) {
    echo "<p style='color: green;'>✅ All old scripts removed successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Errors occurred:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

// Check if working script still exists
if (file_exists($workingScript)) {
    echo "<p style='color: green;'>✅ Working script confirmed: $workingScript</p>";
    echo "<p><strong>File size:</strong> " . number_format(filesize($workingScript)) . " bytes</p>";
    echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($workingScript)) . "</p>";
} else {
    echo "<p style='color: red;'>❌ CRITICAL: Working script missing!</p>";
}

echo "<h2>4. Next Steps</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50;'>";
echo "<h3 style='color: #2e7d32;'>🎯 IMMEDIATE ACTIONS REQUIRED:</h3>";
echo "<ol>";
echo "<li><strong>✅ Old scripts removed</strong> - This will prevent confusion</li>";
echo "<li><strong>🚀 Update Flutter app</strong> - Build new APK/IPA with working code</li>";
echo "<li><strong>📱 Force app update</strong> - Users must update to get file uploads</li>";
echo "<li><strong>📊 Monitor new applications</strong> - They should now have files</li>";
echo "</ol>";
echo "</div>";

echo "<h2>5. Why This Fixes the Problem</h2>";
echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800;'>";
echo "<p><strong>Before:</strong> Users with old app called old endpoints → No file uploads</p>";
echo "<p><strong>After:</strong> Users with old app will get 404 errors → Forces them to update</p>";
echo "<p><strong>Result:</strong> Only updated app users can submit → All will have files</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Cleanup completed at: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><strong>⚠️ IMPORTANT:</strong> After running this script, users with old app versions will get errors until they update!</p>";
?> 