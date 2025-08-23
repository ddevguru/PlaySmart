# Job Application Features - Auto-scrolling & File Upload

## Overview
This update adds two major features to the PlaySmart app:
1. **Auto-scrolling Job Applications Container** - Automatically scrolls through job applications
2. **File Upload for Job Applications** - Allows users to upload photos and resumes when applying for jobs

## Features Implemented

### 1. Auto-scrolling Job Applications
- **Location**: Main screen, first container (Job Applications section)
- **Behavior**: Automatically scrolls horizontally every 3 seconds
- **Smart Scrolling**: 
  - Pauses when user interacts with the container
  - Resumes after 2 seconds of inactivity
  - Loops back to the beginning when reaching the end
- **Smooth Animation**: Uses easing curves for natural movement

### 2. File Upload System
- **Photo Upload**: 
  - Supports JPEG, PNG, GIF formats
  - Maximum file size: 5MB
  - Image compression for optimal performance
- **Resume Upload**:
  - Supports PDF, DOC, DOCX formats
  - Maximum file size: 10MB
  - Secure file handling

## Technical Implementation

### Flutter Changes (lib/main_screen.dart)
- Added `ScrollController` for job applications container
- Implemented auto-scroll timer with pause/resume functionality
- Added file picker integration for photos and resumes
- Enhanced form validation to require file uploads
- Integrated file upload with payment success flow

### New Dependencies
```yaml
file_picker: ^8.0.0+1
image_picker: ^1.1.2
```

### PHP Backend Files
1. **upload_photo.php** - Handles photo uploads
2. **upload_resume.php** - Handles resume uploads
3. **job_application_files.sql** - Database schema for file storage

## Setup Instructions

### 1. Install Dependencies
```bash
flutter pub get
```

### 2. Database Setup
Run the SQL commands in `job_application_files.sql` to create the necessary tables.

### 3. File Permissions
Ensure the `uploads/` directory has write permissions:
```bash
mkdir -p uploads/photos uploads/resumes
chmod 755 uploads uploads/photos uploads/resumes
```

### 4. Server Configuration
- Ensure PHP has file upload support enabled
- Set appropriate `upload_max_filesize` and `post_max_size` in php.ini
- Verify CORS headers are properly configured

## Usage

### For Users
1. **Viewing Job Applications**: The container automatically scrolls, showing different applications
2. **Applying for Jobs**: 
   - Fill in personal details
   - Select a profile photo (tap the photo area)
   - Upload a resume (tap the resume area)
   - Complete payment to submit application

### For Developers
- Auto-scrolling can be paused/resumed using `_pauseAutoScroll()` and `_resumeAutoScroll()`
- File uploads are handled automatically after successful payment
- File paths are stored in the database for future reference

## File Structure
```
uploads/
├── photos/          # Profile photos
└── resumes/         # Resume files
```

## Security Features
- File type validation
- File size limits
- User authentication required
- Secure file naming (user_id_job_id_timestamp.ext)
- Database constraints and foreign keys

## Troubleshooting

### Common Issues
1. **Files not uploading**: Check file permissions and PHP upload settings
2. **Auto-scroll not working**: Verify ScrollController is properly initialized
3. **Image picker errors**: Ensure camera/gallery permissions are granted

### Debug Information
- Check browser console for JavaScript errors
- Review PHP error logs for upload issues
- Verify database connections and table structure

## Future Enhancements
- File preview before upload
- Multiple file upload support
- Progress indicators for uploads
- File compression and optimization
- Cloud storage integration

## Support
For technical support or questions about these features, please refer to the development team or create an issue in the project repository. 