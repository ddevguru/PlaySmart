<?php
// Test Session Persistence Script
// This script tests if sessions are being maintained properly

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔐 Session Persistence Test</h1>";
echo "<hr>";

echo "<h2>Testing Session Persistence</h2>";

// Test 1: Check if session manager is working
echo "<h3>1. Session Manager Status</h3>";
$sessionFile = 'simple_session_manager.php';
if (file_exists($sessionFile)) {
    echo "<p style='color: green;'>✓ $sessionFile exists</p>";
    
    // Test the endpoints
    $validateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=validate_token';
    $updateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=update_activity';
    
    echo "<p>Testing endpoints:</p>";
    echo "<ul>";
    
    // Test validate_token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $validateUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=test_token_123');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<li style='color: green;'>✓ validate_token endpoint working (HTTP $httpCode)</li>";
    } else {
        echo "<li style='color: red;'>✗ validate_token endpoint failed (HTTP $httpCode)</li>";
    }
    
    // Test update_activity
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $updateUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'session_token=test_token_123');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<li style='color: green;'>✓ update_activity endpoint working (HTTP $httpCode)</li>";
    } else {
        echo "<li style='color: red;'>✗ update_activity endpoint failed (HTTP $httpCode)</li>";
    }
    
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ $sessionFile not found</p>";
}

// Test 2: Check if login.php exists
echo "<h3>2. Login API Status</h3>";
$loginFile = 'login.php';
if (file_exists($loginFile)) {
    echo "<p style='color: green;'>✓ $loginFile exists</p>";
    
    // Check if it has proper session handling
    $content = file_get_contents($loginFile);
    if (strpos($content, 'setBool') !== false || strpos($content, 'isLoggedIn') !== false) {
        echo "<p style='color: green;'>✓ Login API appears to handle session flags</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Login API may not be setting session flags properly</p>";
    }
} else {
    echo "<p style='color: red;'>✗ $loginFile not found</p>";
}

echo "<hr>";

echo "<h2>🔍 Flutter App Session Debugging Guide</h2>";

echo "<h3>What to Check in Flutter Console:</h3>";

echo "<h4>1. App Startup Messages:</h4>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "🔐 === SESSION DEBUG START ===
🔐 DEBUG: Checking login status...
🔐 DEBUG: Token exists: true
🔐 DEBUG: Token value: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
🔐 DEBUG: isLoggedIn flag: true
🔐 DEBUG: All SharedPreferences keys: {token, isLoggedIn, rememberedEmail}
🔐 DEBUG: ✅ Token found, checking login status...
🔐 DEBUG: ✅ User appears to be logged in, validating token...
🔐 DEBUG: ✅ App initialized for logged-in user
🔐 === SESSION DEBUG END ===";
echo "</pre>";

echo "<h4>2. App Resume Messages:</h4>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "🔐 DEBUG: App resumed - checking session status...
🔐 DEBUG: === SESSION RECOVERY CHECK ===
🔐 DEBUG: Token exists: true
🔐 DEBUG: isLoggedIn flag: true
🔐 DEBUG: ✅ Session is healthy, no recovery needed
🔐 DEBUG: === SESSION RECOVERY END ===";
echo "</pre>";

