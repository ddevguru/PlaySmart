import 'dart:convert';

void main() {
  print('Testing payment options serialization...');
  
  // Test 1: Basic options object
  try {
    final basicOptions = {
      'key': 'rzp_live_fgQr0ACWFbL4pN',
      'amount': 200,
      'currency': 'INR',
      'name': 'PlaySmart Services',
      'description': 'Job Application Fee for Test Job',
      'order_id': 'order_test_123',
      'prefill': {
        'contact': '',
        'email': '',
      },
      'external': {
        'wallets': ['paytm']
      }
    };
    
    final jsonString = jsonEncode(basicOptions);
    print('✅ Basic options serialized successfully: $jsonString');
  } catch (e) {
    print('❌ Basic options serialization failed: $e');
  }
  
  // Test 2: Options with explicit types
  try {
    final Map<String, dynamic> typedOptions = <String, dynamic>{
      'key': 'rzp_live_fgQr0ACWFbL4pN',
      'amount': 200,
      'currency': 'INR',
      'name': 'PlaySmart Services',
      'description': 'Job Application Fee for Test Job',
      'order_id': 'order_test_123',
      'prefill': <String, String>{
        'contact': '',
        'email': '',
      },
      'external': <String, List<String>>{
        'wallets': <String>['paytm']
      }
    };
    
    final jsonString = jsonEncode(typedOptions);
    print('✅ Typed options serialized successfully: $jsonString');
  } catch (e) {
    print('❌ Typed options serialization failed: $e');
  }
  
  // Test 3: Options with string conversion
  try {
    final orderId = 'order_test_123';
    final amount = 200.0;
    
    final Map<String, dynamic> convertedOptions = <String, dynamic>{
      'key': 'rzp_live_fgQr0ACWFbL4pN',
      'amount': (amount * 100).toInt(),
      'currency': 'INR',
      'name': 'PlaySmart Services',
      'description': 'Job Application Fee for Test Job',
      'order_id': orderId.toString(),
      'prefill': <String, String>{
        'contact': '',
        'email': '',
      },
      'external': <String, List<String>>{
        'wallets': <String>['paytm']
      }
    };
    
    final jsonString = jsonEncode(convertedOptions);
    print('✅ Converted options serialized successfully: $jsonString');
  } catch (e) {
    print('❌ Converted options serialization failed: $e');
  }
  
  print('Test completed.');
} 