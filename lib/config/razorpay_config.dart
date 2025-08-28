class RazorpayConfig {
  // Test Keys (for development/testing)
  static const String testKey = 'rzp_test_YOUR_TEST_KEY_HERE';
  
  // Live Keys (for production)
  static const String liveKey = 'rzp_live_fgQr0ACWFbL4pN';
  
  // Current environment - change this to switch between test and live
  static const bool isProduction = true;
  
  // Get the current key based on environment
  static String get currentKey {
    return isProduction ? liveKey : testKey;
  }
  
  // Check if we're in production mode
  static bool get isLiveMode => isProduction;
} 