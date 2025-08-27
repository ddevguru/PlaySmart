# 📱 Flutter Payment Integration Usage Example

This document shows how to integrate the payment system into your Flutter app.

## 🔧 Setup

### 1. Import Payment Service

```dart
import 'package:your_app/services/payment_service.dart';
```

### 2. Initialize Payment Service

```dart
class _MainScreenState extends State<MainScreen> {
  late PaymentService _paymentService;
  
  @override
  void initState() {
    super.initState();
    _paymentService = PaymentService();
  }
  
  @override
  void dispose() {
    _paymentService.dispose();
    super.dispose();
  }
}
```

## 💳 Payment Flow for Job Applications

### 1. After Job Application Submission

```dart
// After successfully submitting job application
Future<void> _handlePaymentAfterApplication({
  required int applicationId,
  required double amount,
  required String jobType, // 'local' or 'higher_package'
}) async {
  try {
    await _paymentService.processJobApplicationPayment(
      applicationId: applicationId,
      amount: amount,
      jobType: jobType,
      name: 'John Doe', // Get from user profile
      email: 'john@example.com', // Get from user profile
      contact: '+919876543210', // Get from user profile
      onSuccess: (paymentData) async {
        // Payment successful - handle success
        print('Payment successful: $paymentData');
        
        // Show success message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment successful! Application submitted.'),
            backgroundColor: Colors.green,
          ),
        );
        
        // Navigate to success screen or show confirmation
        _showPaymentSuccessDialog(paymentData);
      },
      onError: (error) {
        // Payment failed - handle error
        print('Payment failed: $error');
        
        // Show error message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment failed: $error'),
            backgroundColor: Colors.red,
          ),
        );
      },
    );
  } catch (e) {
    print('Error processing payment: $e');
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Error: $e'),
        backgroundColor: Colors.red,
      ),
    );
  }
}
```

### 2. Payment Success Dialog

```dart
void _showPaymentSuccessDialog(Map<String, dynamic> paymentData) {
  showDialog(
    context: context,
    builder: (BuildContext context) {
      return AlertDialog(
        title: Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green),
            SizedBox(width: 8),
            Text('Payment Successful!'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Your job application has been submitted successfully.'),
            SizedBox(height: 16),
            Text('Payment Details:', style: TextStyle(fontWeight: FontWeight.bold)),
            Text('Amount: ₹${paymentData['payment_amount']}'),
            Text('Order ID: ${paymentData['razorpay_order_id']}'),
            Text('Job Type: ${paymentData['job_type']}'),
            SizedBox(height: 16),
            Text('You will receive an email confirmation shortly.'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              // Navigate to home or applications list
            },
            child: Text('Continue'),
          ),
        ],
      );
    },
  );
}
```

### 3. Check Payment Status

```dart
Future<void> _checkPaymentStatus(int applicationId) async {
  try {
    final status = await _paymentService.checkPaymentStatus(
      applicationId: applicationId,
    );
    
    if (status['success']) {
      final payments = status['data']['payments'];
      if (payments.isNotEmpty) {
        final payment = payments[0];
        print('Payment Status: ${payment['payment_status']}');
        print('Amount: ₹${payment['amount']}');
        print('Date: ${payment['payment_date']}');
        
        // Update UI based on payment status
        _updatePaymentStatus(payment['payment_status']);
      }
    }
  } catch (e) {
    print('Error checking payment status: $e');
  }
}
```

## 🎯 Complete Example: Job Application with Payment

