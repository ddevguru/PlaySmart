import 'dart:convert';
import 'package:http/http.dart' as http;
import '../Models/job_application.dart';

class SuccessfulCandidatesController {
  static const String baseUrl = 'https://playsmart.co.in';
  
  // Fetch successfully placed candidates
  static Future<List<JobApplication>> fetchSuccessfulCandidates() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/fetch_successful_candidates.php'),
        headers: {
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        
        if (responseData['success'] == true) {
          final List<dynamic> candidatesData = responseData['data'];
          return candidatesData.map((json) => JobApplication.fromJson(json)).toList();
        } else {
          throw Exception(responseData['message'] ?? 'Failed to fetch successful candidates');
        }
      } else {
        throw Exception('HTTP Error: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching successful candidates: $e');
    }
  }

  // Get candidate photo URL
  static String getCandidatePhotoUrl(String? photoPath) {
    if (photoPath == null || photoPath.isEmpty) {
      return ''; // Return empty string for no photo
    }
    return '$baseUrl/Admin/uploads/photos/$photoPath';
  }

  // Get company logo URL
  static String getCompanyLogoUrl(String? logoPath) {
    if (logoPath == null || logoPath.isEmpty) {
      return ''; // Return empty string for no logo
    }
    return '$baseUrl/Admin/uploads/$logoPath';
  }

  // Get resume URL
  static String getResumeUrl(String? resumePath) {
    if (resumePath == null || resumePath.isEmpty) {
      return ''; // Return empty string for no resume
    }
    return '$baseUrl/uploads/resumes/$resumePath';
  }

  // Format experience for display
  static String formatExperience(String? experience) {
    if (experience == null || experience.isEmpty) {
      return 'Not specified';
    }
    return experience;
  }

  // Format skills for display
  static String formatSkills(String? skills) {
    if (skills == null || skills.isEmpty) {
      return 'Not specified';
    }
    // Split skills by comma and format them
    List<String> skillList = skills.split(',').map((skill) => skill.trim()).toList();
    return skillList.join(', ');
  }

  // Get placement status color
  static String getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'accepted':
        return '#28a745'; // Green
      case 'shortlisted':
        return '#ffc107'; // Yellow
      case 'pending':
        return '#17a2b8'; // Blue
      case 'rejected':
        return '#dc3545'; // Red
      default:
        return '#6c757d'; // Gray
    }
  }
} 