import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:playsmart/Models/job_application.dart';

class JobApplicationController {
  static const String baseUrl = 'https://playsmart.co.in'; // Update with your domain

  // Fetch all job applications
  static Future<List<JobApplication>> fetchJobApplications() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/fetch_job_applications.php'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          final List<dynamic> applicationsData = responseData['data'];
          return applicationsData
              .map((json) => JobApplication.fromJson(json))
              .where((app) => app.isActive)
              .toList();
        } else {
          throw Exception(responseData['message'] ?? 'Failed to fetch job applications');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching job applications: $e');
      throw Exception('Failed to fetch job applications: $e');
    }
  }

  // Add new job application
  static Future<Map<String, dynamic>> addJobApplication({
    required int jobId,
    required String companyName,
    required String studentName,
    required String district,
    required String package,
    String? profile,
    String? companyLogoPath,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/add_job_application.php'),
      );

      // Add text fields
      request.fields['job_id'] = jobId.toString();
      request.fields['company_name'] = companyName;
      request.fields['student_name'] = studentName;
      request.fields['district'] = district;
      request.fields['package'] = package;
      if (profile != null) request.fields['profile'] = profile;

      // Add company logo file if provided
      if (companyLogoPath != null) {
        final file = File(companyLogoPath);
        if (await file.exists()) {
          request.files.add(
            await http.MultipartFile.fromPath('company_logo', companyLogoPath),
          );
        }
      }

      final streamedResponse = await request.send().timeout(Duration(seconds: 20));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          return responseData;
        } else {
          throw Exception(responseData['message'] ?? 'Failed to add job application');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error adding job application: $e');
      throw Exception('Failed to add job application: $e');
    }
  }

  // Update job application
  static Future<Map<String, dynamic>> updateJobApplication({
    required int id,
    String? companyName,
    String? studentName,
    String? district,
    String? package,
    String? profile,
    String? applicationStatus,
    String? companyLogoPath,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/update_job_application.php'),
      );

      request.fields['id'] = id.toString();

      // Add fields if provided
      if (companyName != null) request.fields['company_name'] = companyName;
      if (studentName != null) request.fields['student_name'] = studentName;
      if (district != null) request.fields['district'] = district;
      if (package != null) request.fields['package'] = package;
      if (profile != null) request.fields['profile'] = profile;
      if (applicationStatus != null) request.fields['application_status'] = applicationStatus;

      // Add company logo file if provided
      if (companyLogoPath != null) {
        final file = File(companyLogoPath);
        if (await file.exists()) {
          request.files.add(
            await http.MultipartFile.fromPath('company_logo', companyLogoPath),
          );
        }
      }

      final streamedResponse = await request.send().timeout(Duration(seconds: 20));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          return responseData;
        } else {
          throw Exception(responseData['message'] ?? 'Failed to update job application');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error updating job application: $e');
      throw Exception('Failed to update job application: $e');
    }
  }

  // Delete job application (soft delete)
  static Future<Map<String, dynamic>> deleteJobApplication(int id) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/delete_job_application.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id': id}),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          return responseData;
        } else {
          throw Exception(responseData['message'] ?? 'Failed to delete job application');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error deleting job application: $e');
      throw Exception('Failed to delete job application: $e');
    }
  }
} 