import 'dart:convert';
import 'package:http/http.dart' as http;
import '../Models/job.dart';

class LocalJobsController {
  static const String baseUrl = 'https://playsmart.co.in';
  
  // Fetch local jobs (package < 10 LPA)
  static Future<Map<String, dynamic>> fetchLocalJobs({
    int page = 1,
    int limit = 20,
    String? location,
    String? jobType,
    String? experience,
  }) async {
    try {
      // Build query parameters
      final queryParams = <String, String>{
        'page': page.toString(),
        'limit': limit.toString(),
      };
      
      if (location != null && location.isNotEmpty) {
        queryParams['location'] = location;
      }
      
      if (jobType != null && jobType.isNotEmpty) {
        queryParams['job_type'] = jobType;
      }
      
      if (experience != null && experience.isNotEmpty) {
        queryParams['experience'] = experience;
      }
      
      // Build URL with query parameters
      final uri = Uri.parse('$baseUrl/fetch_local_jobs.php').replace(queryParameters: queryParams);
      
      print('Fetching local jobs from: $uri');
      
      final response = await http.get(uri).timeout(const Duration(seconds: 15));
      
      print('Local jobs response status: ${response.statusCode}');
      print('Local jobs response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          // Parse jobs
          final List<dynamic> jobsData = data['data']['jobs'];
          final List<Job> jobs = jobsData.map((jobData) => Job.fromJson(jobData)).toList();
          
          return {
            'success': true,
            'jobs': jobs,
            'pagination': data['data']['pagination'],
            'filters': data['data']['filters'],
          };
        } else {
          return {
            'success': false,
            'message': data['message'] ?? 'Failed to fetch local jobs',
            'jobs': <Job>[],
            'pagination': null,
            'filters': null,
          };
        }
      } else {
        return {
          'success': false,
          'message': 'HTTP Error: ${response.statusCode}',
          'jobs': <Job>[],
          'pagination': null,
          'filters': null,
        };
      }
    } catch (e) {
      print('Error fetching local jobs: $e');
      return {
        'success': false,
        'message': 'Network error: $e',
        'jobs': <Job>[],
        'pagination': null,
        'filters': null,
      };
    }
  }
  
  // Search local jobs with filters
  static Future<Map<String, dynamic>> searchLocalJobs({
    required String query,
    int page = 1,
    int limit = 20,
    String? location,
    String? jobType,
    String? experience,
  }) async {
    try {
      // Build query parameters
      final queryParams = <String, String>{
        'page': page.toString(),
        'limit': limit.toString(),
        'search': query,
      };
      
      if (location != null && location.isNotEmpty) {
        queryParams['location'] = location;
      }
      
      if (jobType != null && jobType.isNotEmpty) {
        queryParams['job_type'] = jobType;
      }
      
      if (experience != null && experience.isNotEmpty) {
        queryParams['experience'] = experience;
      }
      
      // Build URL with query parameters
      final uri = Uri.parse('$baseUrl/fetch_local_jobs.php').replace(queryParameters: queryParams);
      
      print('Searching local jobs from: $uri');
      
      final response = await http.get(uri).timeout(const Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          // Parse jobs
          final List<dynamic> jobsData = data['data']['jobs'];
          final List<Job> jobs = jobsData.map((jobData) => Job.fromJson(jobData)).toList();
          
          return {
            'success': true,
            'jobs': jobs,
            'pagination': data['data']['pagination'],
            'filters': data['data']['filters'],
          };
        } else {
          return {
            'success': false,
            'message': data['message'] ?? 'Failed to search local jobs',
            'jobs': <Job>[],
            'pagination': null,
            'filters': null,
          };
        }
      } else {
        return {
          'success': false,
          'message': 'HTTP Error: ${response.statusCode}',
          'jobs': <Job>[],
          'pagination': null,
          'filters': null,
        };
      }
    } catch (e) {
      print('Error searching local jobs: $e');
      return {
        'success': false,
        'message': 'Network error: $e',
        'jobs': <Job>[],
        'pagination': null,
        'filters': null,
      };
    }
  }
  
  // Get job types available for local jobs
  static List<String> getLocalJobTypes() {
    return [
      'Full-time',
      'Part-time',
      'Contract',
      'Internship',
      'Freelance',
    ];
  }
  
  // Get experience levels for local jobs
  static List<String> getLocalExperienceLevels() {
    return [
      'Entry Level',
      '1-2 years',
      '2-5 years',
      '5-10 years',
      '10+ years',
    ];
  }
  
  // Get popular locations for local jobs
  static List<String> getLocalPopularLocations() {
    return [
      'Mumbai',
      'Delhi',
      'Bangalore',
      'Hyderabad',
      'Chennai',
      'Pune',
      'Kolkata',
      'Ahmedabad',
      'Remote',
    ];
  }
} 