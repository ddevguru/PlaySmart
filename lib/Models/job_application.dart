import 'dart:convert';

class JobApplication {
  final int id;
  final int jobId;
  final String companyName;
  final String companyLogoUrl;
  final String studentName;
  final String district;
  final String package;
  final String profile;
  final String photoPath;
  final String resumePath;
  final String email;
  final String phone;
  final String experience;
  final String skills;
  final String paymentId;
  final String applicationStatus;
  final DateTime appliedDate;
  final bool isActive;

  JobApplication({
    required this.id,
    required this.jobId,
    required this.companyName,
    required this.companyLogoUrl,
    required this.studentName,
    required this.district,
    required this.package,
    required this.profile,
    required this.photoPath,
    required this.resumePath,
    required this.email,
    required this.phone,
    required this.experience,
    required this.skills,
    required this.paymentId,
    required this.applicationStatus,
    required this.appliedDate,
    required this.isActive,
  });

  factory JobApplication.fromJson(Map<String, dynamic> json) {
    // Handle company logo URL - if it's empty or invalid, set to empty string
    String logoUrl = '';
    if (json['company_logo_url'] != null && json['company_logo_url'].toString().isNotEmpty) {
      logoUrl = json['company_logo_url'].toString();
    }
    
    // Handle photo URL - use the photo_url field directly from API
    String photoUrl = '';
    if (json['photo_url'] != null && json['photo_url'].toString().isNotEmpty) {
      photoUrl = json['photo_url'].toString();
    }
    
    // Handle resume URL - use the resume_url field directly from API
    String resumeUrl = '';
    if (json['resume_url'] != null && json['resume_url'].toString().isNotEmpty) {
      resumeUrl = json['resume_url'].toString();
    }
    
    // Also handle photo_path and resume_path for backward compatibility
    if (photoUrl.isEmpty && json['photo_path'] != null && json['photo_path'].toString().isNotEmpty) {
      photoUrl = json['photo_path'].toString();
    }
    
    if (resumeUrl.isEmpty && json['resume_path'] != null && json['resume_path'].toString().isNotEmpty) {
      resumeUrl = json['resume_path'].toString();
    }
    
    return JobApplication(
      id: json['id'],
      jobId: json['job_id'],
      companyName: json['company_name'],
      companyLogoUrl: logoUrl,
      studentName: json['student_name'],
      district: json['district'],
      package: json['package'],
      profile: json['profile'] ?? '',
      photoPath: photoUrl, // Store the full URL here
      resumePath: resumeUrl, // Store the full URL here
      email: json['email'] ?? '',
      phone: json['phone'] ?? '',
      experience: json['experience'] ?? '',
      skills: json['skills'] ?? '',
      paymentId: json['payment_id'] ?? '',
      applicationStatus: json['application_status'],
      appliedDate: DateTime.parse(json['applied_date']),
      isActive: json['is_active'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'job_id': jobId,
      'company_name': companyName,
      'company_logo_url': companyLogoUrl,
      'student_name': studentName,
      'district': district,
      'package': package,
      'profile': profile,
      'application_status': applicationStatus,
      'applied_date': appliedDate.toIso8601String(),
      'is_active': isActive ? 1 : 0,
    };
  }
  
  // Getter for photo URL - Return the stored URL directly
  String? get photoUrl {
    if (photoPath.isNotEmpty) {
      return photoPath; // Return the full URL stored in photoPath
    }
    return null;
  }
  
  // Getter for resume URL - Return the stored URL directly
  String? get resumeUrl {
    if (resumePath.isNotEmpty) {
      return resumePath; // Return the full URL stored in resumePath
    }
    return null;
  }
} 