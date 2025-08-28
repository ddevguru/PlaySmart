import 'dart:convert';
import 'package:http/http.dart' as http;
import '../Models/new_job.dart';

class NewJobsController {
  static const String baseUrl = 'https://playsmart.co.in';
  
  // Fetch all jobs from new_jobs table
  static Future<List<NewJob>> fetchNewJobs() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/fetch_new_jobs.php'),
        headers: {
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          final List<dynamic> jobsData = responseData['data'];
          return jobsData.map((json) => NewJob.fromJson(json)).toList();
        } else {
          throw Exception(responseData['message'] ?? 'Failed to fetch jobs');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching jobs: $e');
    }
  }

  // Get higher package jobs
  static Future<List<NewJob>> fetchHigherPackageJobs() async {
    final allJobs = await fetchNewJobs();
    return allJobs.where((job) => job.isHigherJob).toList();
  }

  // Get local jobs
  static Future<List<NewJob>> fetchLocalJobs() async {
    final allJobs = await fetchNewJobs();
    return allJobs.where((job) => job.isLocalJob).toList();
  }
} 