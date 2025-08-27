<?php
// Test Login Persistence
// This script tests the login persistence functionality

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Login Persistence Test</h1>";
echo "<hr>";

echo "<h2>Testing Login Flow</h2>";

// Test 1: Check if login.php exists
echo "<h3>1. Login API Check</h3>";
$loginFile = 'login.php';
if (file_exists($loginFile)) {
    echo "<p style='color: green;'>✓ $loginFile exists</p>";
} else {
    echo "<p style='color: red;'>✗ $loginFile not found</p>";
}

// Test 2: Check if logout.php exists
echo "<h3>2. Logout API Check</h3>";
$logoutFile = 'logout.php';
if (file_exists($logoutFile)) {
    echo "<p style='color: green;'>✓ $logoutFile exists</p>";
} else {
    echo "<p style='color: red;'>✗ $logoutFile not found</p>";
}

// Test 3: Check if simple_session_manager.php exists
echo "<h3>3. Session Manager Check</h3>";
$sessionFile = 'simple_session_manager.php';
if (file_exists($sessionFile)) {
    echo "<p style='color: green;'>✓ $sessionFile exists</p>";
} else {
    echo "<p style='color: red;'>✗ $sessionFile not found</p>";
}

// Test 4: Test session manager endpoints
echo "<h3>4. Session Manager Endpoints Test</h3>";

// Test validate_token action
$validateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=validate_token';
echo "<p>Testing validate_token: <code>$validateUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $validateUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=test_token_123');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "<p style='color: green;'>✓ validate_token endpoint working</p>";
        echo "<p>Response: " . json_encode($data) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ validate_token endpoint responded but format unexpected</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ validate_token endpoint failed with HTTP $httpCode</p>";
}

// Test update_activity action
$updateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=update_activity';
echo "<p>Testing update_activity: <code>$updateUrl</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $updateUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'session_token=test_token_123');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "<p style='color: green;'>✓ update_activity endpoint working</p>";
        echo "<p>Response: " . json_encode($data) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ update_activity endpoint responded but format unexpected</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ update_activity endpoint failed with HTTP $httpCode</p>";
}

echo "<hr>";

echo "<h2>Flutter App Login Persistence</h2>";

echo "<h3>What's Implemented:</h3>";
echo "<ul>";
echo "<li>✅ <code>isLoggedIn</code> flag in SharedPreferences</li>";
echo "<li>✅ <code>token</code> storage in SharedPreferences</li>";
echo "<li>✅ Login status check on app startup</li>";
echo "<li>✅ Background token validation</li>";
echo "<li>✅ Graceful error handling (no forced logout)</li>";
echo "<li>✅ Proper logout method</li>";
echo "</ul>";

echo "<h3>How It Works:</h3>";
echo "<ol>";
echo "<li><strong>App Startup:</strong> Checks <code>isLoggedIn</code> and <code>token</code> flags</li>";
echo "<li><strong>If Logged In:</strong> Initializes app data immediately, validates token in background</li>";
echo "<li><strong>If Not Logged In:</strong> Redirects to login screen</li>";
echo "<li><strong>Background Validation:</strong> Token validation every 30 minutes (non-blocking)</li>";
echo "<li><strong>Activity Updates:</strong> Updates last activity every 15 minutes</li>";
echo "<li><strong>Error Handling:</strong> Network errors don't force logout</li>";
echo "</ol>";

echo "<h3>Key Features:</h3>";
echo "<ul>";
echo "<li><strong>Persistent Login:</strong> Users stay logged in across app restarts</li>";
echo "<li><strong>Background Validation:</strong> Token validation doesn't block UI</li>";
echo "<li><strong>Graceful Degradation:</strong> App continues working even if validation fails</li>";
echo "<li><strong>Proper Logout:</strong> Users can manually logout when needed</li>";
echo "<li><strong>Data Persistence:</strong> All user data persists across sessions</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>Testing Steps</h2>";

echo "<h3>1. Test Login:</h3>";
echo "<ol>";
echo "<li>Open Flutter app</li>";
echo "<li>Login with valid credentials</li>";
echo "<li>Verify you're on main screen</li>";
echo "<li>Check SharedPreferences: <code>isLoggedIn = true</code></li>";
echo "</ol>";

echo "<h3>2. Test App Restart:</h3>";
echo "<ol>";
echo "<li>Close the app completely</li>";
echo "<li>Reopen the app</li>";
echo "<li>Verify you're still logged in (no login screen)</li>";
echo "<li>Check that all data is loaded</li>";
echo "</ol>";

echo "<h3>3. Test Background Validation:</h3>";
echo "<ol>";
echo "<li>Use the app for 30+ minutes</li>";
echo "<li>Check logs for token validation messages</li>";
echo "<li>Verify app continues working normally</li>";
echo "</ol>";

echo "<h3>4. Test Manual Logout:</h3>";
echo "<ol>";
echo "<li>Go to profile section</li>";
echo "<li>Click logout button</li>";
echo "<li>Verify you're redirected to login screen</li>";
echo "<li>Check SharedPreferences are cleared</li>";
echo "</ol>";

echo "<hr>";

echo "<h2>Debug Information</h2>";

echo "<h3>Flutter Logs to Watch:</h3>";
echo "<ul>";
echo "<li><code>DEBUG: Checking login status...</code></li>";
echo "<li><code>DEBUG: Token exists: true/false</code></li>";
echo "<li><code>DEBUG: isLoggedIn flag: true/false</code></li>";
echo "<li><code>DEBUG: User appears to be logged in, validating token...</code></li>";
echo "<li><code>DEBUG: Token validation successful in background</code></li>";
echo "<li><code>DEBUG: Starting logout process...</code></li>";
echo "</ul>";

echo "<h3>SharedPreferences Keys:</h3>";
echo "<ul>";
echo "<li><code>isLoggedIn</code> - Boolean flag for login status</li>";
echo "<li><code>token</code> - User authentication token</li>";
echo "<li><code>rememberedEmail</code> - Email for remember me feature</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>Common Issues & Solutions</h2>";

echo "<h3>Issue: Still getting logged out on restart</h3>";
echo "<p><strong>Solution:</strong> Check if <code>isLoggedIn</code> flag is being set to <code>false</code> somewhere in the code.</p>";

echo "<h3>Issue: Token validation failing</h3>";
echo "<p><strong>Solution:</strong> Check backend session manager and ensure tokens are being stored/retrieved correctly.</p>";

echo "<h3>Issue: App crashes on startup</h3>";
echo "<p><strong>Solution:</strong> Ensure all SharedPreferences operations are wrapped in try-catch blocks.</p>";

echo "<hr>";

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test in Flutter app:</strong> Login and restart app multiple times</li>";
echo "<li><strong>Monitor logs:</strong> Check for debug messages about login status</li>";
echo "<li><strong>Verify persistence:</strong> Ensure users stay logged in across restarts</li>";
echo "<li><strong>Test logout:</strong> Verify manual logout works correctly</li>";
echo "</ol>";
?> 