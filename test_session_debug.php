<?php
// Test Session Debug Script
// This script helps debug session management issues

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔐 Session Debug Test</h1>";
echo "<hr>";

echo "<h2>Testing Session Management</h2>";

// Test 1: Check if simple_session_manager.php exists
echo "<h3>1. Session Manager File Check</h3>";
$sessionFile = 'simple_session_manager.php';
if (file_exists($sessionFile)) {
    echo "<p style='color: green;'>✓ $sessionFile exists</p>";
    
    // Check file contents for debugging
    $content = file_get_contents($sessionFile);
    if (strpos($content, 'validate_token') !== false) {
        echo "<p style='color: green;'>✓ validate_token action found</p>";
    } else {
        echo "<p style='color: red;'>✗ validate_token action not found</p>";
    }
    
    if (strpos($content, 'update_activity') !== false) {
        echo "<p style='color: green;'>✓ update_activity action found</p>";
    } else {
        echo "<p style='color: red;'>✗ update_activity action not found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ $sessionFile not found</p>";
}

// Test 2: Test validate_token endpoint
echo "<h3>2. Validate Token Endpoint Test</h3>";
$validateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=validate_token';
echo "<p>Testing URL: <code>$validateUrl</code></p>";

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
        
        if ($data['success']) {
            echo "<p style='color: green;'>✓ Token validation successful</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Token validation failed (expected for test token)</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ validate_token endpoint responded but format unexpected</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ validate_token endpoint failed with HTTP $httpCode</p>";
}

// Test 3: Test update_activity endpoint
echo "<h3>3. Update Activity Endpoint Test</h3>";
$updateUrl = 'https://playsmart.co.in/simple_session_manager.php?action=update_activity';
echo "<p>Testing URL: <code>$updateUrl</code></p>";

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

// Test 4: Check if login.php exists and works
echo "<h3>4. Login API Check</h3>";
$loginFile = 'login.php';
if (file_exists($loginFile)) {
    echo "<p style='color: green;'>✓ $loginFile exists</p>";
    
    // Test login endpoint
    $loginUrl = 'https://playsmart.co.in/login.php';
    echo "<p>Testing login endpoint: <code>$loginUrl</code></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=test@example.com&password=testpass');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "<p style='color: green;'>✓ Login endpoint working</p>";
            echo "<p>Response: " . json_encode($data) . "</p>";
            
            if (!$data['success']) {
                echo "<p style='color: orange;'>⚠️ Login failed (expected for test credentials)</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Login endpoint responded but format unexpected</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Login endpoint failed with HTTP $httpCode</p>";
    }
} else {
    echo "<p style='color: red;'>✗ $loginFile not found</p>";
}

echo "<hr>";

echo "<h2>🔍 Flutter App Session Debugging</h2>";

echo "<h3>What to Check in Flutter App:</h3>";
echo "<ol>";
echo "<li><strong>Login Process:</strong> Ensure <code>isLoggedIn</code> flag is set to <code>true</code></li>";
echo "<li><strong>Token Storage:</strong> Verify <code>token</code> is properly stored</li>";
echo "<li><strong>App Restart:</strong> Check if session persists after restart</li>";
echo "<li><strong>Debug Logs:</strong> Monitor console for session debug messages</li>";
echo "</ol>";

echo "<h3>Expected Debug Messages:</h3>";
echo "<pre>";
echo "🔐 === SESSION DEBUG START ===
🔐 DEBUG: Checking login status...
🔐 DEBUG: Token exists: true
🔐 DEBUG: Token value: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
🔐 DEBUG: isLoggedIn flag: true
🔐 DEBUG: All SharedPreferences keys: {token, isLoggedIn, rememberedEmail}
🔐 DEBUG: Key \"token\" = eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
🔐 DEBUG: Key \"isLoggedIn\" = true
🔐 DEBUG: ✅ User appears to be logged in, validating token...
🔐 DEBUG: ✅ App initialized for logged-in user
🔐 === SESSION DEBUG END ===";
echo "</pre>";

echo "<h3>Common Issues & Solutions:</h3>";

echo "<h4>Issue 1: isLoggedIn flag not being set</h4>";
echo "<p><strong>Solution:</strong> Check if login screen is properly setting the flag:</p>";
echo "<pre>";
echo "SharedPreferences prefs = await SharedPreferences.getInstance();
await prefs.setBool('isLoggedIn', true);  // This must be set!
await prefs.setString('token', data['token']);";
echo "</pre>";

echo "<h4>Issue 2: Token not being stored</h4>";
echo "<p><strong>Solution:</strong> Verify token is received from login API and stored:</p>";
echo "<pre>";
echo "if (data['success']) {
  await prefs.setString('token', data['token']);  // This must be set!
  await prefs.setBool('isLoggedIn', true);
}";
echo "</pre>";

