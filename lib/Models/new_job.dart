class NewJob {
  final int id;
  final String jobPost;
  final String salary;
  final String education;
  final String jobType;
  final String createdAt;

  NewJob({
    required this.id,
    required this.jobPost,
    required this.salary,
    required this.education,
    required this.jobType,
    required this.createdAt,
  });

  factory NewJob.fromJson(Map<String, dynamic> json) {
    return NewJob(
      id: json['id'] ?? 0,
      jobPost: json['job_post'] ?? '',
      salary: json['salary'] ?? '',
      education: json['education'] ?? '',
      jobType: json['job_type'] ?? '',
      createdAt: json['created_at'] ?? '',
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
    };
  }

  bool get isHigherJob => jobType == 'higher_job';
  bool get isLocalJob => jobType == 'local_job';
} 