```dart
class JobApplicationScreen extends StatefulWidget {
  final Job job;
  
  const JobApplicationScreen({Key? key, required this.job}) : super(key: key);
  
  @override
  _JobApplicationScreenState createState() => _JobApplicationScreenState();
}

class _JobApplicationScreenState extends State<JobApplicationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _paymentService = PaymentService();
  
  String? _selectedPhotoPath;
  String? _selectedResumePath;
  String? _selectedDistrict;
  
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _experienceController = TextEditingController();
  final _skillsController = TextEditingController();
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Apply for ${widget.job.profile}'),
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Form fields...
              TextFormField(
                controller: _nameController,
                decoration: InputDecoration(labelText: 'Full Name'),
                validator: (value) => value?.isEmpty == true ? 'Name is required' : null,
              ),
              SizedBox(height: 16),
              
              TextFormField(
                controller: _emailController,
                decoration: InputDecoration(labelText: 'Email'),
                validator: (value) => value?.isEmpty == true ? 'Email is required' : null,
              ),
              SizedBox(height: 16),
              
              // Photo upload
              _buildFileUploadField(
                label: 'Photo',
                onFileSelected: (path) => _selectedPhotoPath = path,
                isImage: true,
              ),
              
              // Resume upload
              _buildFileUploadField(
                label: 'Resume',
                onFileSelected: (path) => _selectedResumePath = path,
                isImage: false,
              ),
              
              // District dropdown
              _buildDistrictDropdown(),
              
              SizedBox(height: 32),
              
              ElevatedButton(
                onPressed: _submitApplication,
                child: Text('Submit Application'),
                style: ElevatedButton.styleFrom(
                  padding: EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  Future<void> _submitApplication() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedPhotoPath == null || _selectedResumePath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Please upload photo and resume')),
      );
      return;
    }
    
    try {
      // Show loading
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => Center(child: CircularProgressIndicator()),
      );
      
      // Submit application
      final response = await _submitJobApplication();
      
      // Hide loading
      Navigator.of(context).pop();
      
      if (response['success']) {
        final applicationId = response['data']['application_id'];
        
        // Determine job type and amount
        final isHigherPackage = widget.job.package.contains('15LPA') || 
                               widget.job.package.contains('16LPA') ||
                               widget.job.package.contains('17LPA') ||
                               widget.job.package.contains('18LPA');
        
        final amount = isHigherPackage ? 2000.0 : 1000.0;
        final jobType = isHigherPackage ? 'higher_package' : 'local';
        
        // Show payment instructions
        _showPaymentInstructions(applicationId, amount, jobType);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Application failed: ${response['message']}')),
        );
      }
    } catch (e) {
      Navigator.of(context).pop(); // Hide loading
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }
  
  void _showPaymentInstructions(int applicationId, double amount, String jobType) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Payment Required'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('To complete your application, please pay the registration fee:'),
            SizedBox(height: 16),
            Text('Amount: ₹$amount', style: TextStyle(fontWeight: FontWeight.bold)),
            SizedBox(height: 16),
            Text('Click "Proceed to Payment" to continue.'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              _processPayment(applicationId, amount, jobType);
            },
            child: Text('Proceed to Payment'),
          ),
        ],
      ),
    );
  }
  
  Future<void> _processPayment(int applicationId, double amount, String jobType) async {
    try {
      await _paymentService.processJobApplicationPayment(
        applicationId: applicationId,
        amount: amount,
        jobType: jobType,
        name: _nameController.text,
        email: _emailController.text,
        contact: _phoneController.text,
        onSuccess: (paymentData) {
          // Payment successful
          _showPaymentSuccess(paymentData);
        },
        onError: (error) {
          // Payment failed
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Payment failed: $error'),
              backgroundColor: Colors.red,
            ),
          );
        },
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }
  
  void _showPaymentSuccess(Map<String, dynamic> paymentData) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green),
            SizedBox(width: 8),
            Text('Success!'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Your application has been submitted successfully!'),
            SizedBox(height: 16),
            Text('Payment completed: ₹${paymentData['payment_amount']}'),
            SizedBox(height: 16),
            Text('You will receive an email confirmation shortly.'),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pushReplacementNamed('/home');
            },
            child: Text('Continue'),
          ),
        ],
      ),
    );
  }
  
  // Helper methods for file upload and district selection...
  Widget _buildFileUploadField({
    required String label,
    required Function(String) onFileSelected,
    required bool isImage,
  }) {
    // Implementation for file upload field
    return Container(); // Placeholder
  }
  
  Widget _buildDistrictDropdown() {
    // Implementation for district dropdown
    return Container(); // Placeholder
  }
  
  Future<Map<String, dynamic>> _submitJobApplication() async {
    // Implementation for submitting job application
    return {}; // Placeholder
  }
}
```

## 🔑 Key Points

1. **Payment Flow**: Application → Payment Instructions → Payment Gateway → Success
2. **Amount Logic**: ₹2000 for higher package jobs (15LPA+), ₹1000 for local jobs
3. **Error Handling**: Always handle payment failures gracefully
4. **User Experience**: Show loading states and clear success/error messages
5. **Integration**: Use the `PaymentService` for all payment operations

## 🚨 Important Notes

- Always test with Razorpay test keys first
- Handle payment failures gracefully
- Store application ID for payment tracking
- Show clear instructions to users
- Implement proper error handling and logging

This integration will now properly handle the ₹1000 and ₹2000 payments for your job applications! 