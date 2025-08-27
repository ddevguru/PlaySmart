import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:playsmart/Auth/login_screen.dart';
import 'package:playsmart/Models/contest.dart';
import 'package:playsmart/Models/job.dart';
import 'package:playsmart/Models/job_application.dart';
import 'package:playsmart/Models/mega_contest.dart';
import 'package:playsmart/Models/question.dart';
import 'package:playsmart/local_jobs_screen.dart';
import 'package:playsmart/controller/featured_content_controller.dart';
import 'package:playsmart/controller/job_application_controller.dart';
import 'package:playsmart/controller/job_controller.dart';
import 'package:playsmart/controller/mega-contest-controller.dart';
import 'package:playsmart/controller/mini-contest-controller.dart';
import 'package:playsmart/mega_quiz_screen.dart';
import 'package:playsmart/mega_result_screen.dart';
import 'package:playsmart/mega_score_service.dart';
import 'package:playsmart/profile_Screen.dart';
import 'package:playsmart/quiz_screen.dart';
import 'package:playsmart/score_service.dart';
import 'package:playsmart/splash_screen.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';



class MainScreen extends StatefulWidget {
  const MainScreen({Key? key}) : super(key: key);

  @override
  _MainScreenState createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> with TickerProviderStateMixin, WidgetsBindingObserver {
  late AnimationController _animationController;
  late AnimationController _floatingIconsController;
  late AnimationController _pulseController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;
  late ScrollController _jobApplicationsScrollController;
  Timer? _autoScrollTimer; // Make nullable
  double userBalance = 0.0;
  List<Contest> miniContests = [];
  List<Contest> megaContests = [];
  List<Job> jobs = [];
  List<Job> higherPackageJobs = [];
  List<Job> localJobs = [];
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
  bool _showReferralField = false;

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
    
    // Initialize Razorpay
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
    
    // Initialize animations
    _animationController = AnimationController(
      duration: Duration(seconds: 2),
      vsync: this,
    );
    
    _floatingIconsController = AnimationController(
      duration: Duration(seconds: 3),
      vsync: this,
    );
    
    _pulseController = AnimationController(
      duration: Duration(seconds: 1),
      vsync: this,
    );
    
    _jobApplicationsScrollController = ScrollController();
    
    // Initialize fade animation
    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));
    
    // Initialize slide animation
    _slideAnimation = Tween<Offset>(
      begin: Offset(0, 0.5),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOut,
    ));
    
    // Start animations
    _animationController.repeat();
    _floatingIconsController.repeat();
    _pulseController.repeat();
    
    // Check login status first before initializing data
    _checkLoginStatusAndInitialize();
    
    // Set up periodic token validation (much less frequent to avoid logout issues)
    Timer.periodic(Duration(minutes: 30), (timer) {
      validateAndRefreshToken();
    });
    
    // Set up periodic last activity update (much less frequent)
    Timer.periodic(Duration(minutes: 15), (timer) {
      updateLastActivity();
    });
    
    // Add app lifecycle listener for session tracking
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    _animationController.dispose();
    _floatingIconsController.dispose();
    _pulseController.dispose();
    _jobApplicationsScrollController.dispose();
    _autoScrollTimer?.cancel(); // Safe cancel
    _autoScrollTimer = null; // Set to null
    _refreshTimer?.cancel();
    _razorpay.clear();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    
    switch (state) {
      case AppLifecycleState.resumed:
        print('🔐 DEBUG: App resumed - checking session status...');
        _checkSessionStatus();
        
        // CRITICAL FIX: Recover session if needed
        _recoverSessionIfNeeded();
        break;
      case AppLifecycleState.paused:
        print('🔐 DEBUG: App paused - updating last activity...');
        updateLastActivity();
        break;
      case AppLifecycleState.inactive:
        print('🔐 DEBUG: App inactive');
        break;
      case AppLifecycleState.detached:
        print('🔐 DEBUG: App detached');
        break;
      case AppLifecycleState.hidden:
        print('🔐 DEBUG: App hidden');
        break;
    }
  }

  // CRITICAL FIX: Session recovery method
  Future<void> _recoverSessionIfNeeded() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final isLoggedIn = prefs.getBool('isLoggedIn') ?? false;
      
      print('🔐 DEBUG: === SESSION RECOVERY CHECK ===');
      print('🔐 DEBUG: Token exists: ${token != null}');
      print('🔐 DEBUG: isLoggedIn flag: $isLoggedIn');
      
      // If we have a token but isLoggedIn is false, recover the session
      if (token != null && !isLoggedIn) {
        print('🔐 DEBUG: 🔄 Session recovery needed! Token exists but isLoggedIn is false');
        await prefs.setBool('isLoggedIn', true);
        print('🔐 DEBUG: ✅ Session recovered! isLoggedIn set to true');
        
        // CRITICAL: Backup session data after recovery
        await _backupSessionData(token);
        print('🔐 DEBUG: ✅ Session data backed up after recovery');
        
        // Also ensure we're on the main screen, not login screen
        if (mounted) {
          print('🔐 DEBUG: 🔄 Ensuring user stays on main screen...');
          // Force refresh the current screen to ensure proper state
          setState(() {});
        }
      } else if (token != null && isLoggedIn) {
        print('🔐 DEBUG: ✅ Session is healthy, no recovery needed');
        
        // Still backup session data to be safe
        await _backupSessionData(token);
        print('🔐 DEBUG: ✅ Session data backed up for healthy session');
      } else {
        print('🔐 DEBUG: ❌ No token found, cannot recover session');
      }
      
      print('🔐 DEBUG: === SESSION RECOVERY END ===');
    } catch (e) {
      print('🔐 DEBUG: ❌ Error during session recovery: $e');
    }
  }

  Future<void> _checkLoginStatusAndInitialize() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final isLoggedIn = prefs.getBool('isLoggedIn') ?? false;
      
      // Enhanced debugging
      print('🔐 === SESSION DEBUG START ===');
      print('🔐 DEBUG: Checking login status...');
      print('🔐 DEBUG: Token exists: ${token != null}');
      print('🔐 DEBUG: Token value: ${token?.substring(0, token.length > 20 ? 20 : token.length)}...');
      print('🔐 DEBUG: isLoggedIn flag: $isLoggedIn');
      print('🔐 DEBUG: All SharedPreferences keys: ${prefs.getKeys()}');
      
      // Check all stored values for debugging
      final allKeys = prefs.getKeys();
      for (String key in allKeys) {
        if (key.contains('token') || key.contains('login') || key.contains('user')) {
          final value = prefs.get(key);
          print('🔐 DEBUG: Key "$key" = $value');
        }
      }
      
      // BULLETPROOF FIX: Ultra-lenient session check - ANY token means logged in
      if (token != null && token.isNotEmpty) {
        print('🔐 DEBUG: ✅ Token found, user is logged in!');
        
        // CRITICAL: Force set login flag to true immediately
        await prefs.setBool('isLoggedIn', true);
        print('🔐 DEBUG: ✅ Forced isLoggedIn flag to true');
        
        // Also store a backup flag
        await prefs.setBool('userLoggedIn', true);
        print('🔐 DEBUG: ✅ Set backup login flag');
        
        print('🔐 DEBUG: ✅ User is logged in, initializing app...');
        
        // Validate token in background without blocking UI
        _validateTokenInBackground(token);
        
        // Initialize data immediately for better UX
        _initializeData();
        
        print('🔐 DEBUG: ✅ App initialized for logged-in user');
      } else {
        print('🔐 DEBUG: ❌ No valid token found, checking backup flags...');
        
        // Check backup flags before giving up
        final backupLogin = prefs.getBool('userLoggedIn') ?? false;
        final anyLoginFlag = prefs.getBool('isLoggedIn') ?? false;
        
        if (backupLogin || anyLoginFlag) {
          print('🔐 DEBUG: 🔄 Backup login flags found, recovering session...');
          await prefs.setBool('isLoggedIn', true);
          await prefs.setBool('userLoggedIn', true);
          
          // Try to get token from other sources
          final rememberedToken = prefs.getString('rememberedToken') ?? 
                                 prefs.getString('authToken') ?? 
                                 prefs.getString('userToken');
          
          if (rememberedToken != null && rememberedToken.isNotEmpty) {
            print('🔐 DEBUG: ✅ Found remembered token, restoring session...');
            await prefs.setString('token', rememberedToken);
            _validateTokenInBackground(rememberedToken);
            _initializeData();
            print('🔐 DEBUG: ✅ Session restored from backup!');
            return; // Don't redirect to login
          }
        }
        
        print('🔐 DEBUG: ❌ No valid session found, redirecting to login...');
        
        // Small delay to ensure UI is ready
        Future.delayed(Duration(milliseconds: 500), () {
          _redirectToLogin();
        });
      }
      print('🔐 === SESSION DEBUG END ===');
    } catch (e) {
      print('🔐 DEBUG: ❌ Error checking login status: $e');
      print('🔐 DEBUG: Stack trace: ${StackTrace.current}');
      
      // CRITICAL FIX: On error, try to recover session
      try {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('token');
        if (token != null) {
          print('🔐 DEBUG: 🔄 Error occurred but token exists, trying to recover...');
          await prefs.setBool('isLoggedIn', true);
          _initializeData();
          print('🔐 DEBUG: ✅ Session recovered after error');
        } else {
          print('🔐 DEBUG: ❌ No token to recover, redirecting to login...');
          _redirectToLogin();
        }
      } catch (e2) {
        print('🔐 DEBUG: ❌ Failed to recover session: $e2');
        _redirectToLogin();
      }
    }
  }
  
  Future<void> _validateTokenInBackground(String token) async {
    try {
      print('🔐 DEBUG: Starting background token validation...');
      
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/simple_session_manager.php?action=validate_token'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'token': token},
      ).timeout(const Duration(seconds: 10));

      print('🔐 DEBUG: Token validation response: ${response.statusCode}');
      print('🔐 DEBUG: Token validation body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          print('🔐 DEBUG: ✅ Token validation successful in background');
          // Update last activity
          await updateLastActivity();
          
          // BULLETPROOF FIX: Ensure login flag is still set
          final prefs = await SharedPreferences.getInstance();
          await prefs.setBool('isLoggedIn', true);
          print('🔐 DEBUG: ✅ Confirmed isLoggedIn flag is set to true');
          
          // Also ensure token is still there
          final currentToken = prefs.getString('token');
          if (currentToken == null) {
            print('🔐 DEBUG: ⚠️ Token was cleared! Restoring it...');
            await prefs.setString('token', token);
            print('🔐 DEBUG: ✅ Token restored');
          }
          
          // CRITICAL: Backup session data every time
          await _backupSessionData(token);
          print('🔐 DEBUG: ✅ Session data backed up during validation');
        } else {
          print('🔐 DEBUG: ❌ Token validation failed in background: ${data['message']}');
          // BULLETPROOF FIX: Don't force logout, let user continue
          // But ensure login flag is maintained
          final prefs = await SharedPreferences.getInstance();
          await prefs.setBool('isLoggedIn', true);
          print('🔐 DEBUG: 🔄 Maintaining isLoggedIn flag despite validation failure');
          
          // Also ensure token is maintained
          final currentToken = prefs.getString('token');
          if (currentToken == null) {
            print('🔐 DEBUG: ⚠️ Token was cleared! Restoring it...');
            await prefs.setString('token', token);
            print('🔐 DEBUG: ✅ Token restored');
          }
          
          // CRITICAL: Backup session data every time
          await _backupSessionData(token);
          print('🔐 DEBUG: ✅ Session data backed up despite validation failure');
        }
      } else {
        print('🔐 DEBUG: ❌ Token validation HTTP error: ${response.statusCode}');
        // BULLETPROOF FIX: Maintain login flag even on HTTP errors
        final prefs = await SharedPreferences.getInstance();
        await prefs.setBool('isLoggedIn', true);
        print('🔐 DEBUG: 🔄 Maintaining isLoggedIn flag despite HTTP error');
        
        // Also ensure token is maintained
        final currentToken = prefs.getString('token');
        if (currentToken == null) {
          print('🔐 DEBUG: ⚠️ Token was cleared! Restoring it...');
          await prefs.setString('token', token);
          print('🔐 DEBUG: ✅ Token restored');
        }
        
        // CRITICAL: Backup session data every time
        await _backupSessionData(token);
        print('🔐 DEBUG: ✅ Session data backed up despite HTTP error');
      }
    } catch (e) {
      print('🔐 DEBUG: ❌ Error validating token in background: $e');
              // BULLETPROOF FIX: Don't force logout on network errors
        // Maintain login flag and token
        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setBool('isLoggedIn', true);
          print('🔐 DEBUG: 🔄 Maintaining isLoggedIn flag despite network error');
          
          // Also ensure token is maintained
          final currentToken = prefs.getString('token');
          if (currentToken == null) {
            print('🔐 DEBUG: ⚠️ Token was cleared! Restoring it...');
            await prefs.setString('token', token);
            print('🔐 DEBUG: ✅ Token restored');
          }
          
          // CRITICAL: Backup session data every time
          await _backupSessionData(token);
          print('🔐 DEBUG: ✅ Session data backed up despite network error');
        } catch (e2) {
          print('🔐 DEBUG: ❌ Failed to maintain login flag: $e2');
        }
    }
  }

  void _initializeData() {
    fetchUserBalance();
    fetchContests();
    fetchJobApplications();
    fetchJobs();
    _startRefreshTimer();
    _startAutoScroll(); // This will now be safe
    
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
        photoPath: 'https://playsmart.co.in/uploads/photos/rahul_sharma.jpg',
        resumePath: 'https://playsmart.co.in/uploads/resumes/rahul_sharma_resume.pdf',
        email: 'rahul.sharma@email.com',
        phone: '+91-9876543210',
        experience: '5 years',
        skills: 'Product Management, Analytics, Leadership, Team Management, Agile, Scrum',
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
        photoPath: 'https://playsmart.co.in/uploads/photos/priya_patel.jpg',
        resumePath: 'https://playsmart.co.in/uploads/resumes/priya_patel_resume.pdf',
        email: 'priya.patel@email.com',
        phone: '+91-9876543211',
        experience: '4 years',
        skills: 'Product Strategy, User Research, Data Analysis, Figma, Prototyping, User Testing',
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
        photoPath: 'https://playsmart.co.in/uploads/photos/amit_kumar.jpg',
        resumePath: 'https://playsmart.co.in/uploads/resumes/amit_kumar_resume.pdf',
        email: 'amit.kumar@email.com',
        phone: '+91-9876543212',
        experience: '6 years',
        skills: 'UI/UX Design, Figma, Prototyping, Adobe Creative Suite, User Research, Design Systems',
        paymentId: 'pay_123456791',
        applicationStatus: 'accepted',
        appliedDate: DateTime.now().subtract(Duration(days: 3)),
        isActive: true,
      ),
      JobApplication(
        id: 4,
        jobId: 4,
        companyName: 'Amazon',
        companyLogoUrl: 'https://playsmart.co.in/uploads/amazon_logo.png',
        studentName: 'Neha Singh',
        district: 'Bangalore',
        package: '18LPA',
        profile: 'Data Scientist',
        photoPath: 'https://playsmart.co.in/uploads/photos/neha_singh.jpg',
        resumePath: 'https://playsmart.co.in/uploads/resumes/neha_singh_resume.pdf',
        email: 'neha.singh@email.com',
        phone: '+91-9876543213',
        experience: '7 years',
        skills: 'Python, Machine Learning, SQL, TensorFlow, PyTorch, Data Analysis, Statistics',
        paymentId: 'pay_123456792',
        applicationStatus: 'shortlisted',
        appliedDate: DateTime.now().subtract(Duration(days: 4)),
        isActive: true,
      ),
      JobApplication(
        id: 5,
        jobId: 5,
        companyName: 'Netflix',
        companyLogoUrl: 'https://playsmart.co.in/uploads/netflix_logo.png',
        studentName: 'Vikram Verma',
        district: 'Hyderabad',
        package: '16LPA',
        profile: 'Frontend Developer',
        photoPath: 'https://playsmart.co.in/uploads/photos/vikram_verma.jpg',
        resumePath: 'https://playsmart.co.in/uploads/resumes/vikram_verma_resume.pdf',
        email: 'vikram.verma@email.com',
        phone: '+91-9876543214',
        experience: '5 years',
        skills: 'React, JavaScript, CSS, TypeScript, Node.js, Git, Responsive Design',
        paymentId: 'pay_123456793',
        applicationStatus: 'pending',
        appliedDate: DateTime.now().subtract(Duration(days: 5)),
        isActive: true,
      ),
    ];
    
    print('DEBUG: Sample data setup complete. jobApplications.length = ${jobApplications.length}');
    

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
      
      // Send payment confirmation email
      _sendPaymentConfirmationEmail(_currentJobApplication!, response.paymentId ?? '');
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
    final TextEditingController referralCodeController = TextEditingController();
    
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
                    // Text(
                    //   'Apply for ${job.jobTitle}',
                    //   style: GoogleFonts.poppins(
                    //     fontSize: 22,
                    //     fontWeight: FontWeight.bold,
                    //     color: Colors.white,
                    //   ),
                    //   textAlign: TextAlign.center,
                    // ),
                    // SizedBox(height: 20),
                   
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
                             maxLines: 1,
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
                           
                           // Referral Code removed from main form - will be added in payment instructions
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
                              
                              // Submit the form data first
                              final formData = {
                                'name': nameController.text,
                                'email': emailController.text,
                                'phone': phoneController.text,
                                'experience': experienceController.text,
                                'skills': skillsController.text,
                              };
                              _submitJobApplication(job, referralCodeController.text, formData, _selectedPhotoPath, _selectedResumePath);
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
                              'Submit',
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

  Future<void> _sendPaymentConfirmationEmail(Job job, String paymentId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      if (token == null) {
        print('DEBUG: No token found for sending email');
        return;
      }

      // Send email via backend
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/send_payment_confirmation_email.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'job_id': job.id,
          'job_title': job.jobTitle,
          'company_name': job.companyName,
          'package': job.package,
          'payment_id': paymentId,
          'email': 'user@example.com', // This should come from user data
        }),
      ).timeout(Duration(seconds: 10));

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        if (result['success']) {
          print('DEBUG: Payment confirmation email sent successfully');
        } else {
          print('DEBUG: Failed to send email: ${result['message']}');
        }
      } else {
        print('DEBUG: Email API error: HTTP ${response.statusCode}');
      }
    } catch (e) {
      print('DEBUG: Error sending payment confirmation email: $e');
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





    Future<void> _logout() async {
    try {
      print('🔐 DEBUG: Starting logout process...');
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      
      print('🔐 DEBUG: Current session before logout:');
      print('🔐 DEBUG: Token exists: ${token != null}');
      print('🔐 DEBUG: isLoggedIn: ${prefs.getBool('isLoggedIn')}');
      
      if (token != null) {
        // Try to call logout API (but don't block on it)
        try {
          await http.post(
            Uri.parse('https://playsmart.co.in/logout.php'),
            body: {'token': token},
          ).timeout(const Duration(seconds: 5));
          print('🔐 DEBUG: ✅ Logout API call successful');
        } catch (e) {
          print('🔐 DEBUG: ❌ Logout API call failed: $e');
        }
      }
      
      // Clear all stored data
      await prefs.clear();
      
      print('🔐 DEBUG: ✅ User logged out successfully, all data cleared');
      
      // Navigate to login screen
      if (mounted) {
        Navigator.pushReplacement(
          context,
          PageRouteBuilder(
            pageBuilder: (context, animation, secondaryAnimation) => LoginScreen(),
            transitionsBuilder: (context, animation, secondaryAnimation, child) {
              var begin = Offset(0.0, 1.0);
              var end = Offset.zero;
              var curve = Curves.easeInOutQuart;
              var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
              return SlideTransition(position: animation.drive(tween), child: child);
            },
            transitionDuration: Duration(milliseconds: 700),
          ),
        );
      }
    } catch (e) {
      print('🔐 DEBUG: ❌ Error during logout: $e');
      // Force logout even if there's an error
      try {
        final prefs = await SharedPreferences.getInstance();
        await prefs.clear();
        print('🔐 DEBUG: 🔄 Forced logout due to error');
        if (mounted) {
          Navigator.pushReplacement(
            context,
            PageRouteBuilder(
              pageBuilder: (context, animation, secondaryAnimation) => LoginScreen(),
              transitionsBuilder: (context, animation, secondaryAnimation, child) {
                var begin = Offset(0.0, 1.0);
                var end = Offset.zero;
                var curve = Curves.easeInOutQuart;
                var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
                return SlideTransition(position: animation.drive(tween), child: child);
            },
              transitionDuration: Duration(milliseconds: 700),
            ),
          );
        }
      } catch (e2) {
        print('🔐 DEBUG: ❌ Critical error during logout: $e2');
      }
    }
  }

  // Add session status check method for debugging
  Future<void> _checkSessionStatus() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      final isLoggedIn = prefs.getBool('isLoggedIn') ?? false;
      final userLoggedIn = prefs.getBool('userLoggedIn') ?? false;
      
      print('🔐 === SESSION STATUS CHECK ===');
      print('🔐 DEBUG: Current session status:');
      print('🔐 DEBUG: Token exists: ${token != null}');
      print('🔐 DEBUG: Token length: ${token?.length ?? 0}');
      print('🔐 DEBUG: Token preview: ${token?.substring(0, token != null && token.length > 20 ? 20 : token.length)}...');
      print('🔐 DEBUG: isLoggedIn flag: $isLoggedIn');
      print('🔐 DEBUG: userLoggedIn flag: $userLoggedIn');
      print('🔐 DEBUG: All stored keys: ${prefs.getKeys()}');
      
      // Show all relevant stored values
      final allKeys = prefs.getKeys();
      for (String key in allKeys) {
        if (key.contains('token') || key.contains('login') || key.contains('user') || key.contains('email')) {
          final value = prefs.get(key);
          print('🔐 DEBUG: Key "$key" = $value');
        }
      }
      
      print('🔐 === SESSION STATUS END ===');
    } catch (e) {
      print('🔐 DEBUG: ❌ Error checking session status: $e');
    }
  }

  // BULLETPROOF FIX: Backup session data method
  Future<void> _backupSessionData(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      // Store multiple backup copies
      await prefs.setString('rememberedToken', token);
      await prefs.setString('authToken', token);
      await prefs.setString('userToken', token);
      await prefs.setBool('userLoggedIn', true);
      await prefs.setBool('isLoggedIn', true);
      await prefs.setBool('sessionActive', true);
      await prefs.setBool('userAuthenticated', true);
      
      print('🔐 DEBUG: ✅ Session data backed up with multiple flags');
      print('🔐 DEBUG: Backup flags set: rememberedToken, authToken, userToken, userLoggedIn, isLoggedIn, sessionActive, userAuthenticated');
    } catch (e) {
      print('🔐 DEBUG: ❌ Error backing up session data: $e');
    }
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
        Uri.parse('https://playsmart.co.in/simple_session_manager.php?action=update_activity'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'session_token': token},
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          print('Last activity updated successfully');
        } else {
          print('Failed to update last activity: ${data['message']}');
          // Don't redirect, just log the issue
        }
      } else {
        print('Failed to update last activity: HTTP ${response.statusCode}, Body: ${response.body}');
        // Don't redirect, just log the issue
      }
    } catch (e) {
      print('Error updating last activity: $e');
      // Don't redirect on network errors, just log
    }
  }

  Future<void> validateAndRefreshToken() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    
    if (token == null) {
      // Only redirect if no token at all, not on validation failure
      print('No token found, user needs to login');
      return;
    }
    
    try {
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/simple_session_manager.php?action=validate_token'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'token': token},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] is Map<String, dynamic>) {
          // Token is valid, update last activity
          await updateLastActivity();
          print('Token validation successful');
        } else {
          // Token is invalid, but don't force logout - just log it
          print('Token validation failed: ${data['message']}');
          // Don't redirect, let user continue using the app
        }
      } else {
        // API error, but don't force logout
        print('Token validation API error: ${response.statusCode}');
        // Don't redirect, let user continue using the app
      }
    } catch (e) {
      print('Error checking token validity: $e');
      // Network error, don't redirect immediately, just log
      // User can continue using the app
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
      
      // Add loading timeout
      final applicationsData = await JobApplicationController.fetchJobApplications()
          .timeout(Duration(seconds: 5), onTimeout: () {
        print('DEBUG: Job applications fetch timed out after 5 seconds');
        throw TimeoutException('Job applications fetch timed out', Duration(seconds: 5));
      });
      
      print('DEBUG: Received ${applicationsData.length} applications from API');
      
      if (mounted) {
        setState(() {
          jobApplications = applicationsData;
        });
        print('DEBUG: Updated state with ${jobApplications.length} applications');
        
        // Restart auto-scroll after data is loaded
        _restartAutoScroll();
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
        
        // Restart auto-scroll even with sample data
        _restartAutoScroll();
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
          // Categorize jobs
          _categorizeJobs(jobsData);
        });
        print('DEBUG: Updated state with ${jobs.length} jobs');
      }
      print('DEBUG: Fetched ${jobs.length} jobs successfully');
    } catch (e) {
      print('Error fetching jobs: $e');
      if (mounted) {
        setState(() {
          jobs = [];
          higherPackageJobs = [];
          localJobs = [];
        });
      }
    }
  }

  void _categorizeJobs(List<Job> allJobs) {
    // Categorize jobs based on package amount and location
    
    // Higher Package Jobs (above 12 LPA)
    higherPackageJobs = allJobs.where((job) {
      if (job.package == null || job.package.isEmpty) return false;
      
      // Extract numeric value from package string (e.g., "25LPA" -> 25)
      final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
      return packageValue != null && packageValue >= 12;
    }).toList();

    // Local Jobs (package < 10 LPA)
    localJobs = allJobs.where((job) {
      if (job.package == null || job.package.isEmpty) return false;
      
      // Extract numeric value from package string (e.g., "8LPA" -> 8)
      final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
      return packageValue != null && packageValue < 10;
    }).toList();
    
    print('DEBUG: Categorized ${higherPackageJobs.length} higher package jobs and ${localJobs.length} local jobs');
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
    _autoScrollTimer?.cancel(); // Cancel existing timer if any
    
    // Don't start auto-scroll if there are no job applications
    if (jobApplications.length <= 1) {
      print('DEBUG: Not enough job applications for auto-scroll (${jobApplications.length} <= 1)');
      return;
    }
    
    print('DEBUG: Starting marquee auto-scroll with ${jobApplications.length} applications');
    _autoScrollTimer = Timer.periodic(Duration(milliseconds: 50), (timer) {
      if (!mounted || jobApplications.length <= 1) {
        print('DEBUG: Stopping marquee auto-scroll - mounted: $mounted, apps: ${jobApplications.length}');
        timer.cancel();
        _autoScrollTimer = null;
        return;
      }
      
      if (_jobApplicationsScrollController.hasClients) {
        final maxScroll = _jobApplicationsScrollController.position.maxScrollExtent;
        final currentScroll = _jobApplicationsScrollController.position.pixels;
        
        // Continuous right-to-left marquee scrolling
        if (currentScroll >= maxScroll) {
          // Reset to beginning when reaching the end for seamless loop
          _jobApplicationsScrollController.jumpTo(0);
        } else {
          // Smooth continuous scrolling to the left
          _jobApplicationsScrollController.jumpTo(currentScroll + 1);
        }
      } else {
        print('DEBUG: Scroll controller not ready');
      }
    });
  }

  void _restartAutoScroll() {
    print('DEBUG: Restarting marquee auto-scroll');
    // Restart auto-scroll when data is loaded
    Future.delayed(Duration(milliseconds: 500), () {
      if (mounted && jobApplications.length > 1) {
        print('DEBUG: Restarting marquee auto-scroll after delay');
        _startAutoScroll();
      } else {
        print('DEBUG: Not restarting marquee auto-scroll - mounted: $mounted, apps: ${jobApplications.length}');
      }
    });
  }

  void _pauseAutoScroll() {
    print('DEBUG: Pausing marquee auto-scroll');
    _autoScrollTimer?.cancel();
    _autoScrollTimer = null; // Set to null after canceling
  }

  void _resumeAutoScroll() {
    print('DEBUG: Resuming marquee auto-scroll');
    if (_autoScrollTimer == null) { // Only start if not already running
      _startAutoScroll();
    }
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
            // Profile Photo and Company Logo Row
            Row(
              children: [
                // Profile Photo (Circle)
                Container(
                  width: 30,
                  height: 30,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.grey[300]!, width: 2),
                  ),
                  child: application.photoUrl != null && application.photoUrl!.isNotEmpty
                      ? ClipOval(
                          child: Image.network(
                            application.photoUrl!,
                            fit: BoxFit.cover,
                            width: 30,
                            height: 30,
                            errorBuilder: (context, error, stackTrace) {
                              return Container(
                                decoration: BoxDecoration(
                                  color: Colors.grey[200],
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(
                                  Icons.person,
                                  color: Colors.grey[600],
                                  size: 14,
                                ),
                              );
                            },
                            loadingBuilder: (context, child, loadingProgress) {
                              if (loadingProgress == null) return child;
                              return Container(
                                decoration: BoxDecoration(
                                  color: Colors.grey[200],
                                  shape: BoxShape.circle,
                                ),
                                child: Center(
                                  child: CircularProgressIndicator(
                                    value: loadingProgress.expectedTotalBytes != null
                                        ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                        : null,
                                    strokeWidth: 2,
                                  ),
                                ),
                              );
                            },
                          ),
                        )
                      : Container(
                          decoration: BoxDecoration(
                            color: Colors.grey[200],
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            Icons.person,
                            color: Colors.grey[600],
                            size: 14,
                          ),
                        ),
                ),
                SizedBox(width: 4),
                // Company Logo
                Container(
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(3),
                  ),
                  child: application.companyLogoUrl.isNotEmpty
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(3),
                          child: Image.network(
                            application.companyLogoUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) {
                              return Icon(
                                Icons.business,
                                color: Colors.grey[600],
                                size: 10,
                              );
                            },
                          ),
                        )
                      : Icon(
                          Icons.business,
                          color: Colors.grey[600],
                          size: 10,
                        ),
                ),
                SizedBox(width: 3),
                // Company Name
                Expanded(
                  child: Text(
                    application.companyName,
                    style: GoogleFonts.poppins(
                      fontSize: 10,
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
                fontSize: 11,
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
                fontSize: 8,
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 2),
            // Package
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 9),
                SizedBox(width: 1),
                Text(
                  application.package,
                  style: GoogleFonts.poppins(
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 2),
            // Profile
            Text(
              '💼 ${application.profile}',
              style: GoogleFonts.poppins(
                fontSize: 8,
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              ),
            SizedBox(height: 2),
            // Application Status
            Container(
              padding: EdgeInsets.symmetric(horizontal: 2, vertical: 1),
              decoration: BoxDecoration(
                color: _getStatusColor(application.applicationStatus).withOpacity(0.1),
                borderRadius: BorderRadius.circular(3),
                border: Border.all(
                  color: _getStatusColor(application.applicationStatus).withOpacity(0.3),
                ),
              ),
              child: Text(
                _getStatusText(application.applicationStatus),
                style: GoogleFonts.poppins(
                  fontSize: 6,
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
            // Job Title
            Text(
              job.jobTitle,
              style: GoogleFonts.poppins(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 8),
            // Higher Education
            Row(
              children: [
                Icon(Icons.school, color: Colors.blue[600], size: 16),
                SizedBox(width: 4),
                Text(
                  'Higher Education',
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Colors.blue[600],
                  ),
                ),
              ],
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

  Widget _buildJobCardWithApplyButton(Job job) {
    // Determine button color based on package amount (but don't show amount)
    Color buttonColor;
    
    // Extract numeric value from package string (e.g., "25LPA" -> 25)
    final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
    
    if (packageValue != null && packageValue >= 20) {
      // 20+ LPA jobs get orange button
      buttonColor = Colors.orange;
    } else {
      // Lower package jobs get green button
      buttonColor = Colors.green;
    }
    
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
            // Job Title
            Text(
              job.jobTitle,
              style: GoogleFonts.poppins(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 8),
            // Higher Education
            Row(
              children: [
                Icon(Icons.school, color: Colors.blue[600], size: 16),
                SizedBox(width: 4),
                Text(
                  'Higher Education',
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Colors.blue[600],
                  ),
                ),
              ],
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
            SizedBox(height: 8),
            // Apply Button (no payment amount shown)
            GestureDetector(
              onTap: () => _showJobApplicationModal(job),
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 8),
                decoration: BoxDecoration(
                  color: buttonColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: buttonColor.withOpacity(0.3)),
                ),
                child: Text(
                  'Apply',
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: buttonColor,
                  ),
                  textAlign: TextAlign.center,
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
                              sin((_floatingIconsController.value * 2 * pi) + index) * 30,
        cos((_floatingIconsController.value * 2 * pi) + index + 1) * 20,
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
                            // BULLETPROOF FIX: Check and recover session before showing profile
                            await _checkSessionStatus();
                            await _recoverSessionIfNeeded();
                            
                            // CRITICAL: Backup session data when profile is accessed
                            final prefs = await SharedPreferences.getInstance();
                            final token = prefs.getString('token');
                            if (token != null) {
                              await _backupSessionData(token);
                              print('🔐 DEBUG: ✅ Session data backed up when profile accessed');
                            }
                            
                            await _showProfileWithLogoutOption();
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
                  SizedBox(height: 20),
                  // Scrollable main content
                  Expanded(
                    child: SingleChildScrollView(
                      physics: BouncingScrollPhysics(),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Job Applications Section (First Container)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Our Successfully Placed',
                                style: GoogleFonts.poppins(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Row(
                                children: [
                                  Text(
                                    'Candidates',
                                    style: GoogleFonts.poppins(
                                      color: Colors.yellow,
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          SizedBox(height: 10),

                          Container(
                            height: 140,
                            child: jobApplications.isNotEmpty 
                              ? GestureDetector(
                                  onPanStart: (_) {
                                    print('DEBUG: User interaction detected, pausing marquee auto-scroll');
                                    _pauseAutoScroll();
                                  },
                                  onPanEnd: (_) {
                                    print('DEBUG: User interaction ended, resuming marquee auto-scroll in 2 seconds');
                                    Future.delayed(Duration(seconds: 2), () {
                                      if (mounted) {
                                        print('DEBUG: Resuming marquee auto-scroll after user interaction');
                                        _resumeAutoScroll();
                                      }
                                    });
                                  },
                                  onTap: () {
                                    // Pause auto-scroll on tap and resume after delay
                                    _pauseAutoScroll();
                                    Future.delayed(Duration(seconds: 2), () {
                                      if (mounted) {
                                        _resumeAutoScroll();
                                      }
                                    });
                                  },
                                  child: ListView.builder(
                                    controller: _jobApplicationsScrollController,
                                    scrollDirection: Axis.horizontal,
                                    physics: NeverScrollableScrollPhysics(), // Disable manual scrolling for marquee effect
                                    itemCount: jobApplications.length * 2, // Duplicate items for seamless loop
                                    itemBuilder: (context, index) {
                                      final application = jobApplications[index % jobApplications.length];
                                      return Container(
                                        width: 180,
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
                                    ],
                                  ),
                                ),
                          ),
                          
                          // View All Button for Successful Candidates (Outside Container)
                          SizedBox(height: 15),
                          Center(
                            child: ElevatedButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => SuccessfulCandidatesScreen(candidates: jobApplications),
                                  ),
                                );
                              },
                              child: Text('View All Candidates'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.blue,
                                foregroundColor: Colors.white,
                                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                          ),
                        
                          SizedBox(height: 20),
                          
                          // Higher Package Jobs Section (Horizontal View)
                          Text(
                            'Higher Package Jobs',
                            style: GoogleFonts.poppins(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 12),
                          Container(
                            height: 190,
                            child: higherPackageJobs.isNotEmpty 
                              ? ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(),
                                  itemCount: higherPackageJobs.length,
                                  itemBuilder: (context, index) {
                                    final job = higherPackageJobs[index];
                                    return Container(
                                      width: 180,
                                      margin: EdgeInsets.only(right: 15),
                                      child: _buildJobCardWithApplyButton(job),
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
                                        'No higher package jobs available',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white70,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                          ),
                          
                          // View All Button for Higher Package Jobs (Outside Container)
                          SizedBox(height: 15),
                          Center(
                            child: ElevatedButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => AllJobsPage(
                                      jobs: higherPackageJobs,
                                      title: 'Higher Package Jobs',
                                      jobType: 'higher_package',
                                    ),
                                  ),
                                );
                              },
                              child: Text('View All Higher Package Jobs'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.blue,
                                foregroundColor: Colors.white,
                                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                          ),
                          
                          // Local Jobs Section (Horizontal View)
                          SizedBox(height: 20),
                          Text(
                            'Local Jobs',
                            style: GoogleFonts.poppins(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 12),
                          Container(
                            height: 190,
                            child: localJobs.isNotEmpty 
                              ? ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(),
                                  itemCount: localJobs.length,
                                  itemBuilder: (context, index) {
                                    final job = localJobs[index];
                                    return Container(
                                      width: 180,
                                      margin: EdgeInsets.only(right: 15),
                                      child: _buildJobCardWithApplyButton(job),
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
                                        'No local jobs available',
                                        style: GoogleFonts.poppins(
                                          color: Colors.white70,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                          ),
                          
                          // View All Button for Local Jobs (Outside Container)
                          SizedBox(height: 15),
                          Center(
                            child: ElevatedButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => const LocalJobsScreen(),
                                  ),
                                );
                              },
                              child: Text('View All Local Jobs'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.green,
                                foregroundColor: Colors.white,
                                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                          ),
                          
                          // Contests Section
                          // SizedBox(height: 20),
                          // Text(
                          //   'Contests',
                          //   style: GoogleFonts.poppins(
                          //     color: Colors.white,
                          //     fontSize: 20,
                          //     fontWeight: FontWeight.bold,
                          //   ),
                          // ),
                          // SizedBox(height: 12),
                          
                          // // Show first 2 contests
                          // if (megaContests.isNotEmpty || miniContests.isNotEmpty)
                          //   Column(
                          //     children: [
                          //       if (megaContests.isNotEmpty)
                          //         _buildContestCard(megaContests.first, 0, isMega: true),
                          //       if (miniContests.isNotEmpty)
                          //         _buildContestCard(miniContests.first, 1, isMega: false),
                          //     ],
                          //   )
                          // else
                          //   Center(
                          //     child: Column(
                          //       mainAxisAlignment: MainAxisAlignment.center,
                          //       children: [
                          //         Icon(
                          //           Icons.emoji_events_outlined,
                          //           color: Colors.white70,
                          //           size: 28,
                          //         ),
                          //         SizedBox(height: 6),
                          //         Text(
                          //           'No contests available',
                          //           style: GoogleFonts.poppins(
                          //             color: Colors.white70,
                          //             fontSize: 14,
                          //           ),
                          //         ),
                          //       ],
                          //     ),
                          //   ),
                          
                          // // View All Button for Contests (Outside Container)
                          // SizedBox(height: 15),
                          // Center(
                          //   child: ElevatedButton(
                          //     onPressed: () => _showAllContests(),
                          //     child: Text('View All Contests'),
                          //     style: ElevatedButton.styleFrom(
                          //       backgroundColor: Colors.blue,
                          //       foregroundColor: Colors.white,
                          //       padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                          //       shape: RoundedRectangleBorder(
                          //         borderRadius: BorderRadius.circular(8),
                          //       ),
                          //     ),
                          //   ),
                          // ),
                        
                        
                        
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

  Future<void> _showProfileWithLogoutOption() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? token = prefs.getString('token');
    final isLoggedIn = prefs.getBool('isLoggedIn') ?? false;
    
    if (token == null || !isLoggedIn) {
      await _redirectToLogin();
      return;
    }

    try {
      // Try to fetch user data first to check if token is valid
      final response = await http.get(
        Uri.parse('https://playsmart.co.in/get_user_data.php?token=$token'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'User-Agent': 'QuizMaster/1.0',
        },
      ).timeout(Duration(seconds: 5));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data'] is Map<String, dynamic>) {
          // Token is valid, proceed to profile
          await updateLastActivity();
          Navigator.push(
            context,
            PageRouteBuilder(
              pageBuilder: (context, animation, secondaryAnimation) => ProfileScreen(token: token!),
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
          // Token is invalid, show logout option
          _showTokenExpiredDialog();
        }
      } else {
        // API error, show logout option
        _showTokenExpiredDialog();
      }
    } catch (e) {
      print('Error checking token validity: $e');
      // Network error, show logout option
      _showTokenExpiredDialog();
    }
  }

  void _showCandidateDetails(JobApplication application) {
    showDialog(
      context: context,
      barrierDismissible: true,
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
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header with Profile Photo and Name
                    Row(
                      children: [
                        // Large Profile Photo
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 3),
                          ),
                          child: application.photoUrl != null && application.photoUrl!.isNotEmpty
                              ? ClipOval(
                                  child: Image.network(
                                    application.photoUrl!,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) {
                                      return Container(
                                        decoration: BoxDecoration(
                                          color: Colors.white.withOpacity(0.2),
                                          shape: BoxShape.circle,
                                        ),
                                        child: Icon(
                                          Icons.person,
                                          color: Colors.white,
                                          size: 40,
                                        ),
                                      );
                                    },
                                  ),
                                )
                              : Container(
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.2),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(
                                    Icons.person,
                                    color: Colors.white,
                                    size: 40,
                                  ),
                                ),
                        ),
                        SizedBox(width: 16),
                        // Name and Company
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                application.studentName,
                                style: GoogleFonts.poppins(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              SizedBox(height: 4),
                              Text(
                                application.companyName,
                                style: GoogleFonts.poppins(
                                  fontSize: 16,
                                  color: Colors.white70,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 24),
                    
                    // Job Details Section
                    _buildDetailSection(
                      'Job Details',
                      [
                        _buildDetailRow('Position', application.profile),
                        _buildDetailRow('Package', '₹${application.package}'),
                        _buildDetailRow('Location', application.district),
                        _buildDetailRow('Experience', application.experience),
                      ],
                    ),
                    SizedBox(height: 16),
                    
                    // Skills Section
                    if (application.skills.isNotEmpty)
                      _buildDetailSection(
                        'Skills & Technologies',
                        [
                          _buildDetailRow('Skills', application.skills),
                        ],
                      ),
                    SizedBox(height: 16),
                    
                    // Application Status Section
                    _buildDetailSection(
                      'Application Status',
                      [
                        _buildDetailRow('Status', _getStatusText(application.applicationStatus)),
                        _buildDetailRow('Applied Date', _formatDate(application.appliedDate)),
                      ],
                    ),
                    
                    SizedBox(height: 24),
                    
                    // Close Button
                    Center(
                      child: ElevatedButton(
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

  Widget _buildDetailSection(String title, List<Widget> children) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: GoogleFonts.poppins(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: GoogleFonts.poppins(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Colors.white70,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  void _showTokenExpiredDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.grey[900],
          title: Text(
            'Session Expired',
            style: GoogleFonts.poppins(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          content: Text(
            'Your session has expired or there was an error loading your profile. Please log in again.',
            style: GoogleFonts.poppins(
              color: Colors.white70,
              fontSize: 14,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () async {
                Navigator.of(context).pop();
                await _redirectToLogin();
              },
              child: Text(
                'Logout & Login',
                style: GoogleFonts.poppins(
                  color: Colors.orange,
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  void _showInstructionsModal(Job job, String referralCode) {
    String instructions = '';
    String amountText = '';
    double amount = 0.0;
    final TextEditingController referralCodeController = TextEditingController();

    if (job.package.contains('LPA') && 
        double.parse(job.package.replaceAll('LPA', '').replaceAll('₹', '').trim()) >= 8) {
      instructions = '''
1. Play Smart services only works in company job requirements.

2. Play Smart services working  All Over India.

3. We provide Job for  candidates on local Place  or  elsewhere

4. We provide job opportunities for candidates according to their education.

5. We provide  2 to 3 Interview calls within Month for candidates.

6. We provide you  job opportunities That means we provide you a Service  The registration fee for    them is 2000.

7. Rs. 2000 Registration charges Will be limited for one year.

  8. The fee of Rs. 2000 is non-refundable.

9. If all the above are acceptable then  register today. The company will contact you today for a job    according to your education and provide you with further information.

10. The fee of Rs. 2000 is non-refundable.

11. The fee of Rs. 2000 is non-refundable.

12. The fee of Rs. 2000 is non-refundable.
      ''';
      amountText = '₹2000';
      amount = 2000.0;
    } else {
      instructions = '''
1. Play Smart services only works in company job requirements.

2. Play Smart services working  All Over India.

3. We provide Job for  candidates on local Place  or  elsewhere

4. We provide job opportunities for candidates according to their education.

5. We provide  2 to 3 Interview calls within Month for candidates.

6. We provide you  job opportunities That means we provide you a Service  The registration fee for    them is 1000.

7. Rs. 1000 Registration charges Will be limited for one year.

8. The fee of Rs. 1000 is non-refundable.

9. If all the above are acceptable then  register today. The company will contact you today for a job    according to your education and provide you with further information.
      ''';
      amountText = '₹1000';
      amount = 1000.0;
    }

    bool agreedToTerms = false;

    showModalBottomSheet(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => Container(
          height: MediaQuery.of(context).size.height * 0.95,
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Application Instructions',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Application Fee: $amountText',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.green[700],
                        ),
                      ),
                      SizedBox(height: 16),
                      Text(
                        instructions,
                        style: TextStyle(fontSize: 16, height: 1.5),
                      ),
                      SizedBox(height: 20),
                      
                      // Referral Code Section
                      Container(
                        padding: EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.grey[300]!),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Referral Code (Optional)',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.black87,
                              ),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'If you have a referral code, enter it below to get special benefits:',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.grey[600],
                              ),
                            ),
                            SizedBox(height: 12),
                            TextField(
                              controller: referralCodeController,
                              decoration: InputDecoration(
                                hintText: 'Enter referral code (optional)',
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                prefixIcon: Icon(Icons.card_giftcard, color: Colors.blue),
                              ),
                            ),
                            SizedBox(height: 8),
                            Row(
                              children: [
                                Icon(Icons.info_outline, color: Colors.blue, size: 16),
                                SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    'Referrer will receive 20% commission on your registration fee',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.blue[700],
                                      fontStyle: FontStyle.italic,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      
                      SizedBox(height: 20),
                      
                      Row(
                        children: [
                          Checkbox(
                            value: agreedToTerms,
                            onChanged: (value) {
                              setState(() {
                                agreedToTerms = value ?? false;
                              });
                            },
                          ),
                          Expanded(
                            child: Text(
                              'I agree to the terms and conditions and understand the application fee.',
                              style: TextStyle(fontSize: 14),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: agreedToTerms ? () {
                    Navigator.pop(context);
                    _showPaymentOptions(job, amount, referralCodeController.text);
                  } : null,
                  child: Text('Proceed to Payment'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: agreedToTerms ? Colors.green : Colors.grey,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showPaymentOptions(Job job, double amount, String referralCode) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Payment Options',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 16),
            Text(
              'Amount: ₹${amount.toStringAsFixed(0)}',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            if (referralCode.isNotEmpty) ...[
              SizedBox(height: 8),
              Text(
                'Referral Code: $referralCode',
                style: TextStyle(fontSize: 14, color: Colors.blue),
              ),
              Text(
                'Referrer will receive 20% commission',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
            SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                  _openPaymentGateway(job, amount, referralCode);
                },
                child: Text('Pay with Razorpay'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                ),
              ),
            ),
            SizedBox(height: 16),
            Text(
              'Secure payment powered by Razorpay',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitJobApplication(Job job, String referralCode, Map<String, String> formData, String? photoPath, String? resumePath) async {
    try {
      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return Center(
            child: CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          );
        },
      );

      // Prepare data for submission
      final data = {
        'name': formData['name'] ?? '',
        'email': formData['email'] ?? '',
        'phone': formData['phone'] ?? '',
        'education': 'Not specified', // Default value since education field is not in the form
        'experience': formData['experience'] ?? '',
        'skills': formData['skills'] ?? '',
        'job_id': job.id,
        'referral_code': referralCode.isNotEmpty ? referralCode : '',
        'photo_path': photoPath ?? '',
        'resume_path': resumePath ?? '',
        'company_name': job.companyName,
        'package': job.package,
        'profile': job.jobTitle,
        'district': 'Mumbai', // Default location
      };

      print('DEBUG: Submitting application data: $data');

      // Send to backend to store in database
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/submit_job_application_working.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: jsonEncode(data),
      ).timeout(Duration(seconds: 30));

      print('DEBUG: Response status: ${response.statusCode}');
      print('DEBUG: Response body: ${response.body}');

      // Hide loading indicator
      Navigator.pop(context);

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        if (responseData['success']) {
          // Show success message
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Application submitted successfully!'),
              backgroundColor: Colors.green,
            ),
          );
          
          // Store application ID for payment
          final applicationId = responseData['data']['application_id'];
          final paymentId = responseData['data']['payment_id'];
          
          print('DEBUG: Application submitted successfully. ID: $applicationId, Payment ID: $paymentId');
          
          // Show instructions modal
          _showInstructionsModal(job, referralCode);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(responseData['message'] ?? 'Failed to submit application'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to submit application. Please try again.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      // Hide loading indicator
      Navigator.pop(context);
      
      print('DEBUG: Error submitting application: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error submitting application: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _openPaymentGateway(Job job, double amount, String referralCode) {
    try {
      // Create payment options directly
      var options = {
        'key': 'rzp_live_fgQr0ACWFbL4pN',
        'amount': (amount * 100).toInt(), // Amount in paise
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
      
      print('DEBUG: Opening payment gateway with options: $options');
      _razorpay.open(options);
    } catch (e) {
      print('DEBUG: Error in _openPaymentGateway: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening payment gateway: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

}

class AllJobsPage extends StatefulWidget {
  final List<Job> jobs;
  final String title;
  final String jobType;

  AllJobsPage({
    required this.jobs,
    required this.title,
    required this.jobType,
  });

  @override
  _AllJobsPageState createState() => _AllJobsPageState();
}

class _AllJobsPageState extends State<AllJobsPage> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        backgroundColor: Color(0xFF6A11CB),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Column(
          children: [
            // Header Section
            Container(
              padding: EdgeInsets.all(20),
              child: Column(
                children: [
                  Text(
                    widget.title,
                    style: GoogleFonts.poppins(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 8),
                  Text(
                    '${widget.jobs.length} jobs available',
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      color: Colors.white70,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            // Jobs List
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
                ),
                child: ListView.builder(
                  padding: EdgeInsets.all(20),
                  itemCount: widget.jobs.length,
                  itemBuilder: (context, index) {
                    final job = widget.jobs[index];
                    return Container(
                      margin: EdgeInsets.only(bottom: 20),
                      child: _buildJobCardWithApplyButton(job),
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildJobCardWithApplyButton(Job job) {
    // Determine button color based on package amount (but don't show amount)
    Color buttonColor;
    
    // Extract numeric value from package string (e.g., "25LPA" -> 25)
    final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
    
    if (packageValue != null && packageValue >= 20) {
      // 20+ LPA jobs get orange button
      buttonColor = Colors.orange;
    } else {
      // Lower package jobs get green button
      buttonColor = Colors.green;
    }
    
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
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
                        // Job Title
            Text(
              job.jobTitle,
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 12),
            // Higher Education
            Row(
              children: [
                Icon(Icons.school, color: Colors.blue[600], size: 20),
                SizedBox(width: 8),
                Text(
                  'Higher Education',
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Colors.blue[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            // Package
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 20),
                SizedBox(width: 8),
                Text(
                  job.package,
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            // Apply Button (no payment amount shown)
            GestureDetector(
              onTap: () => _showJobApplicationForm(job),
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  color: buttonColor,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: buttonColor.withOpacity(0.3),
                      blurRadius: 8,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                child: Text(
                  'Apply',
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showJobApplicationForm(Job job) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.transparent,
          contentPadding: EdgeInsets.zero,
                      content: Container(
              width: MediaQuery.of(context).size.width * 0.98,
              height: MediaQuery.of(context).size.height * 0.9,
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
            child: JobApplicationForm(job: job),
          ),
        );
      },
    );
  }
}

class JobApplicationForm extends StatefulWidget {
  final Job job;

  JobApplicationForm({required this.job});

  @override
  _JobApplicationFormState createState() => _JobApplicationFormState();
}

class _JobApplicationFormState extends State<JobApplicationForm> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _educationController = TextEditingController();
  final TextEditingController _experienceController = TextEditingController();
  final TextEditingController _skillsController = TextEditingController();
  final TextEditingController _referralCodeController = TextEditingController();
  String? _selectedPhotoPath;
  String? _selectedResumePath;
  bool _agreedToTerms = false;
  bool _showReferralField = false;

  @override
  Widget build(BuildContext context) {
    final packageValue = double.tryParse(widget.job.package.replaceAll(RegExp(r'[^\d.]'), ''));
    final isHighPackage = packageValue != null && packageValue >= 20;
    final registrationFee = isHighPackage ? '₹2000' : '₹1000';
    
    return Column(
      children: [
        // Header
        Container(
          padding: EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.1),
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Apply for ${widget.job.jobTitle}',
                      style: GoogleFonts.poppins(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      widget.job.companyName,
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: () => Navigator.pop(context),
                icon: Icon(Icons.close, color: Colors.white, size: 24),
              ),
            ],
          ),
        ),
        // Form Content - Ultra compact, no scrolling
        Expanded(
          child: Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Compact Form Fields - 3 columns with minimal spacing
                Row(
                  children: [
                    Expanded(child: _buildUltraCompactFormField('Full Name', _nameController, Icons.person)),
                    SizedBox(width: 8),
                    Expanded(child: _buildUltraCompactFormField('Email', _emailController, Icons.email)),
                    SizedBox(width: 8),
                    Expanded(child: _buildUltraCompactFormField('Phone', _phoneController, Icons.phone)),
                  ],
                ),
                SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(child: _buildUltraCompactFormField('Education', _educationController, Icons.school)),
                    SizedBox(width: 8),
                    Expanded(child: _buildUltraCompactFormField('Experience', _experienceController, Icons.work)),
                    SizedBox(width: 8),
                    Expanded(child: _buildUltraCompactFormField('Referral Code', _referralCodeController, Icons.card_giftcard)),
                  ],
                ),
                SizedBox(height: 12),
                _buildUltraCompactFormField('Skills', _skillsController, Icons.psychology),
                
                // Compact File Uploads
                SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(child: _buildUltraCompactFileUpload('Photo', _selectedPhotoPath, () => _pickImage(), Icons.camera_alt)),
                    SizedBox(width: 8),
                    Expanded(child: _buildUltraCompactFileUpload('Resume', _selectedResumePath, () => _pickResume(), Icons.description)),
                  ],
                ),
                
                // Terms Agreement
                SizedBox(height: 12),
                Row(
                  children: [
                    Checkbox(
                      value: _agreedToTerms,
                      onChanged: (value) {
                        setState(() {
                          _agreedToTerms = value ?? false;
                        });
                      },
                      fillColor: MaterialStateProperty.resolveWith((states) => Colors.white),
                      checkColor: Color(0xFF6A11CB),
                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    Expanded(
                      child: Text(
                        'I agree to terms and conditions',
                        style: GoogleFonts.poppins(
                          fontSize: 11,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
                
                Spacer(),
                
                // Submit Button
                ElevatedButton(
                  onPressed: _agreedToTerms ? () => _submitApplication() : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: Color(0xFF6A11CB),
                    padding: EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    minimumSize: Size(double.infinity, 40),
                  ),
                  child: Text(
                    'Submit Application',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCompactInstructionItem(String text) {
    return Padding(
      padding: EdgeInsets.only(bottom: 4),
      child: Text(
        text,
        style: GoogleFonts.poppins(
          fontSize: 12,
          color: Colors.white,
        ),
      ),
    );
  }

  Widget _buildCompactFormField(String label, TextEditingController controller, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        SizedBox(height: 6),
        TextFormField(
          controller: controller,
          style: GoogleFonts.poppins(color: Colors.white, fontSize: 12),
          decoration: InputDecoration(
            prefixIcon: Icon(icon, color: Colors.white70, size: 18),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide(color: Colors.white30),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide(color: Colors.white30),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide(color: Colors.white30),
            ),
            contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          ),
        ),
      ],
    );
  }

  Widget _buildUltraCompactFormField(String label, TextEditingController controller, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 10,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        SizedBox(height: 4),
        TextFormField(
          controller: controller,
          style: GoogleFonts.poppins(color: Colors.white, fontSize: 11),
          decoration: InputDecoration(
            prefixIcon: Icon(icon, color: Colors.white70, size: 16),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: Colors.white30),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: Colors.white30),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: Colors.white30),
            ),
            contentPadding: EdgeInsets.symmetric(horizontal: 6, vertical: 6),
            isDense: true,
          ),
        ),
      ],
    );
  }

  Widget _buildCompactFileUpload(String label, String? filePath, VoidCallback onTap, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        SizedBox(height: 6),
        GestureDetector(
          onTap: onTap,
          child: Container(
            width: double.infinity,
            height: 60,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: Colors.white30, style: BorderStyle.solid),
            ),
            child: filePath != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: Image.file(
                      File(filePath),
                      fit: BoxFit.cover,
                      width: double.infinity,
                      height: 60,
                    ),
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: Colors.white70, size: 20),
                      SizedBox(height: 2),
                      Text(
                        'Upload $label',
                        style: GoogleFonts.poppins(
                          color: Colors.white70,
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildUltraCompactFileUpload(String label, String? filePath, VoidCallback onTap, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 9,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        SizedBox(height: 4),
        GestureDetector(
          onTap: onTap,
          child: Container(
            width: double.infinity,
            height: 45,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(4),
              border: Border.all(color: Colors.white30, style: BorderStyle.solid),
            ),
            child: filePath != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: Image.file(
                      File(filePath),
                      fit: BoxFit.cover,
                      width: double.infinity,
                      height: 45,
                    ),
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: Colors.white70, size: 16),
                      SizedBox(height: 1),
                      Text(
                        'Upload $label',
                        style: GoogleFonts.poppins(
                          color: Colors.white70,
                          fontSize: 8,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildInstructionItem(String text) {
    return Padding(
      padding: EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '• ',
            style: GoogleFonts.poppins(
              fontSize: 16,
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFormField(String label, TextEditingController controller, IconData icon) {
    return Container(
      margin: EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
          SizedBox(height: 8),
          TextFormField(
            controller: controller,
            style: GoogleFonts.poppins(color: Colors.white),
            decoration: InputDecoration(
              prefixIcon: Icon(icon, color: Colors.white70),
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
                borderSide: BorderSide(color: Colors.white30),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFileUpload(String label, String? filePath, VoidCallback onTap, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        SizedBox(height: 8),
        GestureDetector(
          onTap: onTap,
          child: Container(
            width: double.infinity,
            height: 80,
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.white30, style: BorderStyle.solid),
            ),
            child: filePath != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.file(
                      File(filePath),
                      fit: BoxFit.cover,
                      width: double.infinity,
                      height: 80,
                    ),
                  )
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: Colors.white70, size: 32),
                      SizedBox(height: 4),
                      Text(
                        'Tap to upload $label',
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
    );
  }

  Future<void> _pickImage() async {
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
      }
    } catch (e) {
      print('Error picking image: $e');
    }
  }

  Future<void> _pickResume() async {
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
      }
    } catch (e) {
      print('Error picking resume: $e');
    }
  }

  Future<void> _submitApplication() async {
    // Validate form
    if (_nameController.text.isEmpty ||
        _emailController.text.isEmpty ||
        _phoneController.text.isEmpty ||
        _educationController.text.isEmpty ||
        _experienceController.text.isEmpty ||
        _skillsController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Please fill all required fields'),
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

    try {
      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return Center(
            child: CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          );
        },
      );

      // Prepare data for submission
      final data = {
        'name': _nameController.text,
        'email': _emailController.text,
        'phone': _phoneController.text,
        'education': _educationController.text,
        'experience': _experienceController.text,
        'skills': _skillsController.text,
        'job_id': widget.job.id,
        'referral_code': _referralCodeController.text.isNotEmpty ? _referralCodeController.text : '',
        'photo_path': _selectedPhotoPath ?? '',
        'resume_path': _selectedResumePath ?? '',
        'company_name': widget.job.companyName,
        'package': widget.job.package,
        'profile': widget.job.jobTitle,
        'district': 'Mumbai', // Default location
      };

      print('DEBUG: Submitting application data: $data');

      // Send to backend to store in database
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/submit_job_application_working.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: jsonEncode(data),
      ).timeout(Duration(seconds: 30));

      print('DEBUG: Response status: ${response.statusCode}');
      print('DEBUG: Response body: ${response.body}');

      // Hide loading indicator
      Navigator.pop(context);

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        
        if (result['success']) {
          // Show success message
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ Application submitted successfully! Data stored in database.'),
              backgroundColor: Colors.green,
              duration: Duration(seconds: 3),
            ),
          );
          
          // Close the form and show payment instructions
          Navigator.pop(context);
          
          // Show payment instructions modal
          _showPaymentInstructionsModal(widget.job, _referralCodeController.text);
        } else {
          // Show error message
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Error: ${result['message']}'),
              backgroundColor: Colors.red,
              duration: Duration(seconds: 5),
            ),
          );
        }
      } else {
        // Show error message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Network error: HTTP ${response.statusCode}. Please try again.'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 5),
          ),
        );
      }
    } catch (e) {
      print('DEBUG: Error in _submitApplication: $e');
      
      // Hide loading indicator
      Navigator.pop(context);
      
      // Show error message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Error: $e'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 5),
        ),
      );
    }
  }

  void _showPaymentInstructionsModal(Job job, String referralCode) {
    String instructions = '';
    String amountText = '';
    double amount = 0.0;

    final packageValue = double.tryParse(job.package.replaceAll('LPA', '').replaceAll('₹', '').trim());
    if (packageValue != null && packageValue >= 20) {
      instructions = '''
1. **Application Review**: Your application has been submitted and stored in our database.

2. **Document Verification**: Our team will verify your uploaded documents (photo and resume).

3. **Skill Assessment**: You may be contacted for a brief skill assessment or interview.

4. **Payment Process**: Complete the payment of ₹2000 to proceed with job matching.

5. **Job Matching**: After payment confirmation, we'll match you with suitable opportunities.

6. **Support**: Our team will guide you through the entire process.

**Referral Program**: If you used a referral code, the referrer will receive 20% commission on your registration fee.
      ''';
      amountText = '₹2000';
      amount = 2000.0;
    } else {
      instructions = '''
1. **Application Review**: Your application has been submitted and stored in our database.

2. **Document Verification**: Our team will verify your uploaded documents (photo and resume).

3. **Local Job Matching**: We'll match you with suitable local job opportunities.

4. **Payment Process**: Complete the payment of ₹1000 to proceed with job placement.

5. **Job Placement**: After payment confirmation, we'll connect you with local employers.

6. **Support**: Our team will provide ongoing support throughout your job search.

**Referral Program**: If you used a referral code, the referrer will receive 20% commission on your registration fee.
      ''';
      amountText = '₹1000';
      amount = 1000.0;
    }

    bool agreedToTerms = false;

    showModalBottomSheet(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => Container(
          height: MediaQuery.of(context).size.height * 0.8,
          padding: EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Payment Instructions',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Application Fee: $amountText',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.green[700],
                        ),
                      ),
                      SizedBox(height: 16),
                      Text(
                        instructions,
                        style: TextStyle(fontSize: 16, height: 1.5),
                      ),
                      SizedBox(height: 20),
                      Row(
                        children: [
                          Checkbox(
                            value: agreedToTerms,
                            onChanged: (value) {
                              setState(() {
                                agreedToTerms = value ?? false;
                              });
                            },
                          ),
                          Expanded(
                            child: Text(
                              'I agree to the terms and conditions and understand the application fee.',
                              style: TextStyle(fontSize: 14),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                                  child: ElevatedButton(
                    onPressed: agreedToTerms
                        ? () {
                            Navigator.pop(context);
                            _showPaymentOptions(widget.job, amount, _referralCodeController.text);
                          }
                        : null,
                  child: Text('Proceed to Payment'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: agreedToTerms ? Colors.green : Colors.grey,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showPaymentOptions(Job job, double amount, String referralCode) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Payment Options',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 16),
            Text(
              'Amount: ₹${amount.toStringAsFixed(0)}',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            if (referralCode.isNotEmpty) ...[
              SizedBox(height: 8),
              Text(
                'Referral Code: $referralCode',
                style: TextStyle(fontSize: 14, color: Colors.blue),
              ),
              Text(
                'Referrer will receive 20% commission',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
            SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                  _openPaymentGateway(job, amount, referralCode);
                },
                child: Text('Pay with Razorpay'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                ),
              ),
            ),
            SizedBox(height: 16),
            Text(
              'Secure payment powered by Razorpay',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }

  void _openPaymentGateway(Job job, double amount, String referralCode) {
    try {
      // Create payment options directly
      var options = {
        'key': 'rzp_live_fgQr0ACWFbL4pN',
        'amount': (amount * 100).toInt(), // Amount in paise
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
      
      print('DEBUG: Opening payment gateway with options: $options');
      // Get the Razorpay instance from the parent widget
      final mainScreen = context.findAncestorStateOfType<_MainScreenState>();
      if (mainScreen != null) {
        mainScreen._razorpay.open(options);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment gateway not available - MainScreen not found'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      print('DEBUG: Error in _openPaymentGateway: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening payment gateway: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
}

class SuccessfulCandidatesScreen extends StatelessWidget {
  final List<JobApplication> candidates;

  SuccessfulCandidatesScreen({required this.candidates});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('All Successful Candidates'),
        backgroundColor: Color(0xFF6A11CB),
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Column(
          children: [
            // Header Section
            Container(
              padding: EdgeInsets.all(20),
              child: Column(
                children: [
                  Text(
                    'Our Successfully Placed Candidates',
                    style: GoogleFonts.poppins(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 8),
                  Text(
                    '${candidates.length} candidates have been successfully placed',
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      color: Colors.white70,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            // Candidates Grid
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
                ),
                child: GridView.builder(
                  padding: EdgeInsets.all(20),
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.85,
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 16,
                  ),
                  itemCount: candidates.length,
                  itemBuilder: (context, index) {
                    final candidate = candidates[index];
                    return _buildCandidateCard(candidate);
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCandidateCard(JobApplication candidate) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 15,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Profile Photo Section
          Container(
            height: 100,
            width: double.infinity,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              gradient: LinearGradient(
                colors: [Color(0xFF6A11CB), Color(0xFF2575FC)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Stack(
              children: [
                // Background Pattern
                Positioned(
                  top: -20,
                  right: -20,
                  child: Opacity(
                    opacity: 0.1,
                    child: Icon(
                      Icons.star,
                      size: 80,
                      color: Colors.white,
                    ),
                  ),
                ),
                // Profile Photo
                Center(
                  child: Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 3),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.2),
                          blurRadius: 10,
                          offset: Offset(0, 5),
                        ),
                      ],
                    ),
                    child: candidate.photoPath.isNotEmpty
                        ? ClipOval(
                            child: Image.network(
                              candidate.photoPath,
                              fit: BoxFit.cover,
                              loadingBuilder: (context, child, loadingProgress) {
                                if (loadingProgress == null) return child;
                                return Container(
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.2),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Center(
                                    child: CircularProgressIndicator(
                                      value: loadingProgress.expectedTotalBytes != null
                                          ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                          : null,
                                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                    ),
                                  ),
                                );
                              },
                              errorBuilder: (context, error, stackTrace) => Container(
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(Icons.person, size: 30, color: Colors.white),
                              ),
                            ),
                          )
                        : Container(
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(Icons.person, size: 30, color: Colors.white),
                          ),
                  ),
                ),
              ],
            ),
          ),
          // Candidate Details
          Expanded(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Name
                  Text(
                    candidate.studentName,
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 5),
                  // Experience
                  Row(
                    children: [
                      Icon(Icons.work, color: Colors.blue[600], size: 14),
                      SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          '${candidate.experience}',
                          style: GoogleFonts.poppins(
                            fontSize: 12,
                            color: Colors.grey[700],
                            fontWeight: FontWeight.w500,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 6),
                  // Skills
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.psychology, color: Colors.green[600], size: 14),
                      SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          candidate.skills,
                          style: GoogleFonts.poppins(
                            fontSize: 8,
                            color: Colors.grey[600],
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  Spacer(),
                  // Location
                  if (candidate.district.isNotEmpty) ...[
                    Row(
                      children: [
                        Icon(Icons.location_on, color: Colors.red[600], size: 12),
                        SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            candidate.district,
                            style: GoogleFonts.poppins(
                              fontSize: 10,
                              color: Colors.grey[500],
                              fontWeight: FontWeight.w500,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  String _formatDateForCandidate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }
}