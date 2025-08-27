# Email & Status Button Troubleshooting Guide - PlaySmart

## 🚨 Current Issues Identified

### 1. **Email Not Being Sent**
- Users see "application submitted, check your mail for details"
- But no email arrives in their inbox
- Email functionality is failing silently

### 2. **Status Button Not Converting**
- "Apply" button remains as "Apply Now" after payment
- Should convert to status button showing "Accepted"
- Local status update is not persisting

## 🔍 Step-by-Step Troubleshooting

### **Step 1: Test Basic Email Functionality**

Run the simple email test:
```bash
php test_email_simple.php
```

**Expected Output:**
```
=== SIMPLE EMAIL TEST ===
✅ Mail function is available
Sendmail path: /usr/sbin/sendmail
SMTP host: localhost
SMTP port: 25
Attempting to send test email to: test@example.com
✅ Test email sent successfully!
```

**If Failed:**
- Contact your hosting provider to enable mail functionality
- Check if mail server is running
- Verify firewall settings

### **Step 2: Check Server Mail Configuration**

**Common Issues:**
1. **Mail function disabled** - Hosting provider blocks outgoing mail
2. **SMTP not configured** - No mail server available
3. **Firewall blocking** - Port 25/587 blocked
4. **DNS issues** - Mail server not resolving

**Solutions:**
- Contact hosting provider
- Use external SMTP service (Gmail, SendGrid, Mailgun)
- Configure PHPMailer as alternative

### **Step 3: Test Payment Flow**

1. **Complete a test payment** in the Flutter app
2. **Check server logs** for email attempts
3. **Verify database records** are created
4. **Check email_logs folder** for fallback emails

### **Step 4: Verify Status Button Logic**

**Check Flutter app logs:**
```
DEBUG: Setting job [ID] status to accepted
DEBUG: userJobApplications after update: {[ID]: accepted}
DEBUG: UI updated with new application status
```

**If missing:**
- Payment success handler not triggered
- Local status not being set
- UI not refreshing

## 🛠️ Immediate Fixes

### **Fix 1: Enable Alternative Email Methods**

The system now has fallback email methods:
1. **Primary**: PHP mail() function
2. **Fallback**: Alternative mail() attempt
3. **Last Resort**: File-based logging

### **Fix 2: Status Button Persistence**

The status button now:
1. **Immediately updates** local status to 'accepted'
2. **Preserves local status** during API refreshes
3. **Forces UI updates** after payment success

### **Fix 3: Enhanced Logging**

All operations are now logged:
- Email attempts and results
- Status updates and UI changes
- Error details with stack traces

## 📧 Email Configuration Options

### **Option 1: External SMTP Service**

**Gmail SMTP:**
```php
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

**SendGrid:**
```php
$mail->Host = 'smtp.sendgrid.net';
$mail->SMTPAuth = true;
$mail->Username = 'apikey';
$mail->Password = 'your-sendgrid-api-key';
```

### **Option 2: Mailgun API**

```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/your-domain.com/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $emailData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode('api:your-api-key')
]);
```

### **Option 3: File-Based Logging**

Emails are logged to `email_logs/` folder:
- One file per day
- JSON format for easy processing
- Can be sent manually later

## 🔧 Server Configuration

### **PHP Configuration**

Check `php.ini`:
```ini
[mail function]
SMTP = localhost
smtp_port = 25
sendmail_path = /usr/sbin/sendmail
sendmail_from = noreply@playsmart.co.in
```

### **Apache/Nginx Configuration**

**Apache (.htaccess):**
```apache
php_value sendmail_path "/usr/sbin/sendmail"
php_value SMTP "localhost"
```

**Nginx:**
```nginx
fastcgi_param PHP_VALUE "sendmail_path=/usr/sbin/sendmail;SMTP=localhost";
```

### **cPanel Configuration**

1. **Email Accounts** → Create email account
2. **PHP Selector** → Enable mail functions
3. **Cron Jobs** → Set up email processing

## 📱 Flutter App Debugging

### **Check Payment Success Handler**

```dart
void _handlePaymentSuccess(PaymentSuccessResponse response) async {
  print('=== PAYMENT SUCCESS HANDLER STARTED ===');
  print('Payment Success: ${response.paymentId}');
  print('Current Job Application: ${_currentJobApplication?.id}');
  
  // Update local status immediately
  if (_currentJobApplication != null) {
    int jobId = _currentJobApplication!.id;
    userJobApplications[jobId] = 'accepted';
    
    setState(() {
      print('DEBUG: UI updated with new application status');
    });
  }
}
```

### **Verify Status Button Widget**

```dart
Widget _buildActionButton() {
  final hasApplied = _applicationStatus?['has_applied'] ?? false;
  
  if (!hasApplied) {
    return ElevatedButton(
      onPressed: widget.onApplyPressed,
      child: Text('Apply Now'),
    );
  } else {
    final status = _applicationStatus!['data']['status'];
    return ElevatedButton(
      onPressed: widget.onStatusPressed,
      child: Text(JobApplicationStatusService.getStatusDisplayText(status)),
    );
  }
}
```

## 🧪 Testing Procedures

### **Test 1: Email Functionality**

1. Run `php test_email_simple.php`
2. Check email inbox and spam folder
3. Verify server logs for errors

### **Test 2: Payment Flow**

1. Complete payment in Flutter app
2. Check server logs for email attempts
3. Verify status button updates
4. Check email_logs folder

### **Test 3: Status Persistence**

1. Complete payment
2. Navigate away from job list
3. Return to job list
4. Verify status button still shows "Accepted"

## 📊 Monitoring and Alerts

### **Log Monitoring**

Check these files regularly:
- `process_payment.php` logs
- `email_logs/` folder
- Server error logs
- Flutter app console logs

### **Email Status Tracking**

Monitor email delivery:
- Success/failure rates
- Delivery times
- Bounce rates
- Spam folder placement

## 🆘 Emergency Solutions

### **If Email Completely Fails**

1. **Use file logging** - Emails saved to `email_logs/`
2. **Manual email sending** - Process logged emails later
3. **SMS notifications** - Send text messages instead
4. **In-app notifications** - Show status updates in app

### **If Status Button Still Not Working**

1. **Force refresh** - Pull to refresh job list
2. **Restart app** - Clear app state
3. **Check network** - Verify API connectivity
4. **Clear cache** - Remove stored application data

## 📞 Support Contacts

### **Technical Support**
- **Email**: support@playsmart.co.in
- **Server**: Contact hosting provider
- **Flutter**: Check app logs and console

### **Hosting Provider**
- **Mail server configuration**
- **SMTP settings**
- **Firewall configuration**
- **DNS setup**

## 🔄 Next Steps

1. **Run email tests** to identify server issues
2. **Configure external SMTP** if needed
3. **Test payment flow** in Flutter app
4. **Monitor logs** for any remaining issues
5. **Implement monitoring** for email delivery

---

**Last Updated**: December 2024
**Status**: Active Troubleshooting
**Priority**: High - Email and Status Button Issues 