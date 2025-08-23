import 'dart:async';
import 'dart:math' as math;
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';

import 'package:playsmart/Models/contest.dart';
import 'package:playsmart/Models/job.dart';
import 'package:playsmart/Models/job_application.dart';
import 'package:playsmart/controller/mega-contest-controller.dart';
import 'package:playsmart/controller/mini-contest-controller.dart';
import 'package:playsmart/controller/job_controller.dart';
import 'package:playsmart/controller/job_application_controller.dart';
import 'package:playsmart/profile_Screen.dart';
import 'package:playsmart/splash_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'quiz_screen.dart';
import 'mega_quiz_screen.dart';
import 'mega_result_screen.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';

class MainScreen extends StatefulWidget {
  const MainScreen({Key? key}) : super(key: key);

  @override
  _MainScreenState createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> with TickerProviderStateMixin {
  late AnimationController _animationController;
  late AnimationController _floatingIconsController;
  late AnimationController _pulseController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;
  late ScrollController _jobApplicationsScrollController;
  late Timer _autoScrollTimer;
  double userBalance = 0.0;
  List<Contest> miniContests = [];
  List<Contest> megaContests = [];
  List<Job> jobs = [];
  List<JobApplication> jobApplications = [];
  Map<int, String> userJobApplications = {}; // Track user's job applications
  Job? _currentJobApplication; // Track current job being applied for
  final ContestController _miniContestController = ContestController();
  final MegaContestController _megaContestController = MegaContestController();
  Timer? _refreshTimer;
  Map<int, Map<String, dynamic>> _megaContestStatus = {};
  late Razorpay _razorpay;
  
  // File paths for job application
  String? _selectedPhotoPath;
  String? _selectedResumePath;

  final List<List<Color>> cardGradients = [
    [Color(0xFFFF4E50), Color(0xFFF9D423)],
    [Color(0xFF00C9FF), Color(0xFF92FE9D)],
    [Color(0xFFFF8008), Color(0xFFFFC837)],
    [Color(0xFFFF512F), Color(0xFFDD2476)],
    [Color(0xFF4776E6), Color(0xFF8E54E9)],
    [Color(0xFF1FA2FF), Color(0xFF12D8FA), Color(0xFFA6FFCB)],
  ];

  Map<int, List<Map<String, dynamic>>> _contestRankings = {};

  @override
  void initState() {
    super.initState();
    _jobApplicationsScrollController = ScrollController();
    _initializeAnimations();
    _initializeRazorpay();
    fetchUserBalance();
    fetchContests();
    fetchJobApplications();
    fetchJobs();
    _startRefreshTimer();
    _startAutoScroll();
    
    // Add sample job applications for testing
    userJobApplications[1] = 'pending';
    userJobApplications[2] = 'shortlisted';
    
    // Add sample job applications for testing display
    print('DEBUG: Setting up sample job applications...');
    jobApplications = [
      JobApplication(
        id: 1,
        jobId: 1,
        companyName: 'Google',
        companyLogoUrl: 'https://playsmart.co.in/uploads/google_logo.png',
        studentName: 'Rahul Sharma',
        district: 'Mumbai',
        package: '12LPA',
        profile: 'Product Manager',
        photoPath: 'uploads/photos/rahul_sharma.jpg',
        resumePath: 'uploads/resumes/rahul_sharma_resume.pdf',
        email: 'rahul.sharma@email.com',
        phone: '+91-9876543210',
        experience: '5 years',
        skills: 'Product Management, Analytics, Leadership',
        paymentId: 'pay_123456789',
        applicationStatus: 'shortlisted',
        appliedDate: DateTime.now().subtract(Duration(days: 2)),
        isActive: true,
      ),
      JobApplication(
        id: 2,
        jobId: 2,
        companyName: 'Spotify',
        companyLogoUrl: 'https://playsmart.co.in/uploads/spotify_logo.png',
        studentName: 'Priya Patel',
        district: 'Pune',
        package: '12LPA',
        profile: 'UI Designer',
        photoPath: 'uploads/photos/priya_patel.jpg',
        resumePath: 'uploads/resumes/priya_patel_resume.pdf',
        email: 'priya.patel@email.com',
        phone: '+91-9876543211',
        experience: '4 years',
        skills: 'Product Strategy, User Research, Data Analysis',
        paymentId: 'pay_123456790',
        applicationStatus: 'pending',
        appliedDate: DateTime.now().subtract(Duration(days: 1)),
        isActive: true,
      ),
      JobApplication(
        id: 3,
        jobId: 3,
        companyName: 'Microsoft',
        companyLogoUrl: 'https://playsmart.co.in/uploads/microsoft_logo.png',
        studentName: 'Amit Kumar',
        district: 'Delhi',
        package: '15LPA',
        profile: 'Software Engineer',
        photoPath: 'uploads/photos/amit_kumar.jpg',
        resumePath: 'uploads/resumes/amit_kumar_resume.pdf',
        email: 'amit.kumar@email.com',
        phone: '+91-9876543212',
        experience: '6 years',
        skills: 'UI/UX Design, Figma, Prototyping',
        paymentId: 'pay_123456791',
        applicationStatus: 'accepted',
        appliedDate: DateTime.now().subtract(Duration(days: 3)),
        isActive: true,
      ),
    ];
    
    print('DEBUG: Sample data setup complete. jobApplications.length = ${jobApplications.length}');
    

  }

  void _initializeAnimations() {
    _animationController = AnimationController(
      vsync: this,
      duration: Duration(milliseconds: 1200),
    );
    _floatingIconsController = AnimationController(
      vsync: this,
      duration: Duration(milliseconds: 8000),
    )..repeat();
    _pulseController = AnimationController(
      vsync: this,
      duration: Duration(milliseconds: 1500),
    )..repeat(reverse: true);
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: Interval(0.0, 0.65, curve: Curves.easeOut),
      ),
    );
    _slideAnimation = Tween<Offset>(
      begin: Offset(0, 0.3),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: Interval(0.3, 1.0, curve: Curves.easeOutCubic),
      ),
    );
    _animationController.forward();
  }

  void _initializeRazorpay() {
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
  }

  void _handlePaymentSuccess(PaymentSuccessResponse response) {
    print('Payment Success: ${response.paymentId}');
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Payment successful! Job application submitted.'),
        backgroundColor: Colors.green,
      ),
    );
    
    // Store the job application status locally
    if (_currentJobApplication != null) {
      userJobApplications[_currentJobApplication!.id] = 'pending';
      setState(() {});
      
      // Upload files if they were selected
      if (_selectedPhotoPath != null && _selectedResumePath != null) {
        _uploadFiles(
          _currentJobApplication!,
          _selectedPhotoPath!,
          _selectedResumePath!,
        );
      }
    }
    
    print('Payment ID: ${response.paymentId}');
    print('Payment Signature: ${response.signature}');
    
    // Refresh job applications to show the new one
    fetchJobApplications();
  }

  void _handlePaymentError(PaymentFailureResponse response) {
    print('Payment Error: ${response.code} - ${response.message}');
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Payment failed: ${response.message}'),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _handleExternalWallet(ExternalWalletResponse response) {
    print('External Wallet: ${response.walletName}');
  }

  void _showJobApplicationModal(Job job) {
    _currentJobApplication = job; // Set current job for tracking
    
    final TextEditingController nameController = TextEditingController();
    final TextEditingController emailController = TextEditingController();
    final TextEditingController phoneController = TextEditingController();
    final TextEditingController experienceController = TextEditingController();
    final TextEditingController skillsController = TextEditingController();
    
    // Reset file paths for new application
    _selectedPhotoPath = null;
    _selectedResumePath = null;
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.transparent,
          contentPadding: EdgeInsets.zero,
          content: Container(
            width: MediaQuery.of(context).size.width * 0.9,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 20,
                  offset: Offset(0, 10),
                ),
              ],
            ),
            child: SingleChildScrollView(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'Apply for ${job.jobTitle}',
                      style: GoogleFonts.poppins(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    SizedBox(height: 20),
                    
                    // Job Details Card
                    Container(
                      padding: EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Company: ${job.companyName}',
                            style: GoogleFonts.poppins(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Package: ${job.package}',
                            style: GoogleFonts.poppins(
                              fontSize: 14,
                              color: Colors.white70,
                            ),
                          ),
                          SizedBox(height: 8),
                          Text(
                            'Location: ${job.location}',
                            style: GoogleFonts.poppins(
                              fontSize: 14,
                              color: Colors.white70,
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 20),
                    
                    // Application Form
                    Container(
                      padding: EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Personal Details',
                            style: GoogleFonts.poppins(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(height: 16),
                          
                          // Name Field
                          TextFormField(
                            controller: nameController,
                            style: GoogleFonts.poppins(color: Colors.white),
                            decoration: InputDecoration(
                              labelText: 'Full Name',
                              labelStyle: GoogleFonts.poppins(color: Colors.white70),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.yellow),
                              ),
                            ),
                          ),
                          SizedBox(height: 12),
                          
                          // Email Field
                          TextFormField(
                            controller: emailController,
                            style: GoogleFonts.poppins(color: Colors.white),
                            decoration: InputDecoration(
                              labelText: 'Email Address',
                              labelStyle: GoogleFonts.poppins(color: Colors.white70),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.yellow),
                              ),
                            ),
                          ),
                          SizedBox(height: 12),
                          
                          // Phone Field
                          TextFormField(
                            controller: phoneController,
                            style: GoogleFonts.poppins(color: Colors.white),
                            decoration: InputDecoration(
                              labelText: 'Phone Number',
                              labelStyle: GoogleFonts.poppins(color: Colors.white70),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.yellow),
                              ),
                            ),
                          ),
                          SizedBox(height: 12),
                          
                          // Experience Field
                          TextFormField(
                            controller: experienceController,
                            style: GoogleFonts.poppins(color: Colors.white),
                            decoration: InputDecoration(
                              labelText: 'Years of Experience',
                              labelStyle: GoogleFonts.poppins(color: Colors.white70),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.white30),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.yellow),
                              ),
                            ),
                          ),
                          SizedBox(height: 12),
                          
                                                     // Skills Field
                           TextFormField(
                             controller: skillsController,
                             style: GoogleFonts.poppins(color: Colors.white),
                             maxLines: 3,
                             decoration: InputDecoration(
                               labelText: 'Skills & Technologies',
                               labelStyle: GoogleFonts.poppins(color: Colors.white70),
                               border: OutlineInputBorder(
                                 borderRadius: BorderRadius.circular(8),
                                 borderSide: BorderSide(color: Colors.white30),
                               ),
                               enabledBorder: OutlineInputBorder(
                                 borderRadius: BorderRadius.circular(8),
                                 borderSide: BorderSide(color: Colors.white30),
                               ),
                               focusedBorder: OutlineInputBorder(
                                 borderRadius: BorderRadius.circular(8),
                                 borderSide: BorderSide(color: Colors.yellow),
                               ),
                             ),
                           ),
                           SizedBox(height: 12),
                           
                           // Photo Upload
                           Container(
                             padding: EdgeInsets.all(12),
                             decoration: BoxDecoration(
                               color: Colors.white.withOpacity(0.05),
                               borderRadius: BorderRadius.circular(8),
                               border: Border.all(color: Colors.white30),
                             ),
                             child: Column(
                               crossAxisAlignment: CrossAxisAlignment.start,
                               children: [
                                 Text(
                                   'Profile Photo',
                                   style: GoogleFonts.poppins(
                                     color: Colors.white,
                                     fontSize: 14,
                                     fontWeight: FontWeight.w600,
                                   ),
                                 ),
                                 SizedBox(height: 8),
                                 GestureDetector(
                                   onTap: () async {
                                     try {
                                       final ImagePicker picker = ImagePicker();
                                       final XFile? image = await picker.pickImage(
                                         source: ImageSource.gallery,
                                         maxWidth: 512,
                                         maxHeight: 512,
                                         imageQuality: 80,
                                       );
                                       
                                       if (image != null) {
                                         setState(() {
                                           _selectedPhotoPath = image.path;
                                         });
                                         ScaffoldMessenger.of(context).showSnackBar(
                                           SnackBar(
                                             content: Text('Photo selected: ${image.name}'),
                                             backgroundColor: Colors.green,
                                           ),
                                         );
                                       }
                                     } catch (e) {
                                       ScaffoldMessenger.of(context).showSnackBar(
                                         SnackBar(
                                           content: Text('Error selecting photo: $e'),
                                           backgroundColor: Colors.red,
                                         ),
                                       );
                                     }
                                   },
                                   child: Container(
                                     width: double.infinity,
                                     height: 80,
                                     decoration: BoxDecoration(
                                       color: Colors.white.withOpacity(0.1),
                                       borderRadius: BorderRadius.circular(8),
                                       border: Border.all(color: Colors.white30, style: BorderStyle.solid),
                                     ),
                                     child: _selectedPhotoPath != null
                                         ? ClipRRect(
                                             borderRadius: BorderRadius.circular(8),
                                             child: Image.file(
                                               File(_selectedPhotoPath!),
                                               fit: BoxFit.cover,
                                               width: double.infinity,
                                               height: 80,
                                             ),
                                           )
                                         : Column(
                                             mainAxisAlignment: MainAxisAlignment.center,
                                             children: [
                                               Icon(Icons.camera_alt, color: Colors.white70, size: 32),
                                               SizedBox(height: 4),
                                               Text(
                                                 'Tap to upload photo',
                                                 style: GoogleFonts.poppins(
                                                   color: Colors.white70,
                                                   fontSize: 12,
                                                 ),
                                               ),
                                             ],
                                           ),
                                   ),
                                 ),
                               ],
                             ),
                           ),
                           SizedBox(height: 12),
                           
                           // Resume Upload
                           Container(
                             padding: EdgeInsets.all(12),
                             decoration: BoxDecoration(
                               color: Colors.white.withOpacity(0.05),
                               borderRadius: BorderRadius.circular(8),
                               border: Border.all(color: Colors.white30),
                             ),
                             child: Column(
                               crossAxisAlignment: CrossAxisAlignment.start,
                               children: [
                                 Text(
                                   'Resume/CV',
                                   style: GoogleFonts.poppins(
                                     color: Colors.white,
                                     fontSize: 14,
                                     fontWeight: FontWeight.w600,
                                   ),
                                 ),
                                 SizedBox(height: 8),
                                 GestureDetector(
                                   onTap: () async {
                                     try {
                                       FilePickerResult? result = await FilePicker.platform.pickFiles(
                                         type: FileType.custom,
                                         allowedExtensions: ['pdf', 'doc', 'docx'],
                                         allowMultiple: false,
                                       );
                                       
                                       if (result != null) {
                                         setState(() {
                                           _selectedResumePath = result.files.single.path;
                                         });
                                         ScaffoldMessenger.of(context).showSnackBar(
                                           SnackBar(
                                             content: Text('Resume selected: ${result.files.single.name}'),
                                             backgroundColor: Colors.green,
                                           ),
                                         );
                                       }
                                     } catch (e) {
                                       ScaffoldMessenger.of(context).showSnackBar(
                                         SnackBar(
                                           content: Text('Error selecting resume: $e'),
                                           backgroundColor: Colors.red,
                                         ),
                                       );
                                     }
                                   },
                                   child: Container(
                                     width: double.infinity,
                                     height: 60,
                                     decoration: BoxDecoration(
                                       color: Colors.white.withOpacity(0.1),
                                       borderRadius: BorderRadius.circular(8),
                                       border: Border.all(color: Colors.white30, style: BorderStyle.solid),
                                     ),
                                     child: _selectedResumePath != null
                                         ? Row(
                                             mainAxisAlignment: MainAxisAlignment.center,
                                             children: [
                                               Icon(Icons.description, color: Colors.green, size: 24),
                                               SizedBox(width: 8),
                                               Expanded(
                                                 child: Text(
                                                   _selectedResumePath!.split('/').last,
                                                   style: GoogleFonts.poppins(
                                                     color: Colors.green,
                                                     fontSize: 12,
                                                     fontWeight: FontWeight.w600,
                                                   ),
                                                   maxLines: 1,
                                                   overflow: TextOverflow.ellipsis,
                                                 ),
                                               ),
                                             ],
                                           )
                                         : Column(
                                             mainAxisAlignment: MainAxisAlignment.center,
                                             children: [
                                               Icon(Icons.upload_file, color: Colors.white70, size: 24),
                                               SizedBox(height: 4),
                                               Text(
                                                 'Tap to upload resume (PDF/DOC)',
                                                 style: GoogleFonts.poppins(
                                                   color: Colors.white70,
                                                   fontSize: 12,
                                                 ),
                                               ),
                                             ],
                                           ),
                                   ),
                                 ),
                               ],
                             ),
                           ),
                        ],
                      ),
                    ),
                    SizedBox(height: 20),
                    
                    // Fee Display
                    Container(
                      padding: EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.yellow.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.yellow.withOpacity(0.5)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.payment, color: Colors.yellow, size: 24),
                          SizedBox(width: 8),
                          Text(
                            'Application Fee: ₹1000',
                            style: GoogleFonts.poppins(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.yellow,
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 20),
                    
                    // Action Buttons
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () => Navigator.of(context).pop(),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                              foregroundColor: Colors.white,
                              padding: EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            child: Text(
                              'Cancel',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(width: 16),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () {
                              // Validate form
                              if (nameController.text.isEmpty ||
                                  emailController.text.isEmpty ||
                                  phoneController.text.isEmpty ||
                                  experienceController.text.isEmpty ||
                                  skillsController.text.isEmpty) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Please fill all fields'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                                return;
                              }
                              
                              if (_selectedPhotoPath == null) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Please select a profile photo'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                                return;
                              }
                              
                              if (_selectedResumePath == null) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Please select a resume'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                                return;
                              }
                              
                              Navigator.of(context).pop();
                              _initiatePayment(job);
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.green,
                              foregroundColor: Colors.white,
                              padding: EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            child: Text(
                              'Pay & Apply',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  void _initiatePayment(Job job) {
    var options = {
      'key': 'rzp_live_fgQr0ACWFbL4pN', // Replace with your Razorpay test key
      'amount': 100000, // Amount in paise (₹1000 = 100000 paise)
      'name': 'PlaySmart Services',
      'description': 'Job Application Fee for ${job.jobTitle}',
      'prefill': {
        'contact': '',
        'email': '',
      },
      'external': {
        'wallets': ['paytm']
      }
    };

    try {
      _razorpay.open(options);
    } catch (e) {
      print('Error opening Razorpay: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening payment gateway'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _uploadFiles(Job job, String photoPath, String resumePath) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      if (token == null) {
        throw Exception('No authentication token found');
      }

      // Create multipart request for photo
      var photoRequest = http.MultipartRequest(
        'POST',
        Uri.parse('https://playsmart.co.in/upload_photo.php'),
      );
      
      photoRequest.headers['Authorization'] = 'Bearer $token';
      photoRequest.files.add(
        await http.MultipartFile.fromPath('photo', photoPath),
      );
      photoRequest.fields['job_id'] = job.id.toString();
      
      var photoResponse = await photoRequest.send();
      var photoResult = await photoResponse.stream.bytesToString();
      print('Photo upload result: $photoResult');

      // Create multipart request for resume
      var resumeRequest = http.MultipartRequest(
        'POST',
        Uri.parse('https://playsmart.co.in/upload_resume.php'),
      );
      
      resumeRequest.headers['Authorization'] = 'Bearer $token';
      resumeRequest.files.add(
        await http.MultipartFile.fromPath('resume', resumePath),
      );
      resumeRequest.fields['job_id'] = job.id.toString();
      
      var resumeResponse = await resumeRequest.send();
      var resumeResult = await resumeResponse.stream.bytesToString();
      print('Resume upload result: $resumeResult');

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Files uploaded successfully'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      print('Error uploading files: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error uploading files: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  void dispose() {
    _animationController.dispose();
    _floatingIconsController.dispose();
    _pulseController.dispose();
    _jobApplicationsScrollController.dispose();
    _autoScrollTimer?.cancel();
    _refreshTimer?.cancel();
    _razorpay.clear();
    super.dispose();
  }



  Future<void> _redirectToLogin() async {
    print('Redirecting to login...');
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setBool('isLoggedIn', false);
    await prefs.remove('token');
    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => SplashScreen()),
      );
    }
  }

  Future<void> updateLastActivity() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token == null) {
      print('Error: No token found for updating last activity');
      return;
    }
    try {
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/update_last_activity.php'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'session_token': token},
      ).timeout(const Duration(seconds: 5));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (!data['success']) {
          print('Failed to update last activity: ${data['message']}');
        }
      } else {
        print('Failed to update last activity: HTTP ${response.statusCode}, Body: ${response.body}');
      }
    } catch (e) {
      print('Error updating last activity: $e');
    }
  }

  Future<void> fetchUserBalance() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token == null) {
      setState(() {
        userBalance = 0.0;
      });
      return;
    }
    try {
      await updateLastActivity();
      final response = await http.get(
        Uri.parse('https://playsmart.co.in/fetch_user_balance.php?session_token=$token'),
      ).timeout(Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          setState(() {
            userBalance = data['data']['wallet_balance'] is double
                ? data['data']['wallet_balance']
                : double.parse(data['data']['wallet_balance'].toString());
          });
        } else {
          setState(() {
            userBalance = 0.0;
          });
        }
      } else {
        setState(() {
          userBalance = 0.0;
        });
      }
    } catch (e) {
      setState(() {
        userBalance = 0.0;
      });
    }
  }



  Future<void> fetchJobApplications() async {
    try {
      print('DEBUG: Starting to fetch job applications...');
      print('DEBUG: API URL: ${JobApplicationController.baseUrl}/fetch_job_applications.php');
      
      final applicationsData = await JobApplicationController.fetchJobApplications();
      print('DEBUG: Received ${applicationsData.length} applications from API');
      
      if (mounted) {
        setState(() {
          jobApplications = applicationsData;
        });
        print('DEBUG: Updated state with ${jobApplications.length} applications');
      }
      print('DEBUG: Fetched ${jobApplications.length} job applications successfully');
    } catch (e) {
      print('Error fetching job applications: $e');
      print('DEBUG: Full error details: $e');
      if (mounted) {
        setState(() {
          // Keep the sample data if API fails
          if (jobApplications.isEmpty) {
            print('DEBUG: API failed, keeping sample data');
          }
        });
      }
      // Don't crash the app, just show empty state
    }
  }

  Future<void> fetchJobs() async {
    try {
      print('DEBUG: Starting to fetch jobs...');
      final jobsData = await JobController.fetchJobs();
      print('DEBUG: Received ${jobsData.length} jobs from API');
      if (mounted) {
        setState(() {
          jobs = jobsData;
        });
        print('DEBUG: Updated state with ${jobs.length} jobs');
      }
      print('DEBUG: Fetched ${jobs.length} jobs successfully');
    } catch (e) {
      print('Error fetching jobs: $e');
      if (mounted) {
        setState(() {
          jobs = [];
        });
      }
    }
  }

  Future<void> fetchContests() async {
    try {
      await updateLastActivity();
      final miniContestsData = await _miniContestController.fetchContests();
      final megaContestsData = await _megaContestController.fetchMegaContests();
      print('DEBUG: Raw Mega Contests Data fetched:');
      megaContestsData.forEach((contest) {
        print('     ID: ${contest.id}, Name: ${contest.name}, Start: ${contest.startDateTime}');
      });

      Map<int, Map<String, dynamic>> newMegaContestStatus = {};
      for (var contest in megaContestsData) {
        try {
          await updateLastActivity();
          final status = await _megaContestController.fetchMegaContestStatus(contest.id);
          print('DEBUG: Fetched status for Contest ID: ${contest.id}, Status: $status');
          final startDateTime = DateTime.tryParse(status['start_datetime'] ?? '') ?? contest.startDateTime ?? DateTime.now();

          final hasSubmitted = status['has_submitted'] ?? false;
          final hasViewedResults = status['has_viewed_results'] ?? false;

          final existingStatus = _megaContestStatus[contest.id];
          newMegaContestStatus[contest.id] = {
            'is_joinable': status['is_joinable'] ?? false,
            'has_joined': status['has_joined'] ?? false,
            'is_active': status['is_active'] ?? false,
            'has_submitted': hasSubmitted,
            'has_viewed_results': hasViewedResults,
            'start_datetime': startDateTime.toIso8601String(),
            'isWinner': existingStatus?['isWinner'] ?? false,
            'isTie': existingStatus?['isTie'] ?? false,
            'opponentName': existingStatus?['opponentName'],
            'opponentScore': existingStatus?['opponentScore'],
            'matchCompleted': existingStatus?['matchCompleted'] ?? false,
          };
        } catch (e) {
          print('ERROR: Failed to fetch status for contest ${contest.id}: $e');
          if (_megaContestStatus.containsKey(contest.id)) {
            newMegaContestStatus[contest.id] = _megaContestStatus[contest.id]!;
            print('DEBUG: Preserving old status for contest ${contest.id} due to error.');
          } else {
            print('DEBUG: Contest ${contest.id} has no prior status and fetch failed. It will be filtered out.');
          }
        }
      }

      setState(() {
        miniContests = miniContestsData;
        _megaContestStatus = newMegaContestStatus;
        print('--- DEBUG: Mega Contest Status after fetch and update ---');
        _megaContestStatus.forEach((id, status) {
          print('Contest ID: $id');
          print('   is_joinable: ${status['is_joinable']}');
          print('   has_joined: ${status['has_joined']}');
          print('   is_active: ${status['is_active']}');
          print('   has_submitted: ${status['has_submitted']}');
          print('   has_viewed_results: ${status['has_viewed_results']}');
          print('   start_datetime: ${status['start_datetime']}');
          print('   isWinner: ${status['isWinner']}');
          print('   isTie: ${status['isTie']}');
          print('   opponentName: ${status['opponentName']}');
          print('   opponentScore: ${status['opponentScore']}');
          print('   matchCompleted: ${status['matchCompleted']}');
        });
        megaContests = megaContestsData.where((contest) {
          final status = _megaContestStatus[contest.id];
          if (status == null) {
            print('DEBUG: Contest ID: ${contest.id} has no status data. Using fallback logic.');
            final startDateTime = contest.startDateTime ?? DateTime.now();
            final now = DateTime.now();
            final minutesUntilStart = startDateTime.difference(now).inMinutes;
            final minutesSinceStart = now.difference(startDateTime).inMinutes;
            final shouldBeVisible = (minutesUntilStart >= -120 && minutesUntilStart <= 30);
            print('DEBUG: Fallback filtering for Contest ID: ${contest.id}, minutesUntilStart: $minutesUntilStart, shouldBeVisible: $shouldBeVisible');
            return shouldBeVisible;
          }
          final hasJoined = status['has_joined'] ?? false;
          final hasSubmitted = status['has_submitted'] ?? false;
          final hasViewedResults = status['has_viewed_results'] ?? false;
          final isJoinable = status['is_joinable'] ?? false;

          final startDateTime = DateTime.tryParse(status['start_datetime'] ?? '') ?? contest.startDateTime ?? DateTime.now();
          final now = DateTime.now();
          final minutesUntilStart = startDateTime.difference(now).inMinutes;
          final minutesSinceStart = now.difference(startDateTime).inMinutes;

          final shouldBeVisible = isJoinable ||
              hasJoined ||
              hasSubmitted ||
              (minutesUntilStart >= -120 && minutesUntilStart <= 30);

          print('DEBUG: Filtering Contest ID: ${contest.id}, Name: ${contest.name}');
          print('   hasJoined: $hasJoined, hasSubmitted: $hasSubmitted, hasViewedResults: $hasViewedResults, isJoinable: $isJoinable');
          print('   startDateTime: $startDateTime, minutesUntilStart: $minutesUntilStart, minutesSinceStart: $minutesSinceStart');
          print('   Result: shouldBeVisible = $shouldBeVisible');
          return shouldBeVisible;
        }).toList();
        print('--- DEBUG: Mega Contests after final filtering ---');
        megaContests.forEach((contest) {
          print('Contest ID: ${contest.id}, Name: ${contest.name}, Type: ${contest.type}');
        });
      });
    } catch (e) {
      print('Error loading contests: $e');
    }
  }

  void _startRefreshTimer() {
    _refreshTimer?.cancel();
    _refreshTimer = Timer.periodic(Duration(seconds: 60), (timer) async {
      if (!mounted) {
        timer.cancel();
        return;
      }
      print('DEBUG: Refresh timer triggered. Fetching user balance, contests, job applications and jobs...');
      try {
      await fetchUserBalance();
      await fetchContests();
        await fetchJobApplications();
        await fetchJobs();
      } catch (e) {
        print('Error in refresh timer: $e');
      }
    });
  }

  void _startAutoScroll() {
    _autoScrollTimer?.cancel();
    _autoScrollTimer = Timer.periodic(Duration(seconds: 3), (timer) {
      if (!mounted || jobApplications.length <= 3) {
        timer.cancel();
        return;
      }
      
      if (_jobApplicationsScrollController.hasClients) {
        final maxScroll = _jobApplicationsScrollController.position.maxScrollExtent;
        final currentScroll = _jobApplicationsScrollController.position.pixels;
        
        if (currentScroll >= maxScroll) {
          // Reset to beginning when reaching the end
          _jobApplicationsScrollController.animateTo(
            0,
            duration: Duration(milliseconds: 500),
            curve: Curves.easeInOut,
          );
        } else {
          // Scroll to next item
          _jobApplicationsScrollController.animateTo(
            currentScroll + 215, // Width of card + margin
            duration: Duration(milliseconds: 500),
            curve: Curves.easeInOut,
          );
        }
      }
    });
  }

  void _pauseAutoScroll() {
    _autoScrollTimer?.cancel();
  }

  void _resumeAutoScroll() {
    _startAutoScroll();
  }

  Future<String?> getMatchId(int contestId) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token == null) {
      print('ERROR: No token found for fetching match ID');
      return null;
    }
    try {
      await updateLastActivity();
      final response = await http.get(
        Uri.parse('https://playsmart.co.in/mega/get_match_id.php?session_token=$token&contest_id=$contestId&contest_type=mega'),
      ).timeout(Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          final matchId = data['match_id']?.toString();
          if (matchId != null && matchId.isNotEmpty) {
            print('DEBUG: Fetched match ID: $matchId for contest $contestId');
            return matchId;
          } else {
            print('ERROR: Match ID not provided by server for contest $contestId');
            return null;
          }
        } else {
          print('ERROR: Failed to fetch match ID for contest $contestId: ${data['message']}');
          return null;
        }
      } else {
        print('ERROR: Failed to fetch match ID for contest $contestId: HTTP ${response.statusCode}, Body: ${response.body}');
        return null;
      }
    } catch (e) {
      print('ERROR: Error fetching match ID for contest $contestId: $e');
      return null;
    }
  }

  Future<void> _showRankingsPopup(Contest contest) async {
    try {
      final rankings = await _megaContestController.fetchContestRankings(contest.id);
      _contestRankings[contest.id] = rankings;

      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Text(
              'Contest Rankings',
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            content: Container(
              width: double.maxFinite,
              height: 300,
              child: Column(
                children: [
                  Text(
                    contest.name,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  SizedBox(height: 10),
                  Expanded(
                    child: ListView.builder(
                      itemCount: rankings.length,
                      itemBuilder: (context, index) {
                        final ranking = rankings[index];
                        return Card(
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: _getRankColor(ranking['rank_start']),
                              child: Text(
                                '${ranking['rank_start']}',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                            title: Text(
                              'Rank ${ranking['rank_start']} - ${ranking['rank_end']}',
                              style: GoogleFonts.poppins(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            trailing: Text(
                              '₹${ranking['prize_amount']}',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.green,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () {
                  Navigator.of(context).pop();
                  joinContest(contest);
                },
                child: Text('Join Now'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          );
        },
      );
    } catch (e) {
      print('Error fetching rankings: $e');
      joinContest(contest);
    }
  }

  Color _getRankColor(int rank) {
    if (rank == 1) return Colors.amber;
    if (rank == 2) return Colors.grey;
    if (rank == 3) return Colors.brown;
    return Colors.blue;
  }

  Future<void> joinContest(Contest contest) async {
    if (userBalance < contest.entryFee) {
      print('ERROR: Insufficient balance to join contest ${contest.id}');
      return;
    }

    try {
      await updateLastActivity();
      final joinData = contest.type == 'mega'
          ? await _megaContestController.joinMegaContest(contest.id, contest.entryFee)
          : await _miniContestController.joinContest(contest.id, contest.entryFee, contest.type);

      final String? matchId = joinData['match_id']?.toString();
      if (matchId == null || matchId.isEmpty) {
        print('ERROR: Match ID not received from server for contest ${contest.id}');
        return;
      }

      setState(() {
        userBalance -= contest.entryFee;
        if (contest.type == 'mega') {
          _megaContestStatus[contest.id] = {
            'is_joinable': false,
            'has_joined': true,
            'is_active': false,
            'has_submitted': false,
            'has_viewed_results': false,
            'start_datetime': contest.startDateTime?.toIso8601String() ?? _megaContestStatus[contest.id]?['start_datetime'] ?? DateTime.now().toIso8601String(),
            'isWinner': false,
            'isTie': false,
            'opponentName': null,
            'opponentScore': null,
            'matchCompleted': false,
          };
        }
      });

      if (contest.type == 'mega') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Successfully joined Mega Contest. Wait for the start time.')),
        );
        fetchContests();
      } else {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => QuizScreen(
              contestId: contest.id,
              contestName: contest.name,
              contestType: contest.type,
              entryFee: contest.entryFee,
              prizePool: contest.prizePool,
              matchId: matchId,
              initialIsBotOpponent: joinData['is_bot'] ?? false,
              initialOpponentName: joinData['opponent_name'],
              initialAllPlayersJoined: joinData['all_players_joined'] ?? false,
            ),
          ),
        );
      }
    } catch (e) {
      print('Error joining contest ${contest.id}: $e');
    }
  }

  Future<void> startMegaContest(Contest contest) async {
    await updateLastActivity();
    final matchId = await getMatchId(contest.id);
    if (matchId == null) {
      print('ERROR: Match ID not found for contest ${contest.id}');
      return;
    }

    try {
      final result = await _megaContestController.startMegaContest(contest.id, matchId);
      if (result['success']) {
        setState(() {
          _megaContestStatus[contest.id]!['is_active'] = true;
        });

        final quizResult = await Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => MegaQuizScreen(
              contestId: contest.id,
              contestName: contest.name,
              contestType: contest.type,
              entryFee: contest.entryFee,
              numQuestions: contest.numQuestions,
              matchId: matchId,
            ),
          ),
        );

        if (quizResult != null && quizResult is Map<String, dynamic> && quizResult['success'] == true) {
          setState(() {
            _megaContestStatus[contest.id]!['has_submitted'] = quizResult['hasSubmitted'] ?? true;
            _megaContestStatus[contest.id]!['has_viewed_results'] = quizResult['hasViewedResults'] ?? false;
            _megaContestStatus[contest.id]!['is_active'] = false;
            _megaContestStatus[contest.id]!['isWinner'] = quizResult['isWinner'] ?? false;
            _megaContestStatus[contest.id]!['isTie'] = quizResult['isTie'] ?? false;
            _megaContestStatus[contest.id]!['opponentName'] = quizResult['opponentName'];
            _megaContestStatus[contest.id]!['opponentScore'] = quizResult['opponentScore'];
            _megaContestStatus[contest.id]!['matchCompleted'] = quizResult['matchCompleted'] ?? false;
            print('DEBUG: Updated _megaContestStatus after quiz submission for contest ${contest.id}:');
            print('    has_submitted: ${_megaContestStatus[contest.id]!['has_submitted']}');
            print('    has_viewed_results: ${_megaContestStatus[contest.id]!['has_viewed_results']}');
          });
          Future.delayed(Duration(seconds: 2), () {
            if (mounted) {
              fetchContests();
            }
          });
        } else {
          setState(() {
            _megaContestStatus[contest.id]!['is_active'] = false;
          });
          fetchContests();
        }
      } else {
        print('Error starting contest ${contest.id}: ${result['message']}');
      }
    } catch (e) {
      print('Error starting contest ${contest.id}: $e');
    }
  }

  Future<void> viewMegaResults(Contest contest) async {
    try {
      await updateLastActivity();
      final matchId = await getMatchId(contest.id);
      if (matchId == null) {
        print('ERROR: Match ID not found for contest ${contest.id}');
        return;
      }

      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      if (token == null) {
        print('ERROR: No token found for contest ${contest.id}');
        return;
      }

      final response = await http.get(
        Uri.parse('https://playsmart.co.in/mega/fetch_results.php?session_token=$token&contest_id=${contest.id}&match_id=$matchId'),
      ).timeout(Duration(seconds: 10));

      if (response.statusCode != 200) {
        print('ERROR: Failed to fetch results for contest ${contest.id}: HTTP ${response.statusCode}');
        return;
      }

      final resultData = jsonDecode(response.body);
      if (!resultData['success']) {
        print('ERROR: Failed to fetch results for contest ${contest.id}: ${resultData['message']}');
        return;
      }

      double? parseToDouble(dynamic value) {
        if (value == null) return null;
        if (value is num) return value.toDouble();
        if (value is String) return double.tryParse(value);
        return null;
      }

      final resultViewed = await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => MegaResultScreen(
            contestId: contest.id,
            contestName: contest.name,
            numQuestions: contest.numQuestions,
            matchId: matchId,
            userScore: parseToDouble(resultData['user_score']),
            prizeWon: parseToDouble(resultData['prize_won']),
            isWinner: resultData['is_winner'] ?? false,
            isTie: resultData['is_tie'] ?? false,
            opponentName: resultData['opponent_name'],
            opponentScore: parseToDouble(resultData['opponent_score']),
          ),
        ),
      );

      if (resultViewed == true) {
        setState(() {
          _megaContestStatus[contest.id]!['has_viewed_results'] = true;
          _megaContestStatus[contest.id]!['isWinner'] = resultData['is_winner'] ?? false;
          _megaContestStatus[contest.id]!['isTie'] = resultData['is_tie'] ?? false;
          _megaContestStatus[contest.id]!['opponentName'] = resultData['opponent_name'];
          _megaContestStatus[contest.id]!['opponentScore'] = parseToDouble(resultData['opponent_score']);
          print('DEBUG: Set has_viewed_results to true for contest ${contest.id}');
        });

        print('DEBUG: Results viewed for contest ${contest.id}');
      }

      Future.delayed(Duration(seconds: 1), () {
        if (mounted) {
          fetchContests();
        }
      });
    } catch (e) {
      print('ERROR: Failed to view results for contest ${contest.id}: $e');
    }
  }

  Widget _buildFloatingIcon(int index) {
    final icons = [
      Icons.lightbulb_outline,
      Icons.emoji_events,
      Icons.school,
      Icons.psychology,
      Icons.extension,
      Icons.star,
      Icons.auto_awesome,
      Icons.emoji_objects,
    ];
    final sizes = [30.0, 40.0, 25.0, 35.0, 45.0];
    return Icon(
      icons[index % icons.length],
      color: Colors.grey,
      size: sizes[index % sizes.length],
    );
  }

  Widget _buildJobApplicationCard(JobApplication application) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: Offset(0, 5),
          ),
        ],
      ),
              child: Padding(
          padding: EdgeInsets.all(8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              // Company Name and Logo
              Row(
                children: [
                  Container(
                    width: 24,
                    height: 24,
                    decoration: BoxDecoration(
                      color: Colors.grey[200],
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: application.companyLogoUrl.isNotEmpty
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: Image.network(
                              application.companyLogoUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) {
                                return Icon(
                                  Icons.business,
                                  color: Colors.grey[600],
                                  size: 16,
                                );
                              },
                            ),
                          )
                        : Icon(
                            Icons.business,
                            color: Colors.grey[600],
                            size: 16,
                          ),
                  ),
                  SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      application.companyName,
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            SizedBox(height: 4),
            // Student Name
            Text(
              application.studentName,
              style: GoogleFonts.poppins(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: Colors.black87,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 2),
            // District
            Text(
              application.district,
              style: GoogleFonts.poppins(
                fontSize: 10,
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 4),
            // Package
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 11),
                SizedBox(width: 2),
                Text(
                  application.package,
                  style: GoogleFonts.poppins(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 4),
            // Profile
            Text(
              '💼 ${application.profile}',
              style: GoogleFonts.poppins(
                fontSize: 10,
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 3),
            // Application Status
            Container(
              padding: EdgeInsets.symmetric(horizontal: 4, vertical: 1),
              decoration: BoxDecoration(
                color: _getStatusColor(application.applicationStatus).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: _getStatusColor(application.applicationStatus).withOpacity(0.3),
                ),
              ),
              child: Text(
                _getStatusText(application.applicationStatus),
                style: GoogleFonts.poppins(
                  fontSize: 8,
                  fontWeight: FontWeight.w600,
                  color: _getStatusColor(application.applicationStatus),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }



  Widget _buildJobCard(Job job) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: Offset(0, 5),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Company Logo
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: job.companyLogoUrl.isNotEmpty
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.network(
                            job.companyLogoUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return Icon(
                                Icons.business,
                                color: Colors.grey[600],
                                size: 24,
                              );
                            },
                          ),
                        )
                      : Icon(
                          Icons.business,
                          color: Colors.grey[600],
                          size: 24,
                        ),
                ),
                SizedBox(width: 12),
                // Company Name
                Expanded(
                  child: Text(
                    job.companyName,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            SizedBox(height: 8),
            // Job Title
            Text(
              job.jobTitle,
              style: GoogleFonts.poppins(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Colors.black87,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 8),
            // Package
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 16),
                SizedBox(width: 4),
                Text(
                  job.package,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 4),
            // Location
            Row(
              children: [
                Icon(Icons.location_on, color: Colors.grey[600], size: 16),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    job.location,
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            SizedBox(height: 8),
            // Apply Button or Status
            if (userJobApplications.containsKey(job.id))
              GestureDetector(
                onTap: () => _showJobStatusModal(job),
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.blue.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.blue.withOpacity(0.3)),
                  ),
                  child: Text(
                    'Status',
                    style: GoogleFonts.poppins(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: Colors.blue[700],
                    ),
                  ),
                ),
              )
            else
              GestureDetector(
                onTap: () => _showJobApplicationModal(job),
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.green.withOpacity(0.3)),
                  ),
                  child: Text(
                    'Apply',
                    style: GoogleFonts.poppins(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: Colors.green[700],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildAnimatedIconButton({required IconData icon, required VoidCallback onPressed}) {
    return AnimatedBuilder(
      animation: _pulseController,
      builder: (context, child) {
        return Container(
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.15),
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.white.withOpacity(0.1 + (_pulseController.value * 0.05)),
                blurRadius: 10 + (_pulseController.value * 5),
                spreadRadius: 1 + (_pulseController.value * 1),
              ),
            ],
          ),
          child: IconButton(
            icon: Icon(icon, color: Colors.white, size: 24),
            onPressed: onPressed,
            splashColor: Colors.white.withOpacity(0.3),
            highlightColor: Colors.white.withOpacity(0.2),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final sortedMiniContests = miniContests.toList()
      ..sort((a, b) => b.entryFee.compareTo(a.entryFee));

    return Scaffold(
      body: Stack(
        children: [
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
          ),
          ...List.generate(10, (index) {
            return Positioned(
              top: 100 + (index * 70),
              left: (index % 2 == 0) ? -20 : null,
              right: (index % 2 == 1) ? -20 : null,
              child: AnimatedBuilder(
                animation: _floatingIconsController,
                builder: (context, child) {
                  return Transform.translate(
                    offset: Offset(
                      math.sin((_floatingIconsController.value * 2 * math.pi) + index) * 30,
                      math.cos((_floatingIconsController.value * 2 * math.pi) + index + 1) * 20,
                    ),
                    child: Opacity(
                      opacity: 0.15,
                      child: _buildFloatingIcon(index),
                    ),
                  );
                },
              ),
            );
          }),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header content
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      FadeTransition(
                        opacity: _fadeAnimation,
                        child: _buildAnimatedIconButton(
                          icon: Icons.person,
                          onPressed: () async {
                            HapticFeedback.selectionClick();
                            SharedPreferences prefs = await SharedPreferences.getInstance();
                            String? token = prefs.getString('token');
                            print('Token for profile: $token');
                            if (token != null) {
                              await updateLastActivity();
                              Navigator.push(
                                context,
                                PageRouteBuilder(
                                  pageBuilder: (context, animation, secondaryAnimation) => ProfileScreen(token: token),
                                  transitionsBuilder: (context, animation, secondaryAnimation, child) {
                                    var begin = Offset(1.0, 0.0);
                                    var end = Offset.zero;
                                    var curve = Curves.easeOutQuint;
                                    var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
                                    return SlideTransition(position: animation.drive(tween), child: child);
                                  },
                                  transitionDuration: Duration(milliseconds: 500),
                                ),
                              ).then((_) {
                                fetchUserBalance();
                              });
                            } else {
                              await _redirectToLogin();
                            }
                          },
                        ),
                      ),
                      FadeTransition(
                        opacity: _fadeAnimation,
                        child: Text(
                          'Play Smart Services',
                          style: GoogleFonts.poppins(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                            shadows: [
                              Shadow(
                                color: Colors.black.withOpacity(0.3),
                                offset: Offset(0, 2),
                                blurRadius: 4,
                              ),
                            ],
                          ),
                        ),
                      ),
                      SizedBox(width: 60), // Balance the layout
                    ],
                  ),
                  
                  // Scrollable main content
                  Expanded(
                    child: SingleChildScrollView(
                      physics: BouncingScrollPhysics(),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Job Applications Section (First Container)
                          Text(
                            'Job Applications',
                            style: GoogleFonts.poppins(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 8),
                          Container(
                            height: 150,
                            child: jobApplications.isNotEmpty 
                              ? GestureDetector(
                                  onPanStart: (_) => _pauseAutoScroll(),
                                  onPanEnd: (_) => Future.delayed(Duration(seconds: 2), _resumeAutoScroll),
                                  child: ListView.builder(
                                    controller: _jobApplicationsScrollController,
                                    scrollDirection: Axis.horizontal,
                                    physics: BouncingScrollPhysics(),
                                    itemCount: jobApplications.length > 3 ? 3 : jobApplications.length,
                                    itemBuilder: (context, index) {
                                      final application = jobApplications[index];
                                      return Container(
                                        width: 200,
                                        margin: EdgeInsets.only(right: 15),
                                        child: _buildJobApplicationCard(application),
                                      );
                                    },
                                  ),
                                )
                              : Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.work_outline,
                                        color: Colors.white70,
                                        size: 28,
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        'No applications available',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white70,
                                          fontSize: 14,
                                        ),
                                      ),
                                      SizedBox(height: 4),
                                      Text(
                                        'Debug: ${jobApplications.length} apps loaded',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white60,
                                          fontSize: 10,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                          ),
                        
                          SizedBox(height: 8),
                          // Popular Jobs Section
                          Container(
                            padding: EdgeInsets.symmetric(vertical: 8),
                            child: Text(
                              'Popular Jobs',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          SizedBox(height: 8),
                          Container(
                            height: 190,
                            child: jobs.isNotEmpty 
                              ? ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(),
                                  itemCount: jobs.length,
                                  itemBuilder: (context, index) {
                                    final job = jobs[index];
                                    return Container(
                                      width: 200,
                                      margin: EdgeInsets.only(right: 15),
                                      child: _buildJobCard(job),
                                    );
                                  },
                                )
                              : Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.business_center_outlined,
                                        color: Colors.white70,
                                        size: 28,
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        'No jobs available',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white70,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                          ),
                          SizedBox(height: 12),
                          // Contests Section
                          if (megaContests.isNotEmpty) ...[
                            Text(
                              'Mega Contests',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            SizedBox(height: 10),
                            ...megaContests.asMap().entries.map((entry) {
                              final index = entry.key;
                              final contest = entry.value;
                              return _buildContestCard(contest, index, isMega: true);
                            }),
                          ],
                          if (sortedMiniContests.isNotEmpty) ...[
                            SizedBox(height: 20),
                            Text(
                              'Mini Contests',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            SizedBox(height: 10),
                            ...sortedMiniContests.asMap().entries.map((entry) {
                              final index = entry.key;
                              final contest = entry.value;
                              return _buildContestCard(contest, index, isMega: false);
                            }),
                          ],
                          if (megaContests.isEmpty && sortedMiniContests.isEmpty)
                            Center(child: CircularProgressIndicator(color: Colors.white)),
                          
                          // Bottom spacing for scrollable content
                          SizedBox(height: 30),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContestCard(Contest contest, int index, {required bool isMega}) {
    final status = _megaContestStatus[contest.id] ?? {};
    final isJoinable = status['is_joinable'] ?? false;
    final hasJoined = status['has_joined'] ?? false;
    final isActive = status['is_active'] ?? false;
    final hasSubmitted = status['has_submitted'] ?? false;
    final hasViewedResults = status['has_viewed_results'] ?? false;
    final startDateTime = DateTime.tryParse(status['start_datetime'] ?? '') ?? contest.startDateTime ?? DateTime.now();
    final isStartTimeReached = DateTime.now().difference(startDateTime).inSeconds >= 0;
    final minutesUntilStart = startDateTime.difference(DateTime.now()).inMinutes;

    final bool isStartWindowOpen = isActive && !hasSubmitted;
    final bool canJoinMega = isMega && isJoinable && !hasJoined && minutesUntilStart > 1;
    final bool canStartMega = isMega && hasJoined && isStartTimeReached && !hasSubmitted;
    final bool canViewResultsMega = isMega && hasSubmitted && !hasViewedResults;

    final gradient = cardGradients[index % cardGradients.length];

    print('DEBUG: Building Card for Contest ID: ${contest.id}, Name: ${contest.name}');
    print('    isJoinable: $isJoinable, hasJoined: $hasJoined, isActive: $isActive, hasSubmitted: $hasSubmitted, hasViewedResults: $hasViewedResults');
    print('    startDateTime: $startDateTime, isStartTimeReached: $isStartTimeReached, minutesUntilStart: $minutesUntilStart');
    print('    isStartWindowOpen: $isStartWindowOpen');
    print('    canJoinMega: $canJoinMega, canStartMega: $canStartMega, canViewResultsMega: $canViewResultsMega');

    String buttonText;
    Color buttonColor;
    bool buttonEnabled;

    if (isMega) {
      final hasStatusData = _megaContestStatus.containsKey(contest.id);

      if (!hasStatusData) {
        if (minutesUntilStart > 1 && minutesUntilStart <= 30) {
          buttonText = 'Join Now';
          buttonColor = Colors.green;
          buttonEnabled = true;
        } else if (minutesUntilStart <= 1 && minutesUntilStart > -120) {
          buttonText = 'Joining Closed';
          buttonColor = Colors.grey.withOpacity(0.5);
          buttonEnabled = false;
        } else if (minutesUntilStart <= 0 && minutesUntilStart > -120) {
          buttonText = 'Start Now';
          buttonColor = Colors.green;
          buttonEnabled = true;
        } else {
          buttonText = 'Joining Closed';
          buttonColor = Colors.grey.withOpacity(0.5);
          buttonEnabled = false;
        }
      } else if (canViewResultsMega) {
        buttonText = 'View Result';
        buttonColor = Colors.blue;
        buttonEnabled = true;
      } else if (canStartMega) {
        buttonText = 'Start Now';
        buttonColor = Colors.green;
        buttonEnabled = true;
      } else if (hasJoined && !hasSubmitted) {
        if (minutesUntilStart > 0) {
          buttonText = 'Waiting to Start (${minutesUntilStart}m)';
          buttonColor = Colors.orange;
          buttonEnabled = false;
        } else if (isStartTimeReached) {
          buttonText = 'Start Now';
          buttonColor = Colors.green;
          buttonEnabled = true;
        } else {
          buttonText = 'Waiting to Start';
          buttonColor = Colors.orange;
          buttonEnabled = false;
        }
      } else if (canJoinMega) {
        buttonText = 'Join Now';
        buttonColor = Colors.green;
        buttonEnabled = true;
      } else if (hasSubmitted && hasViewedResults) {
        buttonText = 'View Result Again';
        buttonColor = Colors.blue;
        buttonEnabled = true;
      } else if (isMega && !hasJoined && minutesUntilStart <= 1 && minutesUntilStart > -120) {
        buttonText = 'Joining Closed';
        buttonColor = Colors.grey.withOpacity(0.5);
        buttonEnabled = false;
      } else if (isMega && !hasJoined && minutesUntilStart > 1 && minutesUntilStart <= 30) {
        buttonText = 'Joining Soon (${minutesUntilStart}m)';
        buttonColor = Colors.grey.withOpacity(0.5);
        buttonEnabled = false;
      } else {
        buttonText = 'Joining Closed';
        buttonColor = Colors.grey.withOpacity(0.5);
        buttonEnabled = false;
      }
    } else {
      buttonText = 'Join Now';
      buttonColor = Colors.green;
      buttonEnabled = true;
    }

    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _slideAnimation,
        child: GestureDetector(
          onTap: buttonEnabled
              ? () {
                  if (isMega) {
                    final hasStatusData = _megaContestStatus.containsKey(contest.id);

                    if (!hasStatusData) {
                      if (minutesUntilStart > 1 && minutesUntilStart <= 30) {
                        _showRankingsPopup(contest);
                      } else if (minutesUntilStart <= 0 && minutesUntilStart > -120) {
                        startMegaContest(contest);
                      }
                    } else if (canViewResultsMega || (hasSubmitted && hasViewedResults)) {
                      viewMegaResults(contest);
                    } else if (canStartMega) {
                      startMegaContest(contest);
                    } else if (canJoinMega) {
                      _showRankingsPopup(contest);
                    }
                  } else {
                    joinContest(contest);
                  }
                }
              : null,
          child: Container(
            margin: EdgeInsets.only(bottom: 20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: gradient,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.2),
                  blurRadius: 10,
                  offset: Offset(0, 5),
                ),
              ],
            ),
            child: Stack(
              children: [
                Positioned(
                  top: -20,
                  right: -20,
                  child: Opacity(
                    opacity: 0.2,
                    child: Icon(
                      Icons.star,
                      size: 100,
                      color: Colors.white,
                    ),
                  ),
                ),
                Padding(
                  padding: EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: Colors.black.withOpacity(0.3),
                              borderRadius: BorderRadius.circular(15),
                            ),
                            child: Text(
                              contest.type.toUpperCase(),
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          AnimatedBuilder(
                            animation: _pulseController,
                            builder: (context, child) {
                              return Transform.scale(
                                scale: 1.0 + (_pulseController.value * 0.1),
                                child: Container(
                                  padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.2),
                                    borderRadius: BorderRadius.circular(15),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(
                                        Icons.monetization_on,
                                        color: Colors.amber,
                                        size: 16,
                                      ),
                                      SizedBox(width: 5),
                                      Text(
                                        '₹${contest.entryFee.toStringAsFixed(2)}',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white,
                                          fontSize: 12,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                      SizedBox(height: 10),
                      Text(
                        contest.name,
                        style: GoogleFonts.poppins(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          shadows: [
                            Shadow(
                              color: Colors.black.withOpacity(0.3),
                              offset: Offset(0, 2),
                              blurRadius: 4,
                            ),
                          ],
                        ),
                      ),
                      SizedBox(height: 10),
                      if (isMega) ...[
                        Text(
                          'Start: ${startDateTime.toLocal().toString().split('.')[0] ?? 'N/A'}',
                          style: GoogleFonts.poppins(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          'Players: ${contest.numPlayers}',
                          style: GoogleFonts.poppins(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          'Questions: ${contest.numQuestions}',
                          style: GoogleFonts.poppins(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                        if (contest.totalWinningAmount != null)
                          Text(
                            'Total Winning Amount: ₹${contest.totalWinningAmount!.toStringAsFixed(2)}',
                            style: GoogleFonts.poppins(
                              color: Colors.white70,
                              fontSize: 14,
                            ),
                          ),
                      ] else ...[
                        Row(
                          children: [
                            Icon(
                              Icons.account_balance_wallet,
                              color: Colors.white70,
                              size: 18,
                            ),
                            SizedBox(width: 5),
                            Text(
                              'Prize Pool: ₹${contest.prizePool.toStringAsFixed(2)}',
                              style: GoogleFonts.poppins(
                                color: Colors.white70,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                      ],
                      SizedBox(height: 15),
                      Align(
                        alignment: Alignment.centerRight,
                        child: ElevatedButton(
                          onPressed: buttonEnabled
                              ? () {
                                  if (isMega) {
                                    if (canViewResultsMega) {
                                      viewMegaResults(contest);
                                    } else if (canStartMega) {
                                      startMegaContest(contest);
                                    } else if (canJoinMega) {
                                      _showRankingsPopup(contest);
                                    }
                                  } else {
                                    joinContest(contest);
                                  }
                                }
                              : null,
                          child: Text(
                            buttonText,
                            style: GoogleFonts.poppins(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: buttonColor,
                            foregroundColor: Colors.white,
                            padding: EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(15),
                            ),
                            elevation: 5,
                            shadowColor: Colors.black.withOpacity(0.3),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showJobStatusModal(Job job) {
    final status = userJobApplications[job.id] ?? 'pending';
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.transparent,
          contentPadding: EdgeInsets.zero,
          content: Container(
            width: MediaQuery.of(context).size.width * 0.9,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 20,
                  offset: Offset(0, 10),
                ),
              ],
            ),
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Job Header
                  Row(
                    children: [
                      Container(
                        width: 50,
                        height: 50,
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: job.companyLogoUrl.isNotEmpty
                            ? ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: Image.network(
                                  job.companyLogoUrl,
                                  fit: BoxFit.cover,
                                  errorBuilder: (context, error, stackTrace) {
                                    return Icon(
                                      Icons.business,
                                      color: Colors.white70,
                                      size: 30,
                                    );
                                  },
                                ),
                              )
                            : Icon(
                                Icons.business,
                                color: Colors.white70,
                                size: 30,
                              ),
                      ),
                      SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              job.jobTitle,
                              style: GoogleFonts.poppins(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            SizedBox(height: 4),
                            Text(
                              job.companyName,
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: Colors.white70,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 24),
                  
                  // Status Progress Bar
                  Text(
                    'Application Status',
                    style: GoogleFonts.poppins(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  SizedBox(height: 20),
                  
                  // Progress Steps
                  _buildProgressStep('Application Submitted', true, 0),
                  _buildProgressStep('Screening In Progress', status == 'pending' || status == 'shortlisted' || status == 'accepted', 1),
                  _buildProgressStep('Interview Scheduled', status == 'shortlisted' || status == 'accepted', 2),
                  _buildProgressStep('Offer Letter Pending', status == 'accepted', 3),
                  _buildProgressStep('Hired', status == 'accepted', 4),
                  
                  SizedBox(height: 24),
                  
                  // Current Status
                  Container(
                    padding: EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          _getStatusIcon(status),
                          color: _getStatusColor(status),
                          size: 24,
                        ),
                        SizedBox(width: 12),
                        Text(
                          'Current Status: ${_getStatusText(status)}',
                          style: GoogleFonts.poppins(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  
                  SizedBox(height: 24),
                  
                  // Close Button
                  ElevatedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Color(0xFF6A11CB),
                      padding: EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text(
                      'Close',
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildProgressStep(String title, bool isActive, int step) {
    final isCompleted = step <= _getCurrentStep(userJobApplications[_currentJobApplication?.id] ?? 'pending');
    
    return Container(
      margin: EdgeInsets.only(bottom: 16),
      child: Row(
        children: [
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              color: isCompleted ? Colors.green : Colors.white.withOpacity(0.3),
              borderRadius: BorderRadius.circular(12),
            ),
            child: isCompleted 
                ? Icon(Icons.check, color: Colors.white, size: 16)
                : null,
          ),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              title,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: isCompleted ? Colors.white : Colors.white70,
                fontWeight: isCompleted ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
          ),
        ],
      ),
    );
  }

  int _getCurrentStep(String status) {
    switch (status) {
      case 'pending':
        return 1;
      case 'shortlisted':
        return 2;
      case 'accepted':
        return 4;
      default:
        return 0;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status) {
      case 'pending':
        return Icons.hourglass_empty;
      case 'shortlisted':
        return Icons.thumb_up;
      case 'accepted':
        return Icons.check_circle;
      default:
        return Icons.info;
    }
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'shortlisted':
        return Colors.blue;
      case 'accepted':
        return Colors.green;
      default:
        return Colors.white;
    }
  }

  String _getStatusText(String status) {
    switch (status) {
      case 'pending':
        return 'Under Review';
      case 'shortlisted':
        return 'Shortlisted';
      case 'accepted':
        return 'Accepted';
      default:
        return 'Pending';
    }
  }


}