echo "<h4>Issue 3: Session being cleared on app restart</h4>";
echo "<p><strong>Solution:</strong> Check if SharedPreferences are being cleared somewhere:</p>";
echo "<pre>";
echo "// DON'T do this on app startup:
// await prefs.clear();  // This clears everything!

// DO this instead:
final token = prefs.getString('token');
final isLoggedIn = prefs.getBool('isLoggedIn') ?? false;";
echo "</pre>";

echo "<h4>Issue 4: Background validation clearing session</h4>";
echo "<p><strong>Solution:</strong> Ensure background validation maintains login flag:</p>";
echo "<pre>";
echo "// Even if validation fails, maintain login flag:
final prefs = await SharedPreferences.getInstance();
await prefs.setBool('isLoggedIn', true);";
echo "</pre>";

echo "<hr>";

echo "<h2>🧪 Testing Steps</h2>";

echo "<h3>Step 1: Test Login</h3>";
echo "<ol>";
echo "<li>Open Flutter app</li>";
echo "<li>Login with valid credentials</li>";
echo "<li>Check console for: <code>🔐 DEBUG: ✅ User appears to be logged in</code></li>";
echo "<li>Verify you're on main screen</li>";
echo "</ol>";

echo "<h3>Step 2: Test Session Persistence</h3>";
echo "<ol>";
echo "<li>Close app completely</li>";
echo "<li>Reopen app</li>";
echo "<li>Check console for session debug messages</li>";
echo "<li>Verify you're still logged in (no login screen)</li>";
echo "</ol>";

echo "<h3>Step 3: Test Session Status</h3>";
echo "<ol>";
echo "<li>On main screen, tap profile button</li>";
echo "<li>Check console for session status check</li>";
echo "<li>Verify all session data is present</li>";
echo "</ol>";

echo "<h3>Step 4: Test Background Validation</h3>";
echo "<ol>";
echo "<li>Use app for 30+ minutes</li>";
echo "<li>Check console for background validation messages</li>";
echo "<li>Verify app continues working normally</li>";
echo "</ol>";

echo "<hr>";

echo "<h2>📊 Debug Information</h2>";

echo "<h3>SharedPreferences Keys to Monitor:</h3>";
echo "<ul>";
echo "<li><code>isLoggedIn</code> - Must be <code>true</code> for logged-in users</li>";
echo "<li><code>token</code> - Must contain valid authentication token</li>";
echo "<li><code>rememberedEmail</code> - Optional, for remember me feature</li>";
echo "</ul>";

echo "<h3>Key Debug Messages:</h3>";
echo "<ul>";
echo "<li><code>🔐 DEBUG: ✅ User appears to be logged in</code> - Session check successful</li>";
echo "<li><code>🔐 DEBUG: ✅ App initialized for logged-in user</code> - App ready for user</li>";
echo "<li><code>🔐 DEBUG: ❌ No valid login found</code> - Session missing, redirecting to login</li>";
echo "<li><code>🔐 DEBUG: ✅ Token validation successful in background</code> - Background validation working</li>";
echo "</ul>";

echo "<hr>";

echo "<p><em>Session debug test completed at: " . date('Y-m-d H:i:s') . "</em></p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test in Flutter app:</strong> Follow the testing steps above</li>";
echo "<li><strong>Monitor debug logs:</strong> Look for session debug messages</li>";
echo "<li><strong>Check SharedPreferences:</strong> Verify session data is stored</li>";
echo "<li><strong>Test app restart:</strong> Ensure session persists</li>";
echo "</ol>";

echo "<p><strong>Remember:</strong> The key is ensuring both <code>isLoggedIn</code> and <code>token</code> are properly set and maintained!</p>";
?> 