echo "<h4>3. Background Validation Messages:</h4>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "🔐 DEBUG: Starting background token validation...
🔐 DEBUG: Token validation response: 200
🔐 DEBUG: Token validation body: {\"success\":true,\"message\":\"Token is valid\"}
🔐 DEBUG: ✅ Token validation successful in background
🔐 DEBUG: ✅ Confirmed isLoggedIn flag is set to true
🔐 DEBUG: ✅ Token restored";
echo "</pre>";

echo "<hr>";

echo "<h2>🚨 Common Issues & Solutions</h2>";

echo "<h3>Issue 1: App Still Logs Out on Restart</h3>";
echo "<p><strong>Symptoms:</strong> User sees login screen after app restart</p>";
echo "<p><strong>Debug Steps:</strong></p>";
echo "<ol>";
echo "<li>Check console for <code>🔐 DEBUG: ❌ No token found</code> message</li>";
echo "<li>Verify SharedPreferences are not being cleared</li>";
echo "<li>Check if <code>isLoggedIn</code> flag is being set to false</li>";
echo "</ol>";

echo "<h3>Issue 2: Session Recovery Not Working</h3>";
echo "<p><strong>Symptoms:</strong> Token exists but user still logged out</p>";
echo "<p><strong>Debug Steps:</strong></p>";
echo "<ol>";
echo "<li>Look for <code>🔐 DEBUG: 🔄 Session recovery needed!</code> message</li>";
echo "<li>Check if <code>_recoverSessionIfNeeded()</code> is being called</li>";
echo "<li>Verify token restoration is working</li>";
echo "</ol>";

echo "<h3>Issue 3: Background Validation Clearing Session</h3>";
echo "<p><strong>Symptoms:</strong> User logged out after 30 minutes</p>";
echo "<p><strong>Debug Steps:</strong></p>";
echo "<ol>";
echo "<li>Monitor background validation messages</li>";
echo "<li>Check if token is being restored after validation</li>";
echo "<li>Verify <code>isLoggedIn</code> flag is maintained</li>";
echo "</ol>";

echo "<hr>";

echo "<h2>🧪 Testing Steps</h2>";

echo "<h3>Step 1: Test Login & Session Storage</h3>";
echo "<ol>";
echo "<li>Open Flutter app</li>";
echo "<li>Login with valid credentials</li>";
echo "<li><strong>Watch for:</strong> <code>🔐 DEBUG: ✅ App initialized for logged-in user</code></li>";
echo "<li>Verify you're on main screen</li>";
echo "</ol>";

echo "<h3>Step 2: Test App Restart Persistence</h3>";
echo "<ol>";
echo "<li>Close app completely (force stop)</li>";
echo "<li>Reopen app</li>";
echo "<li><strong>Watch for:</strong> Session debug messages</li>";
echo "<li><strong>Expected:</strong> You should still be logged in! 🎉</li>";
echo "</ol>";

echo "<h3>Step 3: Test Session Recovery</h3>";
echo "<ol>";
echo "<li>On main screen, tap profile button</li>";
echo "<li><strong>Watch for:</strong> Session recovery messages</li>";
echo "<li>Verify session status check shows healthy session</li>";
echo "</ol>";

echo "<h3>Step 4: Test Background Validation</h3>";
echo "<ol>";
echo "<li>Keep app open for 30+ minutes</li>";
echo "<li><strong>Watch for:</strong> Background validation messages</li>";
echo "<li>Verify app continues working normally</li>";
echo "</ol>";

echo "<hr>";

echo "<h2>📊 Debug Information to Monitor</h2>";

echo "<h3>Key Debug Messages:</h3>";
echo "<ul>";
echo "<li><code>🔐 DEBUG: ✅ Token found, checking login status...</code> - Session check successful</li>";
echo "<li><code>🔐 DEBUG: 🔄 Session recovery needed!</code> - Session recovery triggered</li>";
echo "<li><code>🔐 DEBUG: ✅ Session recovered!</code> - Session recovery successful</li>";
echo "<li><code>🔐 DEBUG: ⚠️ Token was cleared! Restoring it...</code> - Token restoration needed</li>";
echo "<li><code>🔐 DEBUG: ✅ Token restored</code> - Token restoration successful</li>";
echo "</ul>";

echo "<h3>SharedPreferences Keys to Monitor:</h3>";
echo "<ul>";
echo "<li><code>isLoggedIn</code> - Must be <code>true</code> for logged-in users</li>";
echo "<li><code>token</code> - Must contain valid authentication token</li>";
echo "<li><code>rememberedEmail</code> - Optional, for remember me feature</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>🎯 Success Criteria</h2>";

echo "<p><strong>✅ Session Persistence Working:</strong></p>";
echo "<ul>";
echo "<li>User logs in once and stays logged in</li>";
echo "<li>App restart doesn't require re-login</li>";
echo "<li>Background validation maintains session</li>";
echo "<li>Session recovery works automatically</li>";
echo "<li>Token and login flags are preserved</li>";
echo "</ul>";

echo "<p><strong>❌ Session Persistence Broken:</strong></p>";
echo "<ul>";
echo "<li>User needs to login after every app restart</li>";
echo "<li>Session debug shows missing token or false isLoggedIn</li>";
echo "<li>Background validation clears session</li>";
echo "<li>Session recovery fails</li>";
echo "</ul>";

echo "<hr>";

echo "<p><em>Session persistence test completed at: " . date('Y-m-d H:i:s') . "</em></p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test in Flutter app:</strong> Follow the testing steps above</li>";
echo "<li><strong>Monitor debug logs:</strong> Look for session debug messages</li>";
echo "<li><strong>Check session persistence:</strong> Restart app and verify login status</li>";
echo "<li><strong>Report any issues:</strong> Share debug messages if problems persist</li>";
echo "</ol>";

echo "<p><strong>Remember:</strong> The key is ensuring both <code>isLoggedIn</code> and <code>token</code> are properly set and maintained across app restarts!</p>";

echo "<h3>🔧 If Issues Persist:</h3>";
echo "<ol>";
echo "<li>Check if any other code is calling <code>prefs.clear()</code></li>";
echo "<li>Verify SharedPreferences are working correctly</li>";
echo "<li>Check if app is being killed by system</li>";
echo "<li>Monitor memory usage and app lifecycle</li>";
echo "</ol>";
?> 