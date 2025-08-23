import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:playsmart/Models/job.dart';

class JobController {
  static const String baseUrl = 'https://playsmart.co.in';

  // Fetch all active jobs
  static Future<List<Job>> fetchJobs() async {
    try {
      print('DEBUG: JobController - Starting fetchJobs');
      print('DEBUG: JobController - URL: $baseUrl/fetch_jobs.php');
      
      final response = await http.get(
        Uri.parse('$baseUrl/fetch_jobs.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ).timeout(Duration(seconds: 15));

      print('DEBUG: JobController - Response status: ${response.statusCode}');
      print('DEBUG: JobController - Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('DEBUG: JobController - Parsed data: $data');
        
        if (data['success'] == true) {
          final jobsList = (data['data'] as List)
              .map((json) => Job.fromJson(json))
              .toList();
          print('DEBUG: JobController - Created ${jobsList.length} Job objects');
          return jobsList;
        } else {
          throw Exception(data['message'] ?? 'Failed to fetch jobs');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error fetching jobs: $e');
      throw Exception('Failed to fetch jobs: $e');
    }
  }

  // Add new job (for admin use)
  static Future<Map<String, dynamic>> addJob({
    required String companyName,
    required String studentName,
    required String district,
    required String package,
    String? profile,
    String? jobTitle,
    String? location,
    String? jobType,
    String? experienceLevel,
    String? skillsRequired,
    String? jobDescription,
    String? companyLogoPath,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/add_job.php'),
      );

      // Add text fields
      request.fields['company_name'] = companyName;
      request.fields['student_name'] = studentName;
      request.fields['district'] = district;
      request.fields['package'] = package;
      if (profile != null) request.fields['profile'] = profile;
      if (jobTitle != null) request.fields['job_title'] = jobTitle;
      if (location != null) request.fields['location'] = location;
      if (jobType != null) request.fields['job_type'] = jobType;
      if (experienceLevel != null) request.fields['experience_level'] = experienceLevel;
      if (skillsRequired != null) request.fields['skills_required'] = skillsRequired;
      if (jobDescription != null) request.fields['job_description'] = jobDescription;

      // Add logo file if provided
      if (companyLogoPath != null) {
        request.files.add(await http.MultipartFile.fromPath(
          'company_logo',
          companyLogoPath,
        ));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to add job');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error adding job: $e');
      throw Exception('Failed to add job: $e');
    }
  }

  // Update existing job (for admin use)
  static Future<Map<String, dynamic>> updateJob({
    required int id,
    String? companyName,
    String? studentName,
    String? district,
    String? package,
    String? profile,
    String? jobTitle,
    String? location,
    String? jobType,
    String? experienceLevel,
    String? skillsRequired,
    String? jobDescription,
    String? companyLogoPath,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/update_job.php'),
      );

      // Add job ID
      request.fields['id'] = id.toString();

      // Add text fields if provided
      if (companyName != null) request.fields['company_name'] = companyName;
      if (studentName != null) request.fields['student_name'] = studentName;
      if (district != null) request.fields['district'] = district;
      if (package != null) request.fields['package'] = package;
      if (profile != null) request.fields['profile'] = profile;
      if (jobTitle != null) request.fields['job_title'] = jobTitle;
      if (location != null) request.fields['location'] = location;
      if (jobType != null) request.fields['job_type'] = jobType;
      if (experienceLevel != null) request.fields['experience_level'] = experienceLevel;
      if (skillsRequired != null) request.fields['skills_required'] = skillsRequired;
      if (jobDescription != null) request.fields['job_description'] = jobDescription;

      // Add logo file if provided
      if (companyLogoPath != null) {
        request.files.add(await http.MultipartFile.fromPath(
          'company_logo',
          companyLogoPath,
        ));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to update job');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error updating job: $e');
      throw Exception('Failed to update job: $e');
    }
  }

  // Delete job (for admin use)
  static Future<bool> deleteJob(int id) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/delete_job.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'id': id}),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error deleting job: $e');
      throw Exception('Failed to delete job: $e');
    }
  }
} 