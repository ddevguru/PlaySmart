# 🚀 PlaySmart Payment Integration with Razorpay

This document explains how to set up and use the complete payment integration system for PlaySmart job applications.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Database Setup](#database-setup)
4. [Razorpay Configuration](#razorpay-configuration)
5. [File Structure](#file-structure)
6. [Setup Instructions](#setup-instructions)
7. [Testing](#testing)
8. [API Endpoints](#api-endpoints)
9. [Flutter Integration](#flutter-integration)
10. [Troubleshooting](#troubleshooting)

## 🎯 Overview

The payment integration system allows users to:
- Submit job applications with photo and resume uploads
- Pay application fees (₹1000 for Local Jobs, ₹2000 for Higher Package Jobs)
- Complete payments securely through Razorpay
- Track payment status and application progress
- Receive email confirmations

## 🔧 Prerequisites

- PHP 7.4+ with PDO MySQL extension
- MySQL/MariaDB database
- Razorpay account and API keys
- Flutter app with internet permission
- Web server (Apache/Nginx)

## 🗄️ Database Setup

### 1. Create Payment Tracking Table

Run the SQL from `payment_tracking_table.sql`:

```sql
CREATE TABLE IF NOT EXISTS `payment_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `gateway_response` text,
  `error_message` text,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` datetime DEFAULT NULL,
  `refund_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `application_id` (`application_id`),
  KEY `razorpay_payment_id` (`razorpay_payment_id`),
  KEY `payment_status` (`payment_status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_payment_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Ensure Required Directories Exist

```bash
mkdir -p Admin/uploads/photos
mkdir -p Admin/uploads/resumes
chmod 755 Admin/uploads/photos
chmod 755 Admin/uploads/resumes
```

## 🔑 Razorpay Configuration

### 1. Get API Keys

1. Sign up at [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Go to Settings → API Keys
3. Generate new API key pair
4. Copy the Key ID and Key Secret

### 2. Update Configuration

Edit `razorpay_config.php`:

```php
// Test Mode (set to false for production)
define('RAZORPAY_TEST_MODE', true);

// Test Mode Keys
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_ACTUAL_TEST_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_ACTUAL_TEST_KEY_SECRET');

// Production Mode Keys (when ready)
// define('RAZORPAY_KEY_ID', 'rzp_live_YOUR_ACTUAL_LIVE_KEY_ID');
// define('RAZORPAY_KEY_SECRET', 'YOUR_ACTUAL_LIVE_KEY_SECRET');
```

### 3. Install Razorpay PHP SDK

```bash
composer require razorpay/razorpay
```

Or download manually from [GitHub](https://github.com/razorpay/razorpay-php).

## 📁 File Structure

```
playsmart/
├── payment_tracking_table.sql          # Database table creation
├── razorpay_config.php                 # Razorpay configuration and helpers
├── create_razorpay_order.php           # Create Razorpay orders
├── verify_payment.php                  # Verify payment signatures
├── process_payment.php                 # Process completed payments
├── check_payment_status.php            # Check payment status
├── payment_dashboard.php               # Admin payment dashboard
├── submit_job_application_with_files.php # Job application submission
├── lib/
│   └── services/
│       └── payment_service.dart        # Flutter payment service
└── PAYMENT_INTEGRATION_README.md       # This file
```

## ⚙️ Setup Instructions

### 1. Server Setup

1. Upload all PHP files to your web server
2. Ensure `db_config.php` has correct database credentials
3. Set proper file permissions for upload directories
4. Install Razorpay PHP SDK

### 2. Flutter Setup

1. Add Razorpay dependency (already in `pubspec.yaml`)
2. Import `PaymentService` in your main screen
3. Initialize payment service
4. Handle payment callbacks

### 3. Android Configuration

Add to `android/app/src/main/AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.INTERNET" />
```

### 4. iOS Configuration

Add to `ios/Runner/Info.plist`:

```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
```

## 🧪 Testing

### 1. Test Database Connection

Visit: `https://playsmart.co.in/test_db_connection.php`

### 2. Test Payment Flow

1. Submit a job application
2. Complete payment with test card
3. Check payment status
4. Verify database records

### 3. Test Cards (Test Mode)

- **Success**: 4111 1111 1111 1111
- **Failure**: 4000 0000 0000 0002
- **CVV**: Any 3 digits
- **Expiry**: Any future date

## 🌐 API Endpoints

### 1. Create Razorpay Order
```
POST /create_razorpay_order.php
{
  "amount": 2000,
  "receipt": "order_123",
  "notes": {"job_id": "123"}
}
```

### 2. Verify Payment
```
POST /verify_payment.php
{
  "razorpay_payment_id": "pay_xxx",
  "razorpay_order_id": "order_xxx",
  "razorpay_signature": "xxx"
}
```

### 3. Process Payment
```
POST /process_payment.php
{
  "application_id": 123,
  "payment_id": "pay_xxx",
  "amount": 2000,
  "razorpay_payment_id": "pay_xxx",
  "razorpay_order_id": "order_xxx",
  "razorpay_signature": "xxx"
}
```

### 4. Check Payment Status
```
GET /check_payment_status.php?application_id=123
GET /check_payment_status.php?payment_id=pay_xxx
GET /check_payment_status.php?email=user@example.com
```

## 📱 Flutter Integration

### 1. Initialize Payment Service

```dart
final paymentService = PaymentService();

// Dispose when done
@override
void dispose() {
  paymentService.dispose();
  super.dispose();
}
```

### 2. Process Payment

```dart
await paymentService.processPayment(
  amount: 2000.0,
  receipt: 'order_${DateTime.now().millisecondsSinceEpoch}',
  name: 'John Doe',
  email: 'john@example.com',
  contact: '+919876543210',
  notes: {'job_id': job.id.toString()},
  onSuccess: (paymentData) async {
    // Handle successful payment
    final result = await paymentService.submitPayment(
      applicationId: applicationId,
      paymentId: paymentId,
      amount: 2000.0,
      razorpayPaymentId: paymentData['razorpay_payment_id'],
      razorpayOrderId: paymentData['razorpay_order_id'],
      razorpaySignature: paymentData['razorpay_signature'],
    );
    
    if (result['success']) {
      // Show success message
    }
  },
  onError: (error) {
    // Handle payment error
    print('Payment failed: $error');
  },
);
```

### 3. Check Payment Status

```dart
final status = await paymentService.checkPaymentStatus(
  applicationId: applicationId,
);

if (status['success']) {
  final payments = status['data']['payments'];
  // Handle payment status
}
```

## 🔍 Admin Dashboard

Access the payment dashboard at: `https://playsmart.co.in/payment_dashboard.php`

Features:
- View all payment records
- Check payment statistics
- Process refunds
- Export payment data
- Monitor application status

## 🚨 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check `db_config.php` credentials
   - Verify database server is running
   - Check table exists

2. **Payment Signature Verification Failed**
   - Verify Razorpay API keys
   - Check webhook configuration
   - Ensure proper signature generation

3. **File Upload Failed**
   - Check directory permissions
   - Verify PHP upload limits
   - Check disk space

4. **Flutter Payment Not Working**
   - Verify internet permission
   - Check Razorpay key configuration
   - Ensure proper callback handling

### Debug Logs

Check these log files for detailed information:
- `debug_log.txt` - General application logs
- `payment_debug_log.txt` - Payment processing logs
- `razorpay_order_log.txt` - Order creation logs
- `payment_verification_log.txt` - Payment verification logs
- `payment_status_log.txt` - Status check logs

### Support

For technical support:
1. Check debug logs first
2. Verify all configuration files
3. Test with sample data
4. Contact Razorpay support for payment issues

## 🔐 Security Considerations

1. **API Key Protection**
   - Never commit API keys to version control
   - Use environment variables in production
   - Rotate keys regularly

2. **Payment Verification**
   - Always verify payment signatures
   - Use webhooks for real-time updates
   - Implement proper error handling

3. **Data Validation**
   - Validate all input data
   - Sanitize database queries
   - Implement rate limiting

## 📈 Production Deployment

1. **Switch to Live Mode**
   - Update `RAZORPAY_TEST_MODE` to `false`
   - Use live API keys
   - Test thoroughly in staging

2. **SSL Certificate**
   - Ensure HTTPS is enabled
   - Valid SSL certificate required
   - Update callback URLs

3. **Monitoring**
   - Set up payment monitoring
   - Configure alerts for failures
   - Regular backup of payment data

## 🎉 Success!

Your PlaySmart payment integration is now complete! Users can:
- Submit job applications with documents
- Pay fees securely through Razorpay
- Track application and payment status
- Receive email confirmations

The system automatically handles:
- File uploads and storage
- Payment processing and verification
- Database record management
- Email notifications
- Payment status tracking

For any questions or issues, refer to the troubleshooting section or check the debug logs. 