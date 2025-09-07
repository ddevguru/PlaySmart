// lib/services/auth_service.dart
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;

class AuthService {
  static const String _baseUrl = 'https://playsmart.co.in';
  
  // SharedPreferences keys
  static const String _keyToken = 'token';
  static const String _keyIsLoggedIn = 'isLoggedIn';
  static const String _keyUserEmail = 'user_email';
  static const String _keyUserName = 'user_name';
  static const String _keyUserId = 'user_id';
  static const String _keyRememberMe = 'remember_me';
  static const String _keyRememberedEmail = 'rememberedEmail';
  static const String _keyRememberedPassword = 'rememberedPassword';
  static const String _keyLastLoginTime = 'last_login_time';
  static const String _keyAutoLogin = 'auto_login';
  
  // Singleton pattern
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();
  
  // User login with SharedPreferences integration
  Future<Map<String, dynamic>> login(String email, String password, bool rememberMe) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/login.php'),
        body: {
          'email': email,
          'password': password,
        },
      );

      final data = jsonDecode(response.body);
      
      if (data['success']) {
        // Save login details to SharedPreferences
        await _saveLoginDetails(
          token: data['token'],
          email: email,
          password: rememberMe ? password : null,
          userName: data['user_name'] ?? '',
          userId: data['user_id']?.toString() ?? '',
          rememberMe: rememberMe,
        );
        
        return {
          'success': true,
          'message': 'Login successful',
          'data': data,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Login failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e',
      };
    }
  }
  
  // User signup
  Future<Map<String, dynamic>> signup({
    required String username,
    required String email,
    required String phone,
    required String password,
    String? referralCode,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/signup.php'),
        body: {
          'username': username,
          'email': email,
          'phone': phone,
          'password': password,
          if (referralCode != null && referralCode.isNotEmpty) 'referral_code': referralCode,
        },
      );

      final data = jsonDecode(response.body);
      
      if (data['success']) {
        // Auto-login after successful signup
        await _saveLoginDetails(
          token: data['token'],
          email: email,
          password: null, // Don't save password for signup
          userName: username,
          userId: data['user_id']?.toString() ?? '',
          rememberMe: false,
        );
        
        return {
          'success': true,
          'message': 'Signup successful',
          'data': data,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Signup failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e',
      };
    }
  }
  
  // Save login details to SharedPreferences
  Future<void> _saveLoginDetails({
    required String token,
    required String email,
    String? password,
    required String userName,
    required String userId,
    required bool rememberMe,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    
    // Save essential login data
    await prefs.setString(_keyToken, token);
    await prefs.setBool(_keyIsLoggedIn, true);
    await prefs.setString(_keyUserEmail, email);
    await prefs.setString(_keyUserName, userName);
    await prefs.setString(_keyUserId, userId);
    await prefs.setBool(_keyRememberMe, rememberMe);
    await prefs.setString(_keyLastLoginTime, DateTime.now().toIso8601String());
    
    // Save email for remember me functionality
    if (rememberMe) {
      await prefs.setString(_keyRememberedEmail, email);
      await prefs.setBool(_keyAutoLogin, true);
      
      // Only save password if user explicitly wants to remember credentials
      if (password != null) {
        await prefs.setString(_keyRememberedPassword, password);
      }
    } else {
      // Clear remembered credentials if user doesn't want to remember
      await prefs.remove(_keyRememberedEmail);
      await prefs.remove(_keyRememberedPassword);
      await prefs.setBool(_keyAutoLogin, false);
    }
    
    print('✅ Login details saved to SharedPreferences');
  }
  
  // Check if user is logged in
  Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    final isLoggedIn = prefs.getBool(_keyIsLoggedIn) ?? false;
    final token = prefs.getString(_keyToken);
    
    // User is logged in if both flag is true and token exists
    return isLoggedIn && token != null && token.isNotEmpty;
  }
  
  // Get current user token
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyToken);
  }
  
  // Get current user email
  Future<String?> getUserEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyUserEmail);
  }
  
  // Get current user name
  Future<String?> getUserName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyUserName);
  }
  
  // Get current user ID
  Future<String?> getUserId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyUserId);
  }
  
  // Get remembered email
  Future<String?> getRememberedEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyRememberedEmail);
  }
  
  // Get remembered password (only if user explicitly saved it)
  Future<String?> getRememberedPassword() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyRememberedPassword);
  }
  
  // Check if remember me is enabled
  Future<bool> isRememberMeEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_keyRememberMe) ?? false;
  }
  
  // Check if auto login is enabled
  Future<bool> isAutoLoginEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_keyAutoLogin) ?? false;
  }
  
  // Auto login functionality
  Future<Map<String, dynamic>> autoLogin() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final isAutoLoginEnabled = prefs.getBool(_keyAutoLogin) ?? false;
      final rememberedEmail = prefs.getString(_keyRememberedEmail);
      final rememberedPassword = prefs.getString(_keyRememberedPassword);
      
      if (isAutoLoginEnabled && rememberedEmail != null) {
        if (rememberedPassword != null) {
          // Auto login with saved credentials
          return await login(rememberedEmail, rememberedPassword, true);
        } else {
          // Just fill the email, user needs to enter password
          return {
            'success': false,
            'message': 'Please enter your password',
            'email': rememberedEmail,
          };
        }
      }
      
      return {
        'success': false,
        'message': 'Auto login not available',
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Auto login failed: $e',
      };
    }
  }
  
  // Validate token with server
  Future<bool> validateToken() async {
    try {
      final token = await getToken();
      if (token == null) return false;
      
      final response = await http.post(
        Uri.parse('$_baseUrl/simple_session_manager.php?action=validate_token'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'token': token},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      print('Token validation error: $e');
      return false;
    }
  }
  
  // Update last activity
  Future<void> updateLastActivity() async {
    try {
      final token = await getToken();
      if (token == null) return;
      
      await http.post(
        Uri.parse('$_baseUrl/simple_session_manager.php?action=update_activity'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'session_token': token},
      ).timeout(const Duration(seconds: 10));
      
      // Update local timestamp
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('last_activity', DateTime.now().toIso8601String());
    } catch (e) {
      print('Update activity error: $e');
    }
  }
  
  // Logout user
  Future<void> logout() async {
    try {
      final token = await getToken();
      
      // Call logout API if token exists
      if (token != null) {
        try {
          await http.post(
            Uri.parse('$_baseUrl/logout.php'),
            body: {'token': token},
          ).timeout(const Duration(seconds: 5));
        } catch (e) {
          print('Logout API error: $e');
        }
      }
      
      // Clear all stored data except remembered credentials if user wants to keep them
      final prefs = await SharedPreferences.getInstance();
      final rememberMe = prefs.getBool(_keyRememberMe) ?? false;
      final rememberedEmail = prefs.getString(_keyRememberedEmail);
      final rememberedPassword = prefs.getString(_keyRememberedPassword);
      
      // Clear session data
      await prefs.remove(_keyToken);
      await prefs.setBool(_keyIsLoggedIn, false);
      await prefs.remove(_keyUserEmail);
      await prefs.remove(_keyUserName);
      await prefs.remove(_keyUserId);
      await prefs.remove(_keyLastLoginTime);
      
      // Keep remembered credentials if user had remember me enabled
      if (rememberMe && rememberedEmail != null) {
        await prefs.setString(_keyRememberedEmail, rememberedEmail);
        if (rememberedPassword != null) {
          await prefs.setString(_keyRememberedPassword, rememberedPassword);
        }
        await prefs.setBool(_keyAutoLogin, false); // Disable auto login but keep credentials
      } else {
        // Clear all remembered data
        await prefs.remove(_keyRememberedEmail);
        await prefs.remove(_keyRememberedPassword);
        await prefs.setBool(_keyRememberMe, false);
        await prefs.setBool(_keyAutoLogin, false);
      }
      
      print('✅ User logged out successfully');
    } catch (e) {
      print('Logout error: $e');
      
      // Force clear all data on error
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
    }
  }
  
  // Clear all stored data (for complete reset)
  Future<void> clearAllData() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    print('✅ All stored data cleared');
  }
  
  // Get user profile data
  Future<Map<String, dynamic>> getUserProfile() async {
    final prefs = await SharedPreferences.getInstance();
    
    return {
      'email': prefs.getString(_keyUserEmail),
      'name': prefs.getString(_keyUserName),
      'id': prefs.getString(_keyUserId),
      'last_login': prefs.getString(_keyLastLoginTime),
      'remember_me': prefs.getBool(_keyRememberMe) ?? false,
      'auto_login': prefs.getBool(_keyAutoLogin) ?? false,
    };
  }
  
  // Check session validity (checks both local storage and server)
  Future<bool> isSessionValid() async {
    final isLocallyLoggedIn = await isLoggedIn();
    if (!isLocallyLoggedIn) return false;
    
    // Optionally validate with server
    return await validateToken();
  }
}