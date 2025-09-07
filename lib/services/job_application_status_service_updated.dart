import 'dart:convert';
import 'package:http/http.dart' as http;

class JobApplicationStatusService {
  static const String _baseUrl = 'https://playsmart.co.in';
  
  // Check if user has applied for a specific job
  static Future<Map<String, dynamic>> checkJobApplicationStatus({
    required int jobId,
    required String userEmail,
  }) async {
    try {
      final uri = Uri.parse('$_baseUrl/check_job_application_status.php')
          .replace(queryParameters: {
        'job_id': jobId.toString(),
        'user_email': userEmail,
      });
      
      final response = await http.get(uri).timeout(Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return data;
        } else {
          throw Exception(data['message'] ?? 'Failed to check application status');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error checking job application status: $e');
      throw Exception('Failed to check application status: $e');
    }
  }
  
  // Check application status for multiple jobs
  static Future<Map<String, dynamic>> checkMultipleJobApplicationStatus({
    required List<int> jobIds,
    required String userEmail,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/check_job_application_status.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'job_ids': jobIds,
          'user_email': userEmail,
        }),
      ).timeout(Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return data;
        } else {
          throw Exception(data['message'] ?? 'Failed to check multiple application statuses');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error checking multiple job application statuses: $e');
      throw Exception('Failed to check multiple application statuses: $e');
    }
  }
  
  // Get status display text
  static String getStatusDisplayText(String? status) {
    if (status == null) return 'Not Applied';
    
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Pending Review';
      case 'payment_pending':
        return 'Payment Pending';
      case 'shortlisted':
        return 'Shortlisted';
      case 'accepted':
        return 'Accepted';
      case 'rejected':
        return 'Rejected';
      case 'paid':
        return 'Payment Completed';
      default:
        return 'Unknown Status';
    }
  }
  
  // Get status color
  static String getStatusColor(String? status) {
    if (status == null) return '#6c757d'; // Gray for not applied
    
    switch (status.toLowerCase()) {
      case 'pending':
        return '#ffc107'; // Yellow
      case 'payment_pending':
        return '#ff6600'; // Orange
      case 'shortlisted':
        return '#17a2b8'; // Blue
      case 'accepted':
        return '#28a745'; // Green
      case 'rejected':
        return '#dc3545'; // Red
      case 'paid':
        return '#28a745'; // Green
      default:
        return '#6c757d'; // Gray
    }
  }
  
  // Check if user can apply (hasn't applied yet)
  static bool canApply(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == false;
  }

  // Check if payment is pending
  static bool isPaymentPending(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == true && 
           statusData['data'] != null &&
           statusData['data']['status'] == 'payment_pending';
  }
  
  // Check if application is pending
  static bool isPending(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == true && 
           statusData['data'] != null &&
           statusData['data']['status'] == 'pending';
  }
  
  // Check if application is accepted
  static bool isAccepted(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == true && 
           statusData['data'] != null &&
           statusData['data']['status'] == 'accepted';
  }
  
  // Check if application is shortlisted
  static bool isShortlisted(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == true && 
           statusData['data'] != null &&
           statusData['data']['status'] == 'shortlisted';
  }
  
  // Check if application is rejected
  static bool isRejected(Map<String, dynamic> statusData) {
    return statusData['has_applied'] == true && 
           statusData['data'] != null &&
           statusData['data']['status'] == 'rejected';
  }
}
