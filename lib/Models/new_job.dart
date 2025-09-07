class NewJob {
  final int id;
  final String jobPost;
  final String salary;
  final String education;
  final String jobType;
  final String createdAt;
  final double applicationFee;

  NewJob({
    required this.id,
    required this.jobPost,
    required this.salary,
    required this.education,
    required this.jobType,
    required this.createdAt,
    this.applicationFee = 1000.0,
  });

  factory NewJob.fromJson(Map<String, dynamic> json) {
    return NewJob(
      id: json['id'] ?? 0,
      jobPost: json['job_post'] ?? '',
      salary: json['salary'] ?? '',
      education: json['education'] ?? '',
      jobType: json['job_type'] ?? '',
      createdAt: json['created_at'] ?? '',
      applicationFee: (json['application_fee'] ?? 1000).toDouble(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'job_post': jobPost,
      'salary': salary,
      'education': education,
      'job_type': jobType,
      'created_at': createdAt,
      'application_fee': applicationFee,
    };
  }

  // FIXED: Updated logic - 5 LPA and above is higher job
  bool get isHigherJob {
    // If job_type is explicitly set to higher_job, it's a higher job
    if (jobType == 'higher_job') return true;
    
    // If job_type is explicitly set to local_job, it's a local job
    if (jobType == 'local_job') return false;
    
    // Fallback: Check salary for LPA >= 5
    if (salary.contains('LPA')) {
      final salaryValue = double.tryParse(salary.replaceAll(RegExp(r'[^\d.]'), ''));
      return salaryValue != null && salaryValue >= 5;
    }
    
    return false;
  }
  
  bool get isLocalJob => !isHigherJob;
  
  // FIXED: Higher jobs (5+ LPA) get 2000 fee, local jobs get 1000 fee
  double get calculatedFee => isHigherJob ? 2000.0 : 1000.0;
} 