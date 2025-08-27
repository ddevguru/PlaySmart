import 'dart:convert';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import 'package:http/http.dart' as http;

class PaymentService {
  static const String _baseUrl = 'https://playsmart.co.in';
  
  // Razorpay configuration
  static const String _keyId = 'rzp_test_YOUR_TEST_KEY_ID'; // Replace with your test key
  static const String _keySecret = 'YOUR_TEST_KEY_SECRET'; // Replace with your test secret
  
  late Razorpay _razorpay;
  
  PaymentService() {
    _initializeRazorpay();
  }
  
  void _initializeRazorpay() {
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
  }
  
  // Create payment integration for job application
  Future<Map<String, dynamic>> createPaymentIntegration({
    required int applicationId,
    required double amount,
    required String jobType, // 'local' or 'higher_package'
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/payment_integration.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'application_id': applicationId,
          'payment_amount': amount,
          'job_type': jobType,
        }),
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to create payment integration');
        }
      } else {
        throw Exception('Failed to create payment integration: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error creating payment integration: $e');
    }
  }
  
  // Process payment for job application
  Future<void> processJobApplicationPayment({
    required int applicationId,
    required double amount,
    required String jobType, // 'local' or 'higher_package'
    required String name,
    required String email,
    required String contact,
    required Function(Map<String, dynamic>) onSuccess,
    required Function(String) onError,
  }) async {
    try {
      // Create payment integration first
      final paymentData = await createPaymentIntegration(
        applicationId: applicationId,
        amount: amount,
        jobType: jobType,
      );
      
      // Prepare payment options
      final options = {
        'key': paymentData['key_id'],
        'amount': (amount * 100).toInt(), // Convert to paise
        'currency': 'INR',
        'name': 'PlaySmart',
        'description': paymentData['description'],
        'order_id': paymentData['razorpay_order_id'],
        'prefill': paymentData['prefill'],
        'notes': paymentData['notes'],
        'theme': {
          'color': '#3399cc',
        },
      };
      
      // Open Razorpay payment
      _razorpay.open(options);
      
      // Store callbacks for later use
      _onSuccess = onSuccess;
      _onError = onError;
      
    } catch (e) {
      onError('Payment initialization failed: $e');
    }
  }
  
  // Callback functions
  Function(Map<String, dynamic>)? _onSuccess;
  Function(String)? _onError;
  
  void _handlePaymentSuccess(PaymentSuccessResponse response) async {
    try {
      // Verify payment with backend
      final verificationResult = await _verifyPayment(response);
      
      if (verificationResult['success']) {
        _onSuccess?.call({
          'razorpay_payment_id': response.paymentId,
          'razorpay_order_id': response.orderId,
          'razorpay_signature': response.signature,
          'verification_data': verificationResult['data'],
        });
      } else {
        _onError?.call('Payment verification failed: ${verificationResult['message']}');
      }
    } catch (e) {
      _onError?.call('Payment verification error: $e');
    }
  }
  
  void _handlePaymentError(PaymentFailureResponse response) {
    _onError?.call('Payment failed: ${response.message ?? 'Unknown error'}');
  }
  
  void _handleExternalWallet(ExternalWalletResponse response) {
    _onError?.call('External wallet selected: ${response.walletName}');
  }
  
  // Verify payment with backend
  Future<Map<String, dynamic>> _verifyPayment(PaymentSuccessResponse response) async {
    try {
      final verificationResponse = await http.post(
        Uri.parse('$_baseUrl/verify_payment.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'razorpay_payment_id': response.paymentId,
          'razorpay_order_id': response.orderId,
          'razorpay_signature': response.signature,
        }),
      );
      
      if (verificationResponse.statusCode == 200) {
        return json.decode(verificationResponse.body);
      } else {
        throw Exception('Verification request failed: ${verificationResponse.statusCode}');
      }
    } catch (e) {
      throw Exception('Payment verification error: $e');
    }
  }
  
  // Submit payment to backend
  Future<Map<String, dynamic>> submitPayment({
    required int applicationId,
    required String paymentId,
    required double amount,
    required String razorpayPaymentId,
    required String razorpayOrderId,
    required String razorpaySignature,
    String? paymentMethod,
    Map<String, dynamic>? gatewayResponse,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/process_payment.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'application_id': applicationId,
          'payment_id': paymentId,
          'amount': amount,
          'razorpay_payment_id': razorpayPaymentId,
          'razorpay_order_id': razorpayOrderId,
          'razorpay_signature': razorpaySignature,
          'payment_method': paymentMethod ?? 'razorpay',
          'gateway_response': gatewayResponse ?? {},
        }),
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        throw Exception('Payment submission failed: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error submitting payment: $e');
    }
  }
  
  // Check payment status
  Future<Map<String, dynamic>> checkPaymentStatus({
    int? applicationId,
    String? paymentId,
    String? email,
  }) async {
    try {
      final queryParams = <String, String>{};
      if (applicationId != null) queryParams['application_id'] = applicationId.toString();
      if (paymentId != null) queryParams['payment_id'] = paymentId;
      if (email != null) queryParams['email'] = email;
      
      final uri = Uri.parse('$_baseUrl/check_payment_status.php').replace(queryParameters: queryParams);
      
      final response = await http.get(uri);
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        throw Exception('Status check failed: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error checking payment status: $e');
    }
  }
  
  // Process simple payment (for backward compatibility)
  Future<void> processPayment({
    required double amount,
    required String receipt,
    required String name,
    required String email,
    required String contact,
    Map<String, dynamic>? notes,
    required Function(Map<String, dynamic>) onSuccess,
    required Function(String) onError,
  }) async {
    try {
      // Create order first
      final orderData = await createOrder(
        amount: amount,
        receipt: receipt,
        notes: notes,
      );
      
      // Prepare payment options
      final options = {
        'key': _keyId,
        'amount': (amount * 100).toInt(), // Convert to paise
        'currency': 'INR',
        'name': 'PlaySmart',
        'description': 'Payment',
        'order_id': orderData['order_id'],
        'prefill': {
          'name': name,
          'email': email,
          'contact': contact,
        },
        'notes': notes ?? {},
        'theme': {
          'color': '#3399cc',
        },
      };
      
      // Open Razorpay payment
      _razorpay.open(options);
      
      // Store callbacks for later use
      _onSuccess = onSuccess;
      _onError = onError;
      
    } catch (e) {
      onError('Payment initialization failed: $e');
    }
  }
  
  // Create Razorpay order (for backward compatibility)
  Future<Map<String, dynamic>> createOrder({
    required double amount,
    required String receipt,
    Map<String, dynamic>? notes,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/create_razorpay_order.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'amount': amount,
          'receipt': receipt,
          'notes': notes ?? {},
        }),
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to create order');
        }
      } else {
        throw Exception('Failed to create order: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error creating order: $e');
    }
  }
  
  // Dispose resources
  void dispose() {
    _razorpay.clear();
  }
}

// Payment result model
class PaymentResult {
  final bool success;
  final String? message;
  final Map<String, dynamic>? data;
  final String? error;
  
  PaymentResult({
    required this.success,
    this.message,
    this.data,
    this.error,
  });
  
  factory PaymentResult.success({
    required String message,
    Map<String, dynamic>? data,
  }) {
    return PaymentResult(
      success: true,
      message: message,
      data: data,
    );
  }
  
  factory PaymentResult.error({
    required String error,
  }) {
    return PaymentResult(
      success: false,
      error: error,
    );
  }
} 