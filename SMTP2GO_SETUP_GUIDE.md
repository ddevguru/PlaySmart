# SMTP2GO Setup Guide - PlaySmart

## 🎯 Overview
This guide will help you configure SMTP2GO for reliable email delivery in your PlaySmart application. SMTP2GO is a professional email delivery service that ensures emails reach users' inboxes.

## 📋 Prerequisites
- SMTP2GO account (free tier available)
- Access to your server files
- Basic understanding of email configuration

## 🔧 Step 1: Create SMTP2GO Account

### 1.1 Sign Up
1. Go to [https://www.smtp2go.com/](https://www.smtp2go.com/)
2. Click "Sign Up" or "Get Started Free"
3. Fill in your details and create account

### 1.2 Verify Account
1. Check your email for verification link
2. Click the verification link
3. Complete your profile setup

## 🔑 Step 2: Get SMTP2GO Credentials

### 2.1 Access Dashboard
1. Login to your SMTP2GO account
2. Navigate to "Settings" → "SMTP Credentials"

### 2.2 Get Credentials
You'll find:
- **Username**: Usually your email address
- **Password**: Generated password for SMTP access
- **SMTP Server**: `mail.smtp2go.com`
- **Port**: `587` (TLS) or `2525` (TLS)

### 2.3 Example Credentials
```
Username: your-email@example.com
Password: abc123def456ghi789
SMTP Server: mail.smtp2go.com
Port: 587
```

## ⚙️ Step 3: Configure PlaySmart Application

### 3.1 Update Configuration File
Edit `smtp2go_config.php`:

```php
<?php
// SMTP2GO Configuration
define('SMTP2GO_HOST', 'mail.smtp2go.com');
define('SMTP2GO_PORT', 587);
define('SMTP2GO_USERNAME', 'your-actual-username'); // Replace with your actual username
define('SMTP2GO_PASSWORD', 'your-actual-password'); // Replace with your actual password
define('SMTP2GO_FROM_EMAIL', 'noreply@playsmart.co.in');
define('SMTP2GO_FROM_NAME', 'PlaySmart');
?>
```

### 3.2 Important Notes
- **Never commit real credentials** to version control
- **Use environment variables** in production
- **Keep credentials secure** and private

## 🧪 Step 4: Test Configuration

### 4.1 Run Test Script
```bash
php test_smtp2go_email.php
```

### 4.2 Expected Output
```
=== TESTING SMTP2GO EMAIL FUNCTIONALITY ===

1. Testing SMTP2GO email function...
📧 To: test@example.com
📧 Subject: Test SMTP2GO Email - PlaySmart
✅ SUCCESS: SMTP2GO email sent successfully!

2. Testing configuration...
✅ Configuration file exists
✅ SMTP2GO credentials configured
   Username: you***
   Password: abc***

3. Testing file logging...
✅ SUCCESS: Email logged to file
```

### 4.3 If Test Fails
- Check credentials in `smtp2go_config.php`
- Verify SMTP2GO account status
- Check server firewall settings
- Review error logs

## 📧 Step 5: Test Complete Payment Flow

### 5.1 Flutter App Test
1. Complete a payment in the Flutter app
2. Check if email is received
3. Verify status button updates to "Accepted"
4. Check server logs for email attempts

### 5.2 Server Logs
Check these files for email status:
- `process_payment.php` logs
- `email_logs/` folder
- Server error logs

## 🔍 Step 6: Troubleshooting

### 6.1 Common Issues

#### Issue: "SMTP2GO credentials not configured"
**Solution:**
```php
// In smtp2go_config.php, replace:
define('SMTP2GO_USERNAME', 'your-smtp2go-username');
define('SMTP2GO_PASSWORD', 'your-smtp2go-password');

// With your actual credentials:
define('SMTP2GO_USERNAME', 'your-email@example.com');
define('SMTP2GO_PASSWORD', 'abc123def456ghi789');
```

#### Issue: "Could not connect to SMTP2GO"
**Solutions:**
- Check internet connectivity
- Verify firewall settings
- Try different ports (587, 2525, 25)
- Contact hosting provider

#### Issue: "Authentication failed"
**Solutions:**
- Verify username and password
- Check account status
- Reset SMTP password
- Contact SMTP2GO support

### 6.2 Debug Information
Enable debug logging in `smtp2go_config.php`:
```php
define('SMTP2GO_DEBUG', true);
define('LOG_ALL_EMAIL_ATTEMPTS', true);
```

## 📊 Step 7: Monitor Email Delivery

### 7.1 SMTP2GO Dashboard
- Login to SMTP2GO dashboard
- Check "Activity" section
- Monitor delivery rates
- Review bounce reports

### 7.2 PlaySmart Logs
- Check `email_logs/` folder
- Review server logs
- Monitor payment success rates
- Track user feedback

## 🚀 Step 8: Production Deployment

### 8.1 Security Best Practices
- Use environment variables for credentials
- Restrict file access permissions
- Enable HTTPS for all communications
- Regular credential rotation

### 8.2 Environment Variables (Recommended)
```php
// In production, use environment variables:
define('SMTP2GO_USERNAME', $_ENV['SMTP2GO_USERNAME'] ?? '');
define('SMTP2GO_PASSWORD', $_ENV['SMTP2GO_PASSWORD'] ?? '');
```

### 8.3 .env File Example
```env
SMTP2GO_USERNAME=your-email@example.com
SMTP2GO_PASSWORD=abc123def456ghi789
SMTP2GO_HOST=mail.smtp2go.com
SMTP2GO_PORT=587
```

## 📱 Step 9: Status Button Verification

### 9.1 Expected Behavior
1. **Before Payment**: Shows "Apply Now" button
2. **After Payment**: Immediately shows "Accepted" status button
3. **After Refresh**: Status button persists as "Accepted"
4. **Email Sent**: User receives comprehensive email

### 9.2 Debug Status Button
Check Flutter app logs:
```
DEBUG: Setting job [ID] status to accepted
DEBUG: userJobApplications after update: {[ID]: accepted}
DEBUG: UI updated with new application status
DEBUG: Preserving local accepted status for job [ID]
```

## 🔄 Step 10: Maintenance

### 10.1 Regular Checks
- Monitor email delivery rates
- Check SMTP2GO account status
- Review server logs
- Update credentials if needed

### 10.2 Backup Plans
- Fallback to PHP mail() function
- File-based email logging
- Manual email processing
- SMS notifications (future)

## 📞 Support

### SMTP2GO Support
- **Email**: support@smtp2go.com
- **Documentation**: [https://www.smtp2go.com/docs/](https://www.smtp2go.com/docs/)
- **Status Page**: [https://status.smtp2go.com/](https://status.smtp2go.com/)

### PlaySmart Support
- **Email**: support@playsmart.co.in
- **Technical Issues**: Check server logs and error messages

## 📝 Checklist

- [ ] SMTP2GO account created and verified
- [ ] Credentials obtained from dashboard
- [ ] `smtp2go_config.php` updated with real credentials
- [ ] Test email sent successfully
- [ ] Payment flow tested in Flutter app
- [ ] Status button updates correctly
- [ ] Email received by user
- [ ] Production deployment completed
- [ ] Monitoring setup configured

---

**Last Updated**: December 2024
**Version**: 1.0
**Status**: Ready for Implementation
**Priority**: High - Email Delivery & Status Button 