class Job {
  final int id;
  final String companyName;
  final String companyLogoUrl;
  final String studentName;
  final String district;
  final String package;
  final String profile;
  final String jobTitle;
  final String location;
  final String jobType;
  final String experienceLevel;
  final List<String> skillsRequired;
  final String jobDescription;
  final DateTime createdAt;

  Job({
    required this.id,
    required this.companyName,
    required this.companyLogoUrl,
    required this.studentName,
    required this.district,
    required this.package,
    required this.profile,
    required this.jobTitle,
    required this.location,
    required this.jobType,
    required this.experienceLevel,
    required this.skillsRequired,
    required this.jobDescription,
    required this.createdAt,
  });

  factory Job.fromJson(Map<String, dynamic> json) {
    try {
      print('DEBUG: Job.fromJson - Input: $json');
      
      // Handle company logo URL - if it's empty or invalid, set to empty string
      String logoUrl = '';
      if (json['company_logo_url'] != null && json['company_logo_url'].toString().isNotEmpty) {
        logoUrl = json['company_logo_url'].toString();
      }
      
      final job = Job(
        id: json['id'],
        companyName: json['company_name'] ?? '',
        companyLogoUrl: logoUrl,
        studentName: json['student_name'] ?? '',
        district: json['district'] ?? '',
        package: json['package'] ?? '',
        profile: json['profile'] ?? '',
        jobTitle: json['job_title'] ?? '',
        location: json['location'] ?? '',
        jobType: json['job_type'] ?? 'full_time',
        experienceLevel: json['experience_level'] ?? '',
        skillsRequired: List<String>.from(json['skills_required'] ?? []),
        jobDescription: json['job_description'] ?? '',
        createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      );
      
      print('DEBUG: Job.fromJson - Created job: ${job.companyName} - ${job.jobTitle}');
      return job;
    } catch (e) {
      print('DEBUG: Job.fromJson - Error: $e');
      print('DEBUG: Job.fromJson - Problematic JSON: $json');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'company_name': companyName,
      'company_logo_url': companyLogoUrl,
      'student_name': studentName,
      'district': district,
      'package': package,
      'profile': profile,
      'job_title': jobTitle,
      'location': location,
      'job_type': jobType,
      'experience_level': experienceLevel,
      'skills_required': skillsRequired,
      'job_description': jobDescription,
      'created_at': createdAt.toIso8601String(),
    };
  }
} 