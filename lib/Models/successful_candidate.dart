class SuccessfulCandidate {
  final int id;
  final String companyName;
  final String candidateName;
  final String salary;
  final String jobLocation;
  final String createdAt;

  SuccessfulCandidate({
    required this.id,
    required this.companyName,
    required this.candidateName,
    required this.salary,
    required this.jobLocation,
    required this.createdAt,
  });

  factory SuccessfulCandidate.fromJson(Map<String, dynamic> json) {
    return SuccessfulCandidate(
      id: json['id'] ?? 0,
      companyName: json['company_name'] ?? '',
      candidateName: json['candidate_name'] ?? '',
      salary: json['salary'] ?? '',
      jobLocation: json['job_location'] ?? '',
      createdAt: json['created_at'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'company_name': companyName,
      'candidate_name': candidateName,
      'salary': salary,
      'job_location': jobLocation,
      'created_at': createdAt,
    };
  }
} 