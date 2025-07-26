import 'dart:async';
import 'dart:math';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:playsmart/splash_screen.dart';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/services.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

class ProfileScreen extends StatefulWidget {
  final String token;

  const ProfileScreen({required this.token, Key? key}) : super(key: key);

  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> with TickerProviderStateMixin {
  Map<String, dynamic>? userData;
  bool isLoading = true;
  int _failedAttempts = 0;
  bool _isProcessingPayment = false;
  String? _sessionToken;
  double? _enteredAmount;
  late Razorpay _razorpay;

  final TextEditingController _amountController = TextEditingController();
  static const String BASE_URL = 'https://playsmart.co.in';
  static const double MIN_PAYMENT_AMOUNT = 1.0;
  static const double MAX_PAYMENT_AMOUNT = 100000;
  static const int MAX_RETRY_ATTEMPTS = 3;

  late AnimationController _animationController;
  late AnimationController _floatingIconsController;
  late AnimationController _pulseController;
  late AnimationController _rotateController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  int _selectedTab = 0;

  TextStyle header = const TextStyle(fontSize: 18, fontWeight: FontWeight.bold);
  TextStyle value = const TextStyle(fontWeight: FontWeight.w400, fontSize: 14);

  @override
  void initState() {
    super.initState();
    _sessionToken = widget.token;
    _initializeAnimations();
    _initializeRazorpay();
    _fetchUserData();
  }

  void _initializeRazorpay() {
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
  }

  void _initializeAnimations() {
    _animationController = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200));
    _floatingIconsController = AnimationController(vsync: this, duration: const Duration(milliseconds: 8000))..repeat();
    _pulseController = AnimationController(vsync: this, duration: const Duration(milliseconds: 1500))..repeat(reverse: true);
    _rotateController = AnimationController(vsync: this, duration: const Duration(milliseconds: 20000))..repeat();
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(CurvedAnimation(parent: _animationController, curve: const Interval(0.0, 0.65, curve: Curves.easeOut)));
    _slideAnimation = Tween<Offset>(begin: const Offset(0, 0.3), end: Offset.zero).animate(CurvedAnimation(parent: _animationController, curve: const Interval(0.3, 1.0, curve: Curves.easeOutCubic)));
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    _floatingIconsController.dispose();
    _pulseController.dispose();
    _rotateController.dispose();
    _amountController.dispose();
    _razorpay.clear();
    super.dispose();
  }

  Future<void> _fetchUserData() async {
    setState(() => isLoading = true);
    try {
      final response = await http.get(Uri.parse('$BASE_URL/get_user_data.php?token=$_sessionToken'), headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': 'QuizMaster/1.0',
      }).timeout(const Duration(seconds: 15));
      print('User data response: ${response.statusCode} - ${response.body}');
      if (response.statusCode == 200 && response.headers['content-type']?.contains('application/json') == true) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] is Map<String, dynamic>) {
          setState(() {
            userData = data['data'];
            isLoading = false;
          });
        } else {
          throw Exception(data['message'] ?? 'Failed to fetch user data');
        }
      } else {
        throw Exception('Invalid response format or server error: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      print('Error fetching user data: $e');
      _showCustomSnackBar('Error fetching user data: $e. Please try again.', isError: true);
      setState(() => isLoading = false);
    }
  }

  bool _validatePaymentAmount(double amount) {
    if (amount < MIN_PAYMENT_AMOUNT) {
      _showCustomSnackBar('Minimum payment amount is ₹${MIN_PAYMENT_AMOUNT.toStringAsFixed(0)}', isError: true);
      return false;
    }
    if (amount > MAX_PAYMENT_AMOUNT) {
      _showCustomSnackBar('Amount exceeds limit of ₹${MAX_PAYMENT_AMOUNT.toStringAsFixed(0)}', isError: true);
      return false;
    }
    return true;
  }

  Future<Map<String, dynamic>?> _createRazorpayOrder(double amount) async {
    try {
      final response = await http.post(
        Uri.parse('$BASE_URL/create_razorpay_order.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'token': _sessionToken,
          'amount': amount * 100, // Razorpay expects amount in paise
        }),
      ).timeout(const Duration(seconds: 15));

      print('Create Razorpay order response: ${response.statusCode} - ${response.body}');
      if (response.statusCode == 200 && response.headers['content-type']?.contains('application/json') == true) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
        _showCustomSnackBar('Failed to create order: ${data['message'] ?? 'Unknown error'}', isError: true);
        return null;
      } else {
        _showCustomSnackBar('Server error: ${response.statusCode}', isError: true);
        return null;
      }
    } catch (e) {
      print('Error creating Razorpay order: $e');
      _showCustomSnackBar('Network error: $e', isError: true);
      return null;
    }
  }

  Future<bool> _updateWalletBalance(String paymentId, String orderId, String signature) async {
    try {
      final payload = {
        'token': _sessionToken,
        'razorpay_payment_id': paymentId,
        'razorpay_order_id': orderId,
        'razorpay_signature': signature,
      };
      print('Sending payload to update_wallet.php: $payload');

      final response = await http.post(
        Uri.parse('$BASE_URL/update_wallet.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 15));

      print('Update wallet response: ${response.statusCode} - ${response.body}');
      if (response.statusCode == 200 && response.headers['content-type']?.contains('application/json') == true) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          print('Wallet update successful, new balance: ${data['new_balance']}');
          return true;
        }
        print('Wallet update failed: ${data['message']}');
        return false;
      }
      print('Invalid response format: ${response.statusCode} - ${response.body}');
      return false;
    } catch (e) {
      print('Error updating wallet: $e');
      return false;
    }
  }

  void _initiatePayment(double amount) async {
    if (_failedAttempts >= MAX_RETRY_ATTEMPTS) {
      _showCustomSnackBar('Too many failed attempts. Please try again later.', isError: true);
      return;
    }

    if (_isProcessingPayment) {
      _showCustomSnackBar('Payment is already in progress. Please wait.', isError: true);
      return;
    }

    if (!_validatePaymentAmount(amount)) return;

    setState(() {
      _isProcessingPayment = true;
      _enteredAmount = amount;
    });
    _showLoadingDialog('Initiating payment...');

    try {
      final orderData = await _createRazorpayOrder(amount);
      if (orderData == null) {
        setState(() => _isProcessingPayment = false);
        Navigator.pop(context);
        return;
      }

      var options = {
        'key': 'rzp_live_bTRCrWs2VHLi7o', // Replace with your Razorpay Key ID
        'amount': (amount * 100).toInt(), // Amount in paise
        'order_id': orderData['order_id'],
        'name': 'Esportswala',
        'description': 'Wallet Top-up',
        'prefill': {
          'contact': userData?['phone'] ?? '',
          'email': userData?['email'] ?? '',
        },
        'theme': {
          'color': '#6A11CB'
        }
      };

      _razorpay.open(options);
    } catch (e) {
      print('Payment Error: $e');
      _showCustomSnackBar('Payment initiation failed: $e', isError: true);
      setState(() => _isProcessingPayment = false);
      Navigator.pop(context);
    }
  }

  void _handlePaymentSuccess(PaymentSuccessResponse response) async {
    Navigator.pop(context); // Dismiss "Initiating payment..." dialog
    _showLoadingDialog('Verifying payment...');

    bool success = false;
    try {
      success = await _updateWalletBalance(
        response.paymentId!,
        response.orderId!,
        response.signature!,
      );
      if (success) {
        await _fetchUserData();
        Navigator.pop(context); // Dismiss "Verifying payment..." dialog
        _showSuccessDialog(_enteredAmount!, userData?['wallet_balance']?.toDouble() ?? 0.0);
      } else {
        _showCustomSnackBar('Failed to update wallet. Please try again.', isError: true);
      }
    } catch (e) {
      print('Error in payment success handling: $e');
      _showCustomSnackBar('Error verifying payment: $e', isError: true);
    } finally {
      setState(() {
        _isProcessingPayment = false;
        _failedAttempts = success ? 0 : _failedAttempts + 1;
        _enteredAmount = null;
        _amountController.clear();
      });
      Navigator.pop(context); // Ensure dialog is dismissed
    }
  }

  void _handlePaymentError(PaymentFailureResponse response) {
    Navigator.pop(context);
    _showCustomSnackBar('Payment failed: ${response.message}', isError: true);
    setState(() {
      _isProcessingPayment = false;
      _failedAttempts++;
    });
  }

  void _handleExternalWallet(ExternalWalletResponse response) {
    _showCustomSnackBar('External wallet selected: ${response.walletName}');
  }

  void _showCustomSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(isError ? Icons.error_outline : Icons.check_circle_outline, color: Colors.white),
            SizedBox(width: 10),
            Expanded(child: Text(message, style: GoogleFonts.poppins(fontSize: 14))),
          ],
        ),
        behavior: SnackBarBehavior.floating,
        backgroundColor: isError ? Colors.red[700] : Colors.green[600],
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        duration: const Duration(seconds: 5),
        action: SnackBarAction(
          label: 'OK',
          textColor: Colors.white,
          onPressed: () => ScaffoldMessenger.of(context).hideCurrentSnackBar(),
        ),
      ),
    );
  }

  Widget _displayTransactionData(String title, String body) {
    return Padding(
      padding: const EdgeInsets.all(8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text("$title: ", style: header),
          Flexible(child: Text(body, style: value)),
        ],
      ),
    );
  }

  void _showDepositDialog() {
    if (_isProcessingPayment) {
      _showCustomSnackBar('Payment is already in progress. Please wait.', isError: true);
      return;
    }

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.amber.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.account_balance_wallet,
                  color: Colors.amber,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Add Money to Wallet',
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextField(
                controller: _amountController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'Amount (₹)',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  prefixIcon: Icon(Icons.currency_rupee, color: Colors.blue),
                  filled: true,
                  fillColor: Colors.grey[50],
                ),
                style: GoogleFonts.poppins(fontSize: 14),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                _amountController.clear();
                Navigator.pop(context);
              },
              child: Text(
                'Cancel',
                style: GoogleFonts.poppins(
                  color: Colors.red,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () {
                final amount = double.tryParse(_amountController.text);
                if (amount != null && _validatePaymentAmount(amount)) {
                  Navigator.pop(context);
                  _initiatePayment(amount);
                } else {
                  _showCustomSnackBar(
                    'Please enter a valid amount between ₹$MIN_PAYMENT_AMOUNT and ₹$MAX_PAYMENT_AMOUNT',
                    isError: true,
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blue[800],
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              ),
              child: Text(
                'Proceed',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  void _showLoadingDialog(String message) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const CircularProgressIndicator(color: Colors.blue),
                const SizedBox(height: 15),
                Text(
                  message,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    color: Colors.black,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showSuccessDialog(double amount, double newBalance) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Colors.green[400]!, Colors.green[600]!],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle, color: Colors.white, size: 50),
              ),
              const SizedBox(height: 20),
              Text(
                'Payment Successful!',
                style: GoogleFonts.poppins(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.green[700],
                ),
              ),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.green[50],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Text(
                      '₹${amount.toStringAsFixed(2)} added to your wallet',
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'New Balance: ₹${newBalance.toStringAsFixed(2)}',
                      style: GoogleFonts.poppins(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.green[700],
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[600],
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 30, vertical: 12),
                  elevation: 2,
                ),
                child: Text(
                  'Continue',
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showWithdrawForm() {
    final TextEditingController amountController = TextEditingController();
    final TextEditingController bankNameController = TextEditingController();
    final TextEditingController accountNumberController = TextEditingController();
    final TextEditingController ifscCodeController = TextEditingController();
    final TextEditingController upiIdController = TextEditingController();

    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.account_balance,
                  color: Colors.blue,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Withdrawal Request',
                  style: GoogleFonts.poppins(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildWithdrawField(
                  controller: amountController,
                  label: 'Amount (₹)',
                  icon: Icons.currency_rupee,
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                _buildWithdrawField(
                  controller: bankNameController,
                  label: 'Bank Name',
                  icon: Icons.account_balance,
                ),
                const SizedBox(height: 12),
                _buildWithdrawField(
                  controller: accountNumberController,
                  label: 'Account Number',
                  icon: Icons.credit_card,
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                _buildWithdrawField(
                  controller: ifscCodeController,
                  label: 'IFSC Code',
                  icon: Icons.code,
                ),
                const SizedBox(height: 12),
                _buildWithdrawField(
                  controller: upiIdController,
                  label: 'UPI ID (Optional)',
                  icon: Icons.payment,
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(
                'Cancel',
                style: GoogleFonts.poppins(
                  color: Colors.red,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () {
                final amount = double.tryParse(amountController.text);
                if (amount != null &&
                    amount > 0 &&
                    bankNameController.text.isNotEmpty &&
                    accountNumberController.text.isNotEmpty &&
                    ifscCodeController.text.isNotEmpty) {
                  _submitWithdrawalRequest(
                    amount,
                    bankNameController.text,
                    accountNumberController.text,
                    ifscCodeController.text,
                    upiIdController.text,
                  );
                  Navigator.pop(context);
                } else {
                  _showCustomSnackBar(
                    'Please fill all required fields correctly',
                    isError: true,
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blue,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              ),
              child: Text(
                'Submit Request',
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildWithdrawField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboardType,
  }) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        labelText: label,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        prefixIcon: Icon(icon, color: Colors.blue),
        filled: true,
        fillColor: Colors.grey[50],
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Colors.blue, width: 2),
        ),
      ),
      style: GoogleFonts.poppins(fontSize: 14),
    );
  }

  Future<void> _submitWithdrawalRequest(
    double amount,
    String bankName,
    String accountNumber,
    String ifscCode,
    String upiId,
  ) async {
    try {
      // Validate IFSC code format
      final ifscRegex = RegExp(r'^[A-Z]{4}0[A-Z0-9]{6}$');
      if (!ifscRegex.hasMatch(ifscCode)) {
        _showCustomSnackBar(
          'Invalid IFSC code format. Use format like SBIN0001234.',
          isError: true,
        );
        return;
      }

      final requestBody = jsonEncode({
        'token': _sessionToken,
        'amount': amount,
        'bank_name': bankName,
        'account_number': accountNumber,
        'ifsc_code': ifscCode,
        'upi_id': upiId.isEmpty ? null : upiId,
      });
      print('Withdrawal request: $requestBody');
      final response = await http.post(
        Uri.parse('$BASE_URL/withdraw.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: requestBody,
      );
      print('Withdrawal response: ${response.statusCode} - ${response.body}');
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        _showCustomSnackBar('Withdrawal request submitted successfully');
        await _fetchUserData(); // Refresh user data to update wallet balance
      } else {
        _showCustomSnackBar(
          'Failed to submit withdrawal request: ${data['message'] ?? 'Unknown error'}',
          isError: true,
        );
      }
    } catch (e) {
      print('Error submitting withdrawal request: $e');
      _showCustomSnackBar(
        'Error submitting withdrawal request: $e',
        isError: true,
      );
    }
  }

  Future<void> _logout() async {
    try {
      // Show loading dialog
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return Dialog(
            backgroundColor: Colors.transparent,
            elevation: 0,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const CircularProgressIndicator(color: Colors.blue),
                const SizedBox(height: 15),
                Text(
                  'Logging out...',
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          );
        },
      );

      // Check internet connectivity
      var connectivityResult = await Connectivity().checkConnectivity();
      if (connectivityResult == ConnectivityResult.none) {
        Navigator.pop(context);
        print('Logout: No internet connection');
        _showCustomSnackBar('No internet connection', isError: true);
        return;
      }

      // Send POST request with token in body
      print(
          'Logout: Sending POST request to $BASE_URL/logout.php with token: $_sessionToken');
      final response = await http.post(
        Uri.parse('$BASE_URL/logout.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json',
        },
        body: {
          'token': _sessionToken,
        },
      ).timeout(const Duration(seconds: 10));

      print('Logout response: ${response.statusCode} - ${response.body}');
      Navigator.pop(context); // Dismiss loading dialog

      // Parse response
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          // Show success dialog
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (BuildContext context) {
              return Dialog(
                backgroundColor: Colors.transparent,
                elevation: 0,
                child: Container(
                  width: 200,
                  height: 200,
                  color: Colors.green[50],
                  child: const Icon(
                    Icons.check_circle,
                    color: Colors.green,
                    size: 100,
                  ),
                ),
              );
            },
          );
          await Future.delayed(const Duration(milliseconds: 1500));

          // Clear SharedPreferences
          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setBool('isLoggedIn', false);
          await prefs.remove('token');
          print('Logout: Token and login status cleared');

          // Navigate to splash screen
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => SplashScreen()),
          );
        } else {
          _showCustomSnackBar(data['message'] ?? 'Logout failed', isError: true);
        }
      } else {
        _showCustomSnackBar('Logout failed: HTTP ${response.statusCode}',
            isError: true);
      }
    } catch (e) {
      Navigator.pop(context);
      print('Error during logout: $e');
      _showCustomSnackBar('Error during logout: $e', isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
          ),
          ...List.generate(8, (index) {
            return Positioned(
              top: 100 + (index * 80),
              left: (index % 2 == 0) ? -20 : null,
              right: (index % 2 == 1) ? -20 : null,
              child: AnimatedBuilder(
                animation: _floatingIconsController,
                builder: (context, child) {
                  return Transform.translate(
                    offset: Offset(
                      sin((_floatingIconsController.value * 2 * pi) + index) * 30,
                      cos((_floatingIconsController.value * 2 * pi) + index + 1) *
                          20,
                    ),
                    child: Opacity(
                      opacity: 0.15,
                      child: _buildFloatingIcon(index),
                    ),
                  );
                },
              ),
            );
          }),
          Positioned(
            top: -100,
            right: -100,
            child: AnimatedBuilder(
              animation: _rotateController,
              builder: (context, child) {
                return Transform.rotate(
                  angle: _rotateController.value * 2 * pi,
                  child: Container(
                    width: 300,
                    height: 300,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: RadialGradient(
                        colors: [Colors.purple, Colors.transparent],
                        stops: [0.2, 1.0],
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
          SafeArea(
            child: isLoading
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const CircularProgressIndicator(color: Colors.blue),
                        const SizedBox(height: 20),
                        Text(
                          'Loading Profile...',
                          style: GoogleFonts.poppins(
                            color: Colors.white,
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                  )
                : userData == null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.error_outline,
                              color: Colors.white,
                              size: 50,
                            ),
                            const SizedBox(height: 20),
                            Text(
                              'Failed to load profile data.',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 16,
                              ),
                            ),
                            const SizedBox(height: 20),
                            ElevatedButton(
                              onPressed: _fetchUserData,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.amber,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                              ),
                              child: Text(
                                'Retry',
                                style: GoogleFonts.poppins(
                                  color: Colors.black,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    : SingleChildScrollView(
                        physics: const BouncingScrollPhysics(),
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  FadeTransition(
                                    opacity: _fadeAnimation,
                                    child: _buildAnimatedIconButton(
                                      icon: Icons.arrow_back_ios,
                                      onPressed: () {
                                        HapticFeedback.lightImpact();
                                        Navigator.pop(context);
                                      },
                                    ),
                                  ),
                                  const Spacer(),
                                  FadeTransition(
                                    opacity: _fadeAnimation,
                                    child: _buildAnimatedIconButton(
                                      icon: Icons.refresh,
                                      onPressed: () {
                                        HapticFeedback.lightImpact();
                                        _fetchUserData();
                                      },
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 20),
                              FadeTransition(
                                opacity: _fadeAnimation,
                                child: SlideTransition(
                                  position: _slideAnimation,
                                  child: _buildProfileHeader(),
                                ),
                              ),
                              const SizedBox(height: 30),
                              FadeTransition(
                                opacity: _fadeAnimation,
                                child: SlideTransition(
                                  position: Tween<Offset>(
                                    begin: const Offset(0, 0.3),
                                    end: Offset.zero,
                                  ).animate(
                                    CurvedAnimation(
                                      parent: _animationController,
                                      curve: const Interval(
                                        0.4,
                                        0.7,
                                        curve: Curves.easeOutCubic,
                                      ),
                                    ),
                                  ),
                                  child: _buildTabSelector(),
                                ),
                              ),
                              const SizedBox(height: 25),
                              FadeTransition(
                                opacity: _fadeAnimation,
                                child: SlideTransition(
                                  position: Tween<Offset>(
                                    begin: const Offset(0, 0.3),
                                    end: Offset.zero,
                                  ).animate(
                                    CurvedAnimation(
                                      parent: _animationController,
                                      curve: const Interval(
                                        0.5,
                                        0.8,
                                        curve: Curves.easeOutCubic,
                                      ),
                                    ),
                                  ),
                                  child: _buildTabContent(),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileHeader() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            Colors.purple.withOpacity(0.7),
            Colors.blue.withOpacity(0.7)
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              AnimatedBuilder(
                animation: _pulseController,
                builder: (context, child) {
                  return Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: SweepGradient(
                        colors: const [
                          Colors.purple,
                          Colors.blue,
                          Colors.cyan,
                          Colors.green,
                          Colors.yellow,
                          Colors.orange,
                          Colors.red,
                          Colors.purple
                        ],
                        stops: const [0.0, 0.1, 0.3, 0.4, 0.6, 0.7, 0.9, 1.0],
                        startAngle: 0,
                        endAngle: pi * 2,
                        transform: GradientRotation(_pulseController.value * pi * 2),
                      ),
                      boxShadow: const [
                        BoxShadow(
                          color: Colors.black26,
                          blurRadius: 10,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                    child: const CircleAvatar(
                      radius: 40,
                      backgroundColor: Colors.white24,
                      child: Icon(
                        Icons.person,
                        size: 40,
                        color: Colors.white,
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      userData?['username'] ?? 'User',
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        shadows: const [
                          Shadow(
                            color: Colors.black45,
                            offset: Offset(0, 2),
                            blurRadius: 4,
                          ),
                        ],
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      userData?['email'] ?? 'email@example.com',
                      style: GoogleFonts.poppins(
                        color: Colors.white.withOpacity(0.8),
                        fontSize: 14,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 5),
                    Row(
                      children: [
                        Icon(
                          Icons.phone,
                          color: Colors.white.withOpacity(0.7),
                          size: 14,
                        ),
                        const SizedBox(width: 5),
                        Expanded(
                          child: Text(
                            userData?['phone'] ?? 'N/A',
                            style: GoogleFonts.poppins(
                              color: Colors.white70,
                              fontSize: 14,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: Colors.black26,
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: const BoxDecoration(
                        color: Colors.blue,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.account_balance_wallet,
                        color: Colors.white,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 15),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Wallet Balance',
                          style: GoogleFonts.poppins(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          '₹${userData?['wallet_balance']?.toString() ?? '0'}',
                          style: GoogleFonts.poppins(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _buildWalletButton(
                      label: 'ADD MONEY',
                      icon: Icons.add,
                      onTap: () {
                        HapticFeedback.mediumImpact();
                        _showDepositDialog();
                      },
                    ),
                    const SizedBox(width: 10),
                    _buildWalletButton(
                      label: 'WITHDRAW',
                      icon: Icons.arrow_downward,
                      onTap: () {
                        HapticFeedback.mediumImpact();
                        _showWithdrawForm();
                      },
                      isSecondary: true,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabSelector() {
    return Container(
      height: 50,
      decoration: BoxDecoration(
        color: Colors.white10,
        borderRadius: BorderRadius.circular(30),
      ),
      child: Row(
        children: [
          _buildTabButton(
            icon: Icons.person,
            label: 'Profile',
            index: 0,
          ),
        ],
      ),
    );
  }

  Widget _buildTabButton({
    required IconData icon,
    required String label,
    required int index,
  }) {
    final bool isSelected = _selectedTab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() => _selectedTab = index);
          HapticFeedback.selectionClick();
        },
        child: Container(
          decoration: BoxDecoration(
            color: isSelected ? Colors.white24 : Colors.transparent,
            borderRadius: BorderRadius.circular(30),
          ),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            padding: const EdgeInsets.symmetric(vertical: 5),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  icon,
                  color: isSelected ? Colors.amber : Colors.white70,
                  size: 20,
                ),
                const SizedBox(height: 2),
                Text(
                  label,
                  style: GoogleFonts.poppins(
                    color: isSelected ? Colors.white : Colors.white70,
                    fontSize: 12,
                    fontWeight:
                        isSelected ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTabContent() {
    return _buildProfileTab();
  }

  Widget _buildProfileTab() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Personal Information'),
        const SizedBox(height: 15),
        _buildProfileField(
          label: 'Phone',
          value: userData?['phone'] ?? 'N/A',
          icon: Icons.phone,
        ),
        const SizedBox(height: 15),
        _buildProfileField(
          label: 'Helpline Number',
          value: '+91 94209-16672',
          icon: Icons.support_agent,
          onPressed: () async {
            final Uri phoneUri = Uri(scheme: 'tel', path: '+919420916672');
            if (await canLaunchUrl(phoneUri)) {
              await launchUrl(phoneUri);
              _showCustomSnackBar('Initiating call to helpline');
              HapticFeedback.mediumImpact();
            } else {
              _showCustomSnackBar('Unable to initiate call', isError: true);
            }
          },
          iconAction: Icons.call,
        ),
        const SizedBox(height: 20),
        _buildSectionTitle('Referral Information'),
        const SizedBox(height: 15),
        _buildProfileField(
          label: 'Referral Code',
          value: userData?['referral_code'] ?? 'N/A',
          icon: Icons.card_giftcard,
          onPressed: () {
            if (userData?['referral_code'] != null) {
              Clipboard.setData(
                  ClipboardData(text: userData!['referral_code']));
              _showCustomSnackBar('Referral code copied to clipboard');
              HapticFeedback.mediumImpact();
            }
          },
          iconAction: Icons.copy,
        ),
        const SizedBox(height: 15),
        _buildProfileField(
          label: 'Referral Count',
          value: userData?['referral_count']?.toString() ?? '0',
          icon: Icons.people,
        ),
        const SizedBox(height: 30),
        _buildActionButton(
          label: 'Share App',
          icon: Icons.share,
          color: Colors.blue[700]!,
          onPressed: () async {
            final String message = 'Check out PlaySmart! Download now: https://esportswala.app';
            final Uri whatsappUri = Uri.parse('https://wa.me/?text=${Uri.encodeComponent(message)}');
            if (await canLaunchUrl(whatsappUri)) {
              await launchUrl(whatsappUri, mode: LaunchMode.externalApplication);
              _showCustomSnackBar('Opening WhatsApp to share PlaySmart app');
              HapticFeedback.mediumImpact();
            } else {
              _showCustomSnackBar('WhatsApp is not installed or cannot be opened', isError: true);
            }
          },
        ),
        const SizedBox(height: 15),
        _buildActionButton(
          label: 'Logout',
          icon: Icons.logout,
          color: Colors.red[700]!,
          onPressed: () {
            HapticFeedback.mediumImpact();
            _logout();
          },
        ),
      ],
    );
  }

  Widget _buildSectionTitle(String title) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: GoogleFonts.poppins(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 5),
        Container(
          width: 40,
          height: 3,
          decoration: BoxDecoration(
            color: Colors.orange,
            borderRadius: BorderRadius.circular(10),
          ),
        ),
      ],
    );
  }

  Widget _buildProfileField({
    required String label,
    required String value,
    required IconData icon,
    VoidCallback? onPressed,
    IconData? iconAction,
  }) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white10,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: Colors.white10,
              shape: BoxShape.circle,
            ),
            child: Icon(
              icon,
              color: Colors.white,
              size: 20,
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: GoogleFonts.poppins(
                    color: Colors.white70,
                    fontSize: 14,
                  ),
                ),
                Text(
                  value,
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          if (onPressed != null && iconAction != null)
            IconButton(
              icon: Icon(
                iconAction,
                color: Colors.white70,
              ),
              onPressed: onPressed,
            ),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return InkWell(
      onTap: onPressed,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 20),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [
            BoxShadow(
              color: Colors.black26,
              blurRadius: 10,
              offset: Offset(0, 5),
            ),
          ],
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white),
            const SizedBox(width: 15),
            Text(
              label,
              style: GoogleFonts.poppins(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const Spacer(),
            const Icon(
              Icons.arrow_forward_ios,
              color: Colors.white,
              size: 16,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildWalletButton({
    required String label,
    required IconData icon,
    required VoidCallback onTap,
    bool isSecondary = false,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(30),
        child: Container(
          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: isSecondary ? Colors.transparent : Colors.amber,
            borderRadius: BorderRadius.circular(30),
            border: isSecondary
                ? Border.all(
                    color: Colors.white,
                    width: 1,
                  )
                : null,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                icon,
                color: isSecondary ? Colors.white : Colors.black,
                size: 16,
              ),
              const SizedBox(width: 8),
              Text(
                label,
                style: GoogleFonts.poppins(
                  color: isSecondary ? Colors.white : Colors.black,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFloatingIcon(int index) {
    const icons = [
      Icons.lightbulb,
      Icons.school,
      Icons.psychology,
      Icons.extension,
      Icons.star,
      Icons.auto_awesome,
    ];
    const sizes = [30.0, 40.0, 25.0, 35.0, 45.0];
    return Icon(
      icons[index % icons.length],
      color: Colors.white,
      size: sizes[index % sizes.length],
    );
  }

  Widget _buildAnimatedIconButton({
    required IconData icon,
    required VoidCallback onPressed,
  }) {
    return AnimatedBuilder(
      animation: _pulseController,
      builder: (context, child) {
        return Container(
          decoration: BoxDecoration(
            color: Colors.white24,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.white.withOpacity(0.1 + (_pulseController.value * 0.05)),
                blurRadius: 10 + (_pulseController.value * 5),
                spreadRadius: 1 + (_pulseController.value * 1),
              ),
            ],
          ),
          child: IconButton(
            icon: Icon(
              icon,
              color: Colors.white,
              size: 24,
            ),
            onPressed: onPressed,
          ),
        );
      },
    );
  }
}