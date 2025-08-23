# PlaySmart Jobs Feature Setup

This document explains how to set up the jobs feature for the PlaySmart app.

## 🗄️ Database Setup

### 1. Create Database
```sql
CREATE DATABASE playsmart_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE playsmart_db;
```

### 2. Import Jobs Table
Run the SQL commands from `jobs_table.sql` to create the jobs table and insert sample data.

### 3. Update Database Configuration
Edit `db_config.php` and update these values:
```php
define('DB_HOST', 'your_host');
define('DB_NAME', 'playsmart_db');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
```

## 📁 File Structure

```
your_server/
├── fetch_jobs.php          # API to fetch jobs
├── add_job.php            # API to add new jobs
├── update_job.php         # API to update jobs
├── delete_job.php         # API to delete jobs
├── db_config.php          # Database configuration
├── images/
│   └── company_logos/     # Company logo uploads
└── jobs_table.sql         # Database schema
```

## 🔧 API Endpoints

### 1. Fetch Jobs
- **URL**: `https://yourserver.com/fetch_jobs.php`
- **Method**: GET
- **Response**: JSON with jobs data

### 2. Add Job
- **URL**: `https://yourserver.com/add_job.php`
- **Method**: POST
- **Body**: Form data with job details and company logo

### 3. Update Job
- **URL**: `https://yourserver.com/update_job.php`
- **Method**: PUT/POST
- **Body**: JSON with job ID and fields to update

### 4. Delete Job
- **URL**: `https://yourserver.com/delete_job.php`
- **Method**: DELETE/POST
- **Body**: JSON with job ID

## 📱 Flutter Integration

### 1. Create Job Model
```dart
class Job {
  final int id;
  final String companyName;
  final String companyLogoUrl;
  final String jobTitle;
  final String package;
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
    required this.jobTitle,
    required this.package,
    required this.location,
    required this.jobType,
    required this.experienceLevel,
    required this.skillsRequired,
    required this.jobDescription,
    required this.createdAt,
  });

  factory Job.fromJson(Map<String, dynamic> json) {
    return Job(
      id: json['id'],
      companyName: json['company_name'],
      companyLogoUrl: json['company_logo_url'],
      jobTitle: json['job_title'],
      package: json['package'],
      location: json['location'],
      jobType: json['job_type'],
      experienceLevel: json['experience_level'] ?? '',
      skillsRequired: List<String>.from(json['skills_required'] ?? []),
      jobDescription: json['job_description'] ?? '',
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}
```

### 2. Create Job Service
```dart
class JobService {
  static const String baseUrl = 'https://yourserver.com';
  
  static Future<List<Job>> fetchJobs() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/fetch_jobs.php'));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          return (data['data'] as List)
              .map((json) => Job.fromJson(json))
              .toList();
        }
      }
      throw Exception('Failed to fetch jobs');
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
}
```

### 3. Add Jobs Section to Main Screen
```dart
// Add this above the Mega Contests section
if (jobs.isNotEmpty) ...[
  Text(
    'Popular Jobs',
    style: GoogleFonts.poppins(
      color: Colors.white,
      fontSize: 24,
      fontWeight: FontWeight.bold,
    ),
  ),
  SizedBox(height: 10),
  Container(
    height: 200,
    child: ListView.builder(
      scrollDirection: Axis.horizontal,
      itemCount: jobs.length,
      itemBuilder: (context, index) {
        final job = jobs[index];
        return Container(
          width: 200,
          margin: EdgeInsets.only(right: 15),
          child: _buildJobCard(job),
        );
      },
    ),
  ),
  SizedBox(height: 20),
],
```

## 🖼️ Company Logo Setup

### 1. Create Upload Directory
```bash
mkdir -p images/company_logos
chmod 755 images/company_logos
```

### 2. Logo Requirements
- **Format**: JPG, PNG, GIF
- **Max Size**: 5MB
- **Recommended**: 200x200 pixels, square format

## 🔒 Security Notes

1. **Update database credentials** in all PHP files
2. **Set proper file permissions** for uploads
3. **Validate file uploads** on server side
4. **Use HTTPS** in production
5. **Implement authentication** for admin operations

## 🚀 Testing

### 1. Test Database Connection
```bash
php -r "require 'db_config.php'; echo 'Database connection successful';"
```

### 2. Test API Endpoints
```bash
curl https://yourserver.com/fetch_jobs.php
```

### 3. Test File Upload
```bash
curl -X POST -F "company_name=Test Company" -F "job_title=Test Job" -F "package=10LPA" -F "location=Test City" -F "company_logo=@test_logo.png" https://yourserver.com/add_job.php
```

## 📝 Sample Job Data

The `jobs_table.sql` file includes sample data for:
- Google - Lead Product Manager
- Spotify - Senior UI Designer
- Microsoft - Software Engineer
- Amazon - Data Scientist
- Netflix - Frontend Developer
- Apple - iOS Developer
- Meta - Backend Engineer
- Uber - DevOps Engineer

## 🆘 Troubleshooting

### Common Issues:
1. **Database connection failed**: Check credentials and host
2. **File upload failed**: Check directory permissions
3. **CORS errors**: Ensure headers are set correctly
4. **500 errors**: Check PHP error logs

### Debug Mode:
Add this to PHP files for debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

For issues or questions, check:
1. PHP error logs
2. Database connection status
3. File permissions
4. Network connectivity 