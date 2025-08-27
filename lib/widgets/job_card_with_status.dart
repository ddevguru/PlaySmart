import 'package:flutter/material.dart';
import 'package:playsmart/services/job_application_status_service.dart';
import 'package:playsmart/Models/job.dart';

class JobCardWithStatus extends StatefulWidget {
  final Job job;
  final String userEmail;
  final VoidCallback? onApplyPressed;
  final VoidCallback? onStatusPressed;

  const JobCardWithStatus({
    Key? key,
    required this.job,
    required this.userEmail,
    this.onApplyPressed,
    this.onStatusPressed,
  }) : super(key: key);

  @override
  State<JobCardWithStatus> createState() => _JobCardWithStatusState();
}

class _JobCardWithStatusState extends State<JobCardWithStatus> {
  Map<String, dynamic>? _applicationStatus;
  bool _isLoading = true;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _checkApplicationStatus();
  }

  // Method to refresh application status (can be called externally)
  Future<void> refreshApplicationStatus() async {
    await _checkApplicationStatus();
  }

  Future<void> _checkApplicationStatus() async {
    try {
      setState(() {
        _isLoading = true;
        _hasError = false;
      });

      final statusData = await JobApplicationStatusService.checkJobApplicationStatus(
        jobId: widget.job.id,
        userEmail: widget.userEmail,
      );

      setState(() {
        _applicationStatus = statusData;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _hasError = true;
        _isLoading = false;
      });
      print('Error checking application status: $e');
    }
  }

  Widget _buildActionButton() {
    if (_isLoading) {
      return Container(
        width: 120,
        height: 40,
        child: Center(
          child: SizedBox(
            width: 20,
            height: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation<Color>(Colors.grey),
            ),
          ),
        ),
      );
    }

    if (_hasError) {
      return ElevatedButton(
        onPressed: _checkApplicationStatus,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.orange,
          foregroundColor: Colors.white,
        ),
        child: Text('Retry'),
      );
    }

    if (_applicationStatus == null) {
      return ElevatedButton(
        onPressed: widget.onApplyPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.blue,
          foregroundColor: Colors.white,
        ),
        child: Text('Apply Now'),
      );
    }

    final hasApplied = _applicationStatus!['has_applied'] ?? false;
    
    if (!hasApplied) {
      // User hasn't applied yet - show apply button
      return ElevatedButton(
        onPressed: widget.onApplyPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.blue,
          foregroundColor: Colors.white,
        ),
        child: Text('Apply Now'),
      );
    } else {
      // User has applied - show status button
      final status = _applicationStatus!['data']['status'];
      final statusText = JobApplicationStatusService.getStatusDisplayText(status);
      final statusColor = JobApplicationStatusService.getStatusColor(status);
      
      return ElevatedButton(
        onPressed: widget.onStatusPressed ?? () {
          _showStatusDetails();
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: Color(int.parse(statusColor.replaceAll('#', '0xFF'))),
          foregroundColor: Colors.white,
        ),
        child: Text(statusText),
      );
    }
  }

  void _showStatusDetails() {
    if (_applicationStatus == null || !(_applicationStatus!['has_applied'] ?? false)) {
      return;
    }

    final data = _applicationStatus!['data'];
    final status = data['status'];
    final appliedDate = data['applied_date'];
    final paymentId = data['payment_id'];
    final companyName = data['company_name'] ?? widget.job.companyName;
    final profile = data['profile'] ?? widget.job.jobTitle;
    final package = data['package'] ?? widget.job.package;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.work, color: Colors.blue),
            SizedBox(width: 8),
            Text('Application Status'),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Status badge
              Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                decoration: BoxDecoration(
                  color: Color(int.parse(JobApplicationStatusService.getStatusColor(status).replaceAll('#', '0xFF'))),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  JobApplicationStatusService.getStatusDisplayText(status),
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
              SizedBox(height: 20),
              
              // Application details
              _buildDetailRow('Company', companyName),
              _buildDetailRow('Position', profile),
              _buildDetailRow('Package', package),
              _buildDetailRow('Applied Date', _formatDate(appliedDate)),
              if (paymentId != null) _buildDetailRow('Payment ID', paymentId),
              
              SizedBox(height: 16),
              
              // Status description
              Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.grey[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey[300]!),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'What this means:',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[700],
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      _getStatusDescription(status),
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),
              
              SizedBox(height: 16),
              
              // Next steps
              Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue[200]!),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Next Steps:',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.blue[700],
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      _getNextSteps(status),
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.blue[600],
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text('Close'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              refreshApplicationStatus();
            },
            child: Text('Refresh Status'),
          ),
        ],
      ),
    );
  }

  String _formatDate(String? dateString) {
    if (dateString == null) return 'N/A';
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  String _getStatusDescription(String? status) {
    if (status == null) return '';
    
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Your application is under review. We will contact you soon with updates.';
      case 'shortlisted':
        return 'Congratulations! Your application has been shortlisted. We will schedule an interview soon.';
      case 'accepted':
        return 'Excellent! Your application has been accepted. Welcome to the team!';
      case 'rejected':
        return 'Thank you for your interest. Unfortunately, your application was not selected for this position.';
      case 'paid':
        return 'Payment completed successfully. Your application is being processed.';
      default:
        return 'Your application is being processed.';
    }
  }

  String _getNextSteps(String? status) {
    if (status == null) return '';
    
    switch (status.toLowerCase()) {
      case 'pending':
        return '• Wait for our team to review your application\n• You will receive an email update within 2-3 business days\n• Keep your contact information updated';
      case 'shortlisted':
        return '• Check your email for interview details\n• Prepare for the interview process\n• Keep your phone and email accessible';
      case 'accepted':
        return '• Congratulations! You will receive onboarding details\n• Complete any required documentation\n• Prepare for your new role';
      case 'rejected':
        return '• Don\'t be discouraged - keep applying to other opportunities\n• Consider improving your skills for future applications\n• Stay connected with PlaySmart for new openings';
      case 'paid':
        return '• Your application is being processed\n• You will receive status updates via email\n• Check the app for real-time status updates';
      default:
        return '• Your application is being processed\n• Check back regularly for updates\n• Contact support if you have questions';
    }
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: Colors.grey[700],
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                color: Colors.grey[800],
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.all(8.0),
      elevation: 4.0,
      child: Padding(
        padding: EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Company logo placeholder
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(Icons.business, color: Colors.grey[600]),
                ),
                SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.job.companyName,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        widget.job.jobTitle,
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey[700],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            Row(
              children: [
                Icon(Icons.location_on, color: Colors.grey[600], size: 16),
                SizedBox(width: 4),
                Text(
                  widget.job.location,
                  style: TextStyle(color: Colors.grey[600]),
                ),
                Spacer(),
                Icon(Icons.attach_money, color: Colors.grey[600], size: 16),
                SizedBox(width: 4),
                Text(
                  widget.job.package,
                  style: TextStyle(
                    color: Colors.green[700],
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            Row(
              children: [
                Icon(Icons.work, color: Colors.grey[600], size: 16),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    widget.job.jobDescription,
                    style: TextStyle(color: Colors.grey[600]),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Posted: ${_formatDate(widget.job.createdAt.toIso8601String())}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[500],
                  ),
                ),
                _buildActionButton(),
              ],
            ),
          ],
        ),
      ),
    );
  }
} 