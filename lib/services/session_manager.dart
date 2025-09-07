import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class SessionManager {
  static const String _baseUrl = 'https://playsmart.co.in';
  
  // SharedPreferences keys
  static const String _keyToken = 'token';
  static const String _keyIsLoggedIn = 'isLoggedIn';
  static const String _keyUserEmail = 'user_email';
  static const String _keyUserName = 'user_name';
  static const String _keyUserId = 'user_id';
  static const String _keyRememberMe = 'remember_me';
  static const String _keyRememberedEmail = 'rememberedEmail';
  static const String _keyAutoLogin = 'auto_login';
  static const String _keyLastActivity = 'last_activity';
  
  // Singleton pattern
  static final SessionManager _instance = SessionManager._internal();
  factory SessionManager() => _instance;
  SessionManager._internal();
  
  // Check if user should be automatically logged in
  Future<bool> shouldAutoLogin() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      final isLoggedIn = prefs.getBool(_keyIsLoggedIn) ?? false;
      final token = prefs.getString(_keyToken);
      final autoLogin = prefs.getBool(_keyAutoLogin) ?? false;
      final rememberedEmail = prefs.getString(_keyRememberedEmail);
      
      // Check if we have all the necessary data for auto-login
      return isLoggedIn && 
             token != null && 
             token.isNotEmpty && 
             autoLogin && 
             rememberedEmail != null;
    } catch (e) {
      print('SessionManager: Error checking auto-login: $e');
      return false;
    }
  }
  
  // Validate current session
  Future<bool> isSessionValid() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(_keyToken);
      final isLoggedIn = prefs.getBool(_keyIsLoggedIn) ?? false;
      
      if (!isLoggedIn || token == null || token.isEmpty) {
        return false;
      }
      
      // Validate token with server
      return await _validateTokenWithServer(token);
    } catch (e) {
      print('SessionManager: Error validating session: $e');
      return false;
    }
  }
  
  // Validate token with server
  Future<bool> _validateTokenWithServer(String token) async {
    try {
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
      print('SessionManager: Error validating token with server: $e');
      return false;
    }
  }
  
  // Get current user info
  Future<Map<String, dynamic>?> getCurrentUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final isLoggedIn = prefs.getBool(_keyIsLoggedIn) ?? false;
      final token = prefs.getString(_keyToken);
      
      if (!isLoggedIn || token == null) {
        return null;
      }
      
      return {
        'email': prefs.getString(_keyUserEmail),
        'name': prefs.getString(_keyUserName),
        'id': prefs.getString(_keyUserId),
        'token': token,
        'rememberMe': prefs.getBool(_keyRememberMe) ?? false,
        'autoLogin': prefs.getBool(_keyAutoLogin) ?? false,
      };
    } catch (e) {
      print('SessionManager: Error getting current user: $e');
      return null;
    }
  }
  
  // Update last activity
  Future<void> updateLastActivity() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_keyLastActivity, DateTime.now().toIso8601String());
    } catch (e) {
      print('SessionManager: Error updating last activity: $e');
    }
  }
  
  // Clear session data (but keep remembered credentials if requested)
  Future<void> clearSession({bool keepRemembered = false}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      if (keepRemembered) {
        // Keep remembered credentials
        final rememberedEmail = prefs.getString(_keyRememberedEmail);
        final rememberMe = prefs.getBool(_keyRememberMe) ?? false;
        
        // Clear session data
        await prefs.remove(_keyToken);
        await prefs.setBool(_keyIsLoggedIn, false);
        await prefs.remove(_keyUserEmail);
        await prefs.remove(_keyUserName);
        await prefs.remove(_keyUserId);
        await prefs.remove(_keyLastActivity);
        await prefs.setBool(_keyAutoLogin, false);
        
        // Keep remembered data
        if (rememberMe && rememberedEmail != null) {
          await prefs.setString(_keyRememberedEmail, rememberedEmail);
          await prefs.setBool(_keyRememberMe, true);
        }
      } else {
        // Clear everything
        await prefs.remove(_keyToken);
        await prefs.setBool(_keyIsLoggedIn, false);
        await prefs.remove(_keyUserEmail);
        await prefs.remove(_keyUserName);
        await prefs.remove(_keyUserId);
        await prefs.remove(_keyRememberMe);
        await prefs.remove(_keyRememberedEmail);
        await prefs.setBool(_keyAutoLogin, false);
        await prefs.remove(_keyLastActivity);
      }
      
      print('SessionManager: Session cleared successfully');
    } catch (e) {
      print('SessionManager: Error clearing session: $e');
    }
  }
  
  // Check if session has expired (optional feature)
  Future<bool> isSessionExpired({Duration maxInactivity = const Duration(hours: 24)}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastActivity = prefs.getString(_keyLastActivity);
      
      if (lastActivity == null) return true;
      
      final lastActivityTime = DateTime.parse(lastActivity);
      final now = DateTime.now();
      final difference = now.difference(lastActivityTime);
      
      return difference > maxInactivity;
    } catch (e) {
      print('SessionManager: Error checking session expiry: $e');
      return true; // Assume expired on error
    }
  }
} 