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
import 'package:playsmart/controller/successful_candidates_controller.dart';
import 'package:playsmart/Models/successful_candidate.dart';
import 'package:playsmart/controller/new_jobs_controller.dart';
import 'package:playsmart/Models/new_job.dart';
import 'package:playsmart/mega_quiz_screen.dart';
import 'package:playsmart/mega_result_screen.dart';
import 'package:playsmart/mega_score_service.dart';
import 'package:playsmart/profile_Screen.dart';
import 'package:playsmart/quiz_screen.dart';
import 'package:playsmart/score_service.dart';
import 'package:playsmart/splash_screen.dart';
import 'package:playsmart/successful_candidates_screen.dart';
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
     late ScrollController _companyLogosScrollController;
     late ScrollController _higherPackageJobsScrollController;
     late ScrollController _localJobsScrollController;
     late ScrollController _successfulCandidatesScrollController;
   Timer? _autoScrollTimer; // Make nullable
   Timer? _companyLogosScrollTimer; // Timer for company logos auto-scroll
   Timer? _higherPackageJobsScrollTimer; // Timer for higher package jobs auto-scroll
   Timer? _localJobsScrollTimer; // Timer for local jobs auto-scroll
   Timer? _successfulCandidatesScrollTimer; // Timer for successful candidates auto-scroll
  double userBalance = 0.0;
  List<Contest> miniContests = [];
  List<Contest> megaContests = [];
  List<Job> jobs = [];
  List<Job> higherPackageJobs = [];
  List<Job> localJobs = [];
  List<NewJob> newJobs = [];
  List<NewJob> newHigherPackageJobs = [];
  List<NewJob> newLocalJobs = [];
  List<JobApplication> jobApplications = [];
  List<SuccessfulCandidate> successfulCandidates = [];
  Map<int, String> userJobApplications = {}; // Track user's job applications
  Job? _currentJobApplication; // Track current job being applied for
  int? _currentApplicationId; // Track current application ID for payment
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

  // Add this variable to your state class
  String _successfulCandidatesHeading = 'Our Successfully Placed';
  String _successfulCandidatesSubHeading = 'Candidates';

  // Add this method to fetch headings
  Future<void> _fetchContentHeadings() async {
    try {
      final response = await http.get(
        Uri.parse('https://playsmart.co.in/fetch_content_headings.php?section=successful_candidates'),
      ).timeout(Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] && data['data'] != null) {
          setState(() {
            _successfulCandidatesHeading = data['data']['heading'] ?? 'Our Successfully Placed';
            _successfulCandidatesSubHeading = data['data']['sub_heading'] ?? 'Candidates';
          });
        }
      }
    } catch (e) {
      print('Error fetching content headings: $e');
      // Keep default values if API fails
    }
  }

  @override
  void initState() {
    super.initState();
    
    // Initialize Razorpay - RESTORE ORIGINAL WORKING CODE
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
     _companyLogosScrollController = ScrollController();
     _higherPackageJobsScrollController = ScrollController();
     _localJobsScrollController = ScrollController();
     _successfulCandidatesScrollController = ScrollController();
    
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
    
    // Test Razorpay after a short delay
    Future.delayed(Duration(seconds: 3), () {
      if (mounted) {
        print('DEBUG: Testing Razorpay initialization...');
        print('DEBUG: Razorpay instance: $_razorpay');
        print('DEBUG: Razorpay type: ${_razorpay.runtimeType}');
      }
    });
    
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
    _fetchContentHeadings(); // Add this line
  }

  @override
  void dispose() {
    _animationController.dispose();
    _floatingIconsController.dispose();
    _pulseController.dispose();
         _jobApplicationsScrollController.dispose();
     _companyLogosScrollController.dispose();
     _higherPackageJobsScrollController.dispose();
     _localJobsScrollController.dispose();
     _successfulCandidatesScrollController.dispose();
     _autoScrollTimer?.cancel(); // Safe cancel
     _autoScrollTimer = null; // Set to null
     _companyLogosScrollTimer?.cancel();
     _companyLogosScrollTimer = null;
     _higherPackageJobsScrollTimer?.cancel();
     _higherPackageJobsScrollTimer = null;
     _localJobsScrollTimer?.cancel();
     _localJobsScrollTimer = null;
     _successfulCandidatesScrollTimer?.cancel();
     _successfulCandidatesScrollTimer = null;
     _refreshTimer?.cancel();
    
    // Safely dispose Razorpay
    try {
      if (_razorpay != null) {
        _razorpay.clear();
      }
    } catch (e) {
      print('DEBUG: Error disposing Razorpay: $e');
    }
    
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
        
        // Check and reinitialize Razorpay if needed
        if (_razorpay == null) {
          print('DEBUG: Razorpay is null on resume, reinitializing...');
          _razorpay = Razorpay();
          _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
          _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
          _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
        }
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
        
        // Don't redirect if payment just completed
        if (_paymentJustCompleted) {
          print('🔐 DEBUG: 🚫 Payment just completed, preventing login redirect');
          _paymentJustCompleted = false; // Reset flag
          return;
        }
        
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
          
          // Don't redirect if payment just completed
          if (_paymentJustCompleted) {
            print('🔐 DEBUG: 🚫 Payment just completed, preventing login redirect');
            _paymentJustCompleted = false; // Reset flag
            return;
          }
          
          _redirectToLogin();
        }
      } catch (e2) {
        print('🔐 DEBUG: ❌ Failed to recover session: $e2');
        
        // Don't redirect if payment just completed
        if (_paymentJustCompleted) {
          print('🔐 DEBUG: 🚫 Payment just completed, preventing login redirect');
          _paymentJustCompleted = false; // Reset flag
          return;
        }
        
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
     print('DEBUG: Initializing app data...');
     fetchUserBalance();
     fetchContests();
     fetchJobApplications();
     fetchSuccessfulCandidates();
     fetchNewJobs();
     fetchJobs();
     _startRefreshTimer();
     _startAutoScroll(); // This will now be safe
     _startCompanyLogosAutoScroll(); // Start auto-scroll for company logos
     _startSuccessfulCandidatesAutoScroll(); // Start auto-scroll for successful candidates
     
     print('DEBUG: App data initialization started');
   }





  void _handlePaymentSuccess(PaymentSuccessResponse response) async {
    print('=== PAYMENT SUCCESS HANDLER STARTED ===');
    print('Payment Success: ${response.paymentId}');
    print('Current Job Application: ${_currentJobApplication?.id}');
    print('userJobApplications before update: $userJobApplications');
    
    // Close the application modal first
    Navigator.of(context).pop();
    
    // Show success status popup
    _showPaymentSuccessStatus(response.paymentId ?? '');
    
    // Store the job application status locally IMMEDIATELY
    if (_currentJobApplication != null) {
      // Use the actual job ID from the current job being applied to
      int jobId = _currentJobApplication!.id;
      print('DEBUG: Setting job $jobId status to pending (initial status)');
      print('DEBUG: _currentJobApplication!.id = ${_currentJobApplication!.id}');
      
      // Update local status immediately and PERSIST it
      // Set initial status as 'pending' since payment is complete but application is under review
      userJobApplications[jobId] = 'pending';
      print('DEBUG: userJobApplications after update: $userJobApplications');
      
      // Force immediate UI update to show status button
      setState(() {
        print('DEBUG: UI updated with new application status');
      });
      
      // Upload files if they were selected
      if (_selectedPhotoPath != null && _selectedResumePath != null) {
        _uploadFiles(
          _currentJobApplication!,
          _selectedPhotoPath!,
          _selectedResumePath!,
        );
      }
      
      // Update payment status in database
      if (_currentApplicationId != null) {
        await _updatePaymentStatus(
          _currentApplicationId!,
          response.paymentId ?? '',
          'completed',
        );
      }
      
      // Process payment with backend to trigger email sending
      await _processPaymentWithBackend(
        _currentJobApplication!,
        response.paymentId ?? '',
        response.orderId ?? '',
        response.signature ?? '',
      );
      
      // Final UI refresh to ensure status button is visible
      setState(() {
        print('DEBUG: Final UI refresh after backend processing');
      });
    }
    
    print('Payment ID: ${response.paymentId}');
    print('Payment Signature: ${response.signature}');
    
    // Refresh job applications but PRESERVE local status
    await _refreshJobApplicationsPreserveLocal();
    
    // Also refresh jobs to ensure they're displayed
    await fetchJobs();
    
    // Refresh new jobs to ensure they're displayed
    await fetchNewJobs();
    
    // Force one more UI refresh to ensure everything is updated
    setState(() {
      print('DEBUG: Final force refresh after all data fetching');
    });
    
    // Show success message to user
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(Icons.check_circle, color: Colors.white),
            SizedBox(width: 8),
            Expanded(
              child: Text(
                'Payment successful! Application submitted. Check your email for details.',
                style: TextStyle(fontSize: 16),
              ),
            ),
          ],
        ),
        backgroundColor: Colors.green,
        duration: Duration(seconds: 5),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  // New method to refresh job applications while preserving local status
  Future<void> _refreshJobApplicationsPreserveLocal() async {
    try {
      print('DEBUG: Starting to fetch job applications (preserving local status)...');
      
      final applicationsData = await JobApplicationController.fetchJobApplications()
          .timeout(Duration(seconds: 5), onTimeout: () {
        print('DEBUG: Job applications fetch timed out after 5 seconds');
        throw TimeoutException('Job applications fetch timed out', Duration(seconds: 5));
      });
      
      print('DEBUG: Received ${applicationsData.length} applications from API');
      
      if (mounted) {
        setState(() {
          jobApplications = applicationsData;
          
          // Update local map with fetched applications BUT preserve local statuses
          for (var application in applicationsData) {
            // Only update if we don't have a local status for this job, or if the fetched status is more advanced
            if (!userJobApplications.containsKey(application.jobId)) {
              userJobApplications[application.jobId] = application.applicationStatus;
              print('DEBUG: Job ${application.jobId} -> New Status: ${application.applicationStatus}');
            } else {
              // Keep the more advanced status (pending < shortlisted < accepted)
              String currentStatus = userJobApplications[application.jobId]!;
              String fetchedStatus = application.applicationStatus;
              
              if (_isStatusMoreAdvanced(fetchedStatus, currentStatus)) {
                userJobApplications[application.jobId] = fetchedStatus;
                print('DEBUG: Job ${application.jobId} -> Updated to more advanced status: $fetchedStatus');
              } else {
                print('DEBUG: Preserving local status for job ${application.jobId}: $currentStatus');
              }
            }
          }
          print('DEBUG: Updated userJobApplications map: $userJobApplications');
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

  // Method to check if a specific job has been applied to
  bool hasAppliedToJob(int jobId) {
    return userJobApplications.containsKey(jobId);
  }

  // Method to check if one status is more advanced than another
  bool _isStatusMoreAdvanced(String newStatus, String currentStatus) {
    // Define status hierarchy: pending < shortlisted < accepted
    Map<String, int> statusHierarchy = {
      'pending': 1,
      'shortlisted': 2,
      'accepted': 3,
    };
    
    int newLevel = statusHierarchy[newStatus.toLowerCase()] ?? 0;
    int currentLevel = statusHierarchy[currentStatus.toLowerCase()] ?? 0;
    
    return newLevel > currentLevel;
  }

  // Method to get application status for a specific job
  String getJobApplicationStatus(int jobId) {
    return userJobApplications[jobId] ?? '';
  }
  
  // Method to update payment status in database
  Future<void> _updatePaymentStatus(int applicationId, String paymentId, String status) async {
    try {
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/update_payment_status.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'application_id': applicationId,
          'payment_id': paymentId,
          'payment_status': status,
        }),
      ).timeout(Duration(seconds: 15));
      
      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        if (responseData['success']) {
          print('DEBUG: Payment status updated successfully: $status');
        } else {
          print('DEBUG: Failed to update payment status: ${responseData['message']}');
        }
      } else {
        print('DEBUG: HTTP error updating payment status: ${response.statusCode}');
      }
    } catch (e) {
      print('DEBUG: Error updating payment status: $e');
    }
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
  


  void _showPaymentSuccessStatus(String paymentId) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: Colors.transparent,
          contentPadding: EdgeInsets.zero,
          content: Container(
            width: MediaQuery.of(context).size.width * 0.85,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF28a745), Color(0xFF20c997)],
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
                  // Success Icon
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.check_circle,
                      size: 50,
                      color: Colors.white,
                    ),
                  ),
                  SizedBox(height: 20),
                  
                  // Success Title
                  Text(
                    '🎉 Payment Successful!',
                    style: GoogleFonts.poppins(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 16),
                  
                  // Success Message
                  Text(
                    'Your job application has been submitted successfully!',
                    style: GoogleFonts.poppins(
                      fontSize: 16,
                      color: Colors.white,
                      height: 1.5,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 20),
                  
                  // Payment Details
                  Container(
                    padding: EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        Text(
                          'Payment Details',
                          style: GoogleFonts.poppins(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Payment ID:',
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: Colors.white70,
                              ),
                            ),
                            Text(
                              paymentId,
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Status:',
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: Colors.white70,
                              ),
                            ),
                            Container(
                              padding: EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.2),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'ACCEPTED',
                                style: GoogleFonts.poppins(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  SizedBox(height: 24),
                  
                  // Next Steps
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
                          'What\'s Next?',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        SizedBox(height: 12),
                        Text(
                          '• Your application is now under review\n'
                          '• You will receive updates via email\n'
                          '• Check your application status in the app\n'
                          '• Our team will contact you soon',
                          style: GoogleFonts.poppins(
                            fontSize: 14,
                            color: Colors.white70,
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(height: 24),
                  
                  // Close Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.of(context).pop();
                        // Refresh the UI to show status button
                        setState(() {});
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: Color(0xFF28a745),
                        padding: EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        'Got It!',
                        style: GoogleFonts.poppins(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
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

  void _showJobApplicationModal(Job job) {
    _currentJobApplication = job; // Set current job for tracking
    
    // Determine if this is a new job (from new_jobs table) or old job
    bool isNewJob = job.companyName.isEmpty || job.companyName == 'Company';
    String jobType = isNewJob ? 'higher_job' : 'higher_job'; // Default to higher_job for old jobs
    
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

  // Test payment gateway method
  void _testPaymentGateway() {
    print('DEBUG: Testing payment gateway...');
    print('DEBUG: Razorpay instance: $_razorpay');
    
    // Check if Razorpay is initialized
    if (_razorpay == null) {
      print('ERROR: Razorpay is null! Reinitializing...');
      _razorpay = Razorpay();
      _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
      _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
      _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
    }
    
    var testOptions = {
      'key': 'rzp_live_fgQr0ACWFbL4pN',
      'amount': 100, // 1 rupee in paise
      'name': 'PlaySmart Test',
      'description': 'Payment Gateway Test',
      'prefill': {
        'contact': '',
        'email': '',
      },
    };

    print('DEBUG: Test payment options: ${jsonEncode(testOptions)}');

    try {
      print('DEBUG: Attempting to open test payment...');
      _razorpay.open(testOptions);
      print('DEBUG: Test payment opened successfully');
    } catch (e) {
      print('ERROR: Failed to open test payment: $e');
      print('ERROR: Full error details: ${e.toString()}');
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Test payment failed: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 5),
        ),
      );
    }
  }

  void _initiatePayment(Job job) {
    print('DEBUG: Starting payment initiation for job: ${job.id}');
    print('DEBUG: Razorpay instance: $_razorpay');
    
    // Check if Razorpay is initialized
    if (_razorpay == null) {
      print('ERROR: Razorpay is null! Reinitializing...');
      _razorpay = Razorpay();
      _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
      _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
      _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
    }
    
    // Determine amount based on job package (10LPA threshold)
    final packageValue = double.tryParse(job.package.replaceAll('LPA', '').replaceAll('₹', '').trim());
    final isHighPackage = packageValue != null && packageValue >= 10;
    final amountInRupees = isHighPackage ? 2000.0 : 1000.0;
    final amountInPaise = (amountInRupees * 100).toInt();
    
    print('DEBUG: Package: ${job.package}, Amount: $amountInRupees, Paise: $amountInPaise');
    
    var options = {
      'key': 'rzp_live_fgQr0ACWFbL4pN', // Restore original working key
      'amount': amountInPaise, // Amount in paise (₹0.1 = 10 paise, ₹0.2 = 20 paise)
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

    print('DEBUG: Payment options: ${jsonEncode(options)}');

    try {
      print('DEBUG: Attempting to open Razorpay...');
      _razorpay.open(options);
      print('DEBUG: Razorpay opened successfully');
    } catch (e) {
      print('ERROR: Failed to open Razorpay: $e');
      print('ERROR: Full error details: ${e.toString()}');
      
      // Show detailed error to user
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Payment gateway error: ${e.toString()}'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 5),
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

      // Get user email from the current application
      String userEmail = 'user@example.com'; // Default fallback
      if (_currentJobApplication != null) {
        // Try to get email from the application form
        // Since we don't have direct access to the form controllers here,
        // we'll use a different approach - get it from the backend
        userEmail = 'user@example.com'; // This will be updated by backend
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
          'email': userEmail,
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

  void _showApplicationStatus(Job job, String status) async {
    print('DEBUG: Showing application status for job ${job.id} with status: $status');
    
    // Fetch real application data from database
    try {
      final prefs = await SharedPreferences.getInstance();
      final userEmail = prefs.getString('user_email') ?? '';
      
      print('DEBUG: Fetching application data for user: $userEmail, job: ${job.id}');
      
      // Show loading dialog first
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (BuildContext context) {
          return AlertDialog(
            content: Row(
              children: [
                CircularProgressIndicator(),
                SizedBox(width: 20),
                Text('Loading application details...'),
              ],
            ),
          );
        },
      );
      
      // Fetch application data from backend
      final applicationData = await _fetchApplicationDetails(job.id, userEmail);
      
      // Close loading dialog
      Navigator.of(context).pop();
      
      if (applicationData != null) {
        _showDetailedApplicationStatus(job, applicationData);
      } else {
        _showBasicApplicationStatus(job, status);
      }
      
    } catch (e) {
      print('DEBUG: Error fetching application details: $e');
      Navigator.of(context).pop(); // Close loading dialog
      _showBasicApplicationStatus(job, status);
    }
  }



  // Fetch application details from backend
  Future<Map<String, dynamic>?> _fetchApplicationDetails(int jobId, String userEmail) async {
    try {
      print('DEBUG: Fetching application details for job $jobId and user $userEmail');
      
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/check_job_application_status.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'job_id': jobId,
          'user_email': userEmail,
        }),
      ).timeout(Duration(seconds: 10));

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        if (result['success'] && result['has_applied']) {
          print('DEBUG: Application details fetched: $result');
          return result['application'];
        }
      }
      
      print('DEBUG: No application details found');
      return null;
    } catch (e) {
      print('DEBUG: Error fetching application details: $e');
      return null;
    }
  }

  // Show detailed application status with real data
  void _showDetailedApplicationStatus(Job job, Map<String, dynamic> applicationData) {
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
                  children: [
                    // Header
                    Row(
                      children: [
                        Container(
                          width: 50,
                          height: 50,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(25),
                          ),
                          child: Icon(
                            Icons.work,
                            color: Colors.white,
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
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              Text(
                                job.companyName,
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
                    
                    // Application Details from Database
                    Container(
                      padding: EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Application Details',
                            style: GoogleFonts.poppins(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(height: 20),
                          
                          // Real data from database
                          _buildDetailRow('Status', applicationData['application_status'] ?? 'Pending'),
                          _buildDetailRow('Applied Date', applicationData['applied_date'] ?? 'N/A'),
                          _buildDetailRow('Payment ID', applicationData['payment_id'] ?? 'N/A'),
                          _buildDetailRow('Profile', applicationData['profile'] ?? 'N/A'),
                          _buildDetailRow('Experience', applicationData['experience'] ?? 'N/A'),
                          _buildDetailRow('Skills', applicationData['skills'] ?? 'N/A'),
                          _buildDetailRow('District', applicationData['district'] ?? 'N/A'),
                          _buildDetailRow('Package', job.package),
                        ],
                      ),
                    ),
                    
                    SizedBox(height: 24),
                    
                    // Status Progress Section
                    Container(
                      padding: EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Application Progress',
                            style: GoogleFonts.poppins(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(height: 20),
                          
                          // Progress Steps
                          _buildProgressStep('Application Submitted', true, 0),
                          _buildProgressStep('Screening In Progress', 
                            (applicationData['application_status'] ?? '').toString().toLowerCase() == 'pending', 1),
                          _buildProgressStep('Interview Scheduled', 
                            (applicationData['application_status'] ?? '').toString().toLowerCase() == 'shortlisted', 2),
                          _buildProgressStep('Offer Letter Pending', 
                            (applicationData['application_status'] ?? '').toString().toLowerCase() == 'accepted', 3),
                          _buildProgressStep('Hired', 
                            (applicationData['application_status'] ?? '').toString().toLowerCase() == 'accepted', 4),
                        ],
                      ),
                    ),
                    
                    SizedBox(height: 24),
                    
                    // Close Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () => Navigator.of(context).pop(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: Color(0xFF6A11CB),
                          padding: EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'Close',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
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

  // Show basic application status (fallback)
  void _showBasicApplicationStatus(Job job, String status) {
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
                  children: [
                    // Header
                    Row(
                      children: [
                        Container(
                          width: 50,
                          height: 50,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(25),
                          ),
                          child: Icon(
                            Icons.work,
                            color: Colors.white,
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
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              Text(
                                job.companyName,
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
                    
                    // Status Progress
                    Container(
                      padding: EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        children: [
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
                          _buildProgressStep('Application Submitted', true, 1),
                          _buildProgressStep('Screening In Progress', status == 'pending', 2),
                          _buildProgressStep('Interview Scheduled', status == 'shortlisted', 3),
                          _buildProgressStep('Offer Letter Pending', status == 'accepted', 4),
                          _buildProgressStep('Hired', false, 5),
                        ],
                      ),
                    ),
                    
                    SizedBox(height: 24),
                    
                    // Close Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () => Navigator.of(context).pop(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: Color(0xFF6A11CB),
                          padding: EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'Close',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
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



  Future<void> _processPaymentWithBackend(Job job, String paymentId, String orderId, String signature) async {
    try {
      print('DEBUG: Processing payment with backend...');
      
      // Get user email from the actual form submission, not SharedPreferences
      // The email should come from the job application form that was submitted
      String userEmail = 'user@example.com'; // This will be updated by the backend
      
      // CRITICAL FIX: Get the actual user data from the current job application
      if (_currentJobApplication != null) {
        // Try to get email from the application form data
        // Since we don't have direct access to form controllers here,
        // we'll use a different approach - get it from the backend
        print('DEBUG: Current job application found: ${_currentJobApplication!.id}');
        print('DEBUG: Will get actual user data from backend');
      }
      
      // Determine payment amount based on job package
      final packageValue = double.tryParse(job.package.replaceAll('LPA', '').replaceAll('₹', '').trim());
      final isHighPackage = packageValue != null && packageValue >= 10;
      final amount = isHighPackage ? 2000.0 : 1000.0;
      
      print('DEBUG: Job package: ${job.package}, Amount: $amount, IsHighPackage: $isHighPackage');
      
      // CRITICAL FIX: Send job_id instead of application_id to prevent confusion
      // Backend will create the application record with the actual form data
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/process_payment.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'job_id': job.id, // Changed from application_id to job_id for clarity
          'payment_id': paymentId,
          'amount': amount,
          'razorpay_payment_id': paymentId,
          'razorpay_order_id': orderId,
          'razorpay_signature': signature,
          'payment_method': 'razorpay',
          'user_email': userEmail, // This will be updated by backend with actual form data
          'gateway_response': {
            'payment_id': paymentId,
            'order_id': orderId,
            'signature': signature,
          },
        }),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final result = jsonDecode(response.body);
        if (result['success']) {
          print('DEBUG: Payment processed successfully with backend');
          print('DEBUG: Backend response: $result');
        } else {
          print('DEBUG: Backend payment processing failed: ${result['message']}');
        }
      } else {
        print('DEBUG: Backend API error: HTTP ${response.statusCode}');
        print('DEBUG: Response body: ${response.body}');
      }
    } catch (e) {
      print('DEBUG: Error processing payment with backend: $e');
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
          
          // Update local map with fetched applications BUT preserve local 'accepted' status
          for (var application in applicationsData) {
            // Only update if we don't have a local 'accepted' status for this job
            if (userJobApplications[application.jobId] != 'accepted') {
              userJobApplications[application.jobId] = application.applicationStatus;
              print('DEBUG: Job ${application.jobId} -> Status: ${application.applicationStatus}');
            } else {
              print('DEBUG: Preserving local accepted status for job ${application.jobId}');
            }
          }
          print('DEBUG: Updated userJobApplications map: $userJobApplications');
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

  Future<void> fetchSuccessfulCandidates() async {
    try {
      print('DEBUG: Starting to fetch successful candidates...');
      final candidatesData = await SuccessfulCandidatesController.fetchSuccessfulCandidates();
      print('DEBUG: Received ${candidatesData.length} successful candidates from API');
      if (mounted) {
        setState(() {
          successfulCandidates = candidatesData;
        });
        print('DEBUG: Updated state with ${successfulCandidates.length} successful candidates');
      }
      print('DEBUG: Fetched ${successfulCandidates.length} successful candidates successfully');
    } catch (e) {
      print('Error fetching successful candidates: $e');
      if (mounted) {
        setState(() {
          successfulCandidates = [];
        });
      }
    }
  }

  Future<void> fetchNewJobs() async {
    try {
      print('DEBUG: Starting to fetch new jobs...');
      final jobsData = await NewJobsController.fetchNewJobs();
      print('DEBUG: Received ${jobsData.length} new jobs from API');
      if (mounted) {
        setState(() {
          newJobs = jobsData;
          newHigherPackageJobs = jobsData.where((job) => job.isHigherJob).toList();
          newLocalJobs = jobsData.where((job) => job.isLocalJob).toList();
        });
        print('DEBUG: Updated state with ${newJobs.length} new jobs');
        print('DEBUG: Higher package jobs: ${newHigherPackageJobs.length}');
        print('DEBUG: Local jobs: ${newLocalJobs.length}');
      }
      print('DEBUG: Fetched ${newJobs.length} new jobs successfully');
    } catch (e) {
      print('Error fetching new jobs: $e');
      if (mounted) {
        setState(() {
          newJobs = [];
          newHigherPackageJobs = [];
          newLocalJobs = [];
        });
      }
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
        print('DEBUG: Higher package jobs: ${higherPackageJobs.length}');
        print('DEBUG: Local jobs: ${localJobs.length}');
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
    
    // Higher Package Jobs (10 LPA and above)
    higherPackageJobs = allJobs.where((job) {
      if (job.package == null || job.package.isEmpty) return false;
      
      // Extract numeric value from package string (e.g., "12LPA" -> 12)
      final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
      return packageValue != null && packageValue >= 10;
    }).toList();

    // Local Jobs (below 10 LPA)
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
        await fetchNewJobs();
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

   void _startCompanyLogosAutoScroll() {
     _companyLogosScrollTimer?.cancel(); // Cancel existing timer if any
     
     print('DEBUG: Starting company logos auto-scroll');
     _companyLogosScrollTimer = Timer.periodic(Duration(milliseconds: 40), (timer) {
       if (!mounted) {
         print('DEBUG: Stopping company logos auto-scroll - not mounted');
         timer.cancel();
         _companyLogosScrollTimer = null;
         return;
       }
       
       if (_companyLogosScrollController.hasClients) {
         final maxScroll = _companyLogosScrollController.position.maxScrollExtent;
         final currentScroll = _companyLogosScrollController.position.pixels;
         
         // Continuous right-to-left scrolling
         if (currentScroll >= maxScroll) {
           // Reset to beginning when reaching the end for seamless loop
           _companyLogosScrollController.jumpTo(0);
         } else {
           // Smooth continuous scrolling to the left
           _companyLogosScrollController.jumpTo(currentScroll + 1.2);
         }
       } else {
         print('DEBUG: Company logos scroll controller not ready');
       }
     });
   }

   void _startHigherPackageJobsAutoScroll() {
     _higherPackageJobsScrollTimer?.cancel(); // Cancel existing timer if any
     
     print('DEBUG: Starting higher package jobs auto-scroll');
     _higherPackageJobsScrollTimer = Timer.periodic(Duration(milliseconds: 50), (timer) {
       if (!mounted) {
         print('DEBUG: Stopping higher package jobs auto-scroll - not mounted');
         timer.cancel();
         _higherPackageJobsScrollTimer = null;
         return;
       }
       
       if (_higherPackageJobsScrollController.hasClients) {
         final maxScroll = _higherPackageJobsScrollController.position.maxScrollExtent;
         final currentScroll = _higherPackageJobsScrollController.position.pixels;
         
         // Continuous right-to-left scrolling
         if (currentScroll >= maxScroll) {
           // Reset to beginning when reaching the end for seamless loop
           _higherPackageJobsScrollController.jumpTo(0);
         } else {
           // Smooth continuous scrolling to the left
           _higherPackageJobsScrollController.jumpTo(currentScroll + 1.0);
         }
       } else {
         print('DEBUG: Higher package jobs scroll controller not ready');
       }
     });
   }

   void _startLocalJobsAutoScroll() {
     _localJobsScrollTimer?.cancel(); // Cancel existing timer if any
     
     print('DEBUG: Starting local jobs auto-scroll');
     _localJobsScrollTimer = Timer.periodic(Duration(milliseconds: 50), (timer) {
       if (!mounted) {
         print('DEBUG: Stopping local jobs auto-scroll - not mounted');
         timer.cancel();
         _localJobsScrollTimer = null;
         return;
       }
       
       if (_localJobsScrollController.hasClients) {
         final maxScroll = _localJobsScrollController.position.maxScrollExtent;
         final currentScroll = _localJobsScrollController.position.pixels;
         
         // Continuous right-to-left scrolling
         if (currentScroll >= maxScroll) {
           // Reset to beginning when reaching the end for seamless loop
           _localJobsScrollController.jumpTo(0);
           return;
         } else {
           // Smooth continuous scrolling to the left
           _localJobsScrollController.jumpTo(currentScroll + 1.0);
         }
       } else {
         print('DEBUG: Local jobs scroll controller not ready');
       }
     });
   }

   void _startSuccessfulCandidatesAutoScroll() {
     _successfulCandidatesScrollTimer?.cancel(); // Cancel existing timer if any
     
     print('DEBUG: Starting successful candidates auto-scroll');
     _successfulCandidatesScrollTimer = Timer.periodic(Duration(milliseconds: 50), (timer) {
       if (!mounted) {
         print('DEBUG: Stopping successful candidates auto-scroll - not mounted');
         timer.cancel();
         _successfulCandidatesScrollTimer = null;
         return;
       }
       
       if (_successfulCandidatesScrollController.hasClients) {
         final maxScroll = _successfulCandidatesScrollController.position.maxScrollExtent;
         final currentScroll = _successfulCandidatesScrollController.position.pixels;
         
         // Continuous right-to-left scrolling
         if (currentScroll >= maxScroll) {
           // Reset to beginning when reaching the end for seamless loop
           _successfulCandidatesScrollController.jumpTo(0);
         } else {
           // Smooth continuous scrolling to the left
           _successfulCandidatesScrollController.jumpTo(currentScroll + 1.0);
         }
       } else {
         print('DEBUG: Successful candidates scroll controller not ready');
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
    
    // Also restart successful candidates auto-scroll
    Future.delayed(Duration(milliseconds: 500), () {
      if (mounted && successfulCandidates.length > 1) {
        print('DEBUG: Restarting successful candidates auto-scroll after delay');
        _startSuccessfulCandidatesAutoScroll();
      } else {
        print('DEBUG: Not restarting successful candidates auto-scroll - mounted: $mounted, candidates: ${successfulCandidates.length}');
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

  void _pauseAllAutoScroll() {
    print('DEBUG: Pausing all auto-scroll');
    _pauseAutoScroll();
    _companyLogosScrollTimer?.cancel();
    _companyLogosScrollTimer = null;
    _successfulCandidatesScrollTimer?.cancel();
    _successfulCandidatesScrollTimer = null;
  }

  void _resumeAllAutoScroll() {
    print('DEBUG: Resuming all auto-scroll');
    _resumeAutoScroll();
    if (_companyLogosScrollTimer == null) {
      _startCompanyLogosAutoScroll();
    }
    if (_successfulCandidatesScrollTimer == null) {
      _startSuccessfulCandidatesAutoScroll();
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

     Widget _buildCompanyLogosSection() {
     // List of company logo file names (excluding b1.jpg and logo.jpg)
     final List<String> companyLogos = [
       'adani.jpg',
       'wipro.jpg', 
       'tcs.jpg',
       'info.jpg',
       'Hero.jpg',
       'airtel.jpg',
       'apollo.jpg',
       'assian.jpg',
       'Bajaj.jpg',

       'batra.jpg',
       'britannia.jpg',
       'bsnl.jpg',

       'byjus.jpg',

       'cadbury.jpg',
       'cap.jpg',
       'captial.jpg',
       'colf.jpg',
       'dabur.jpg',
       'fiat.jpg',
       'honda.jpg',
       'ibm.jpg',
       'itc.jpg',
       'jio.jpg',
       'lg.jpg',
       'LIFE.jpg',
       'lux.jpg',
       'mahindra.jpg',
       'MRF.jpg',
       'newholland.jpg',
       'ola.jpg',
       'reliance.jpg',
       'santoor.jpg',
       'serco.png',
       'skybags.png',
       'swiggy.jpg',
       'tata.jpg',
       'tatagreen.jpg',
       'tech.jpg',
       'uber.jpg',
       'Videocon.jpg',
       'Wheels.jpg',
       'wipro.jpg',
       'zomato.jpg',
     ];

     return ListView.builder(
       controller: _companyLogosScrollController,
       scrollDirection: Axis.horizontal,
       physics: NeverScrollableScrollPhysics(), // Disable manual scrolling for auto-scroll effect
       itemCount: companyLogos.length * 3, // Triple the items for seamless loop
       itemBuilder: (context, index) {
         final logoIndex = index % companyLogos.length;
         final logoPath = companyLogos[logoIndex];
         
         return Container(
           width: 120,
           margin: EdgeInsets.only(right: 15),
           decoration: BoxDecoration(
             color: Colors.white.withOpacity(0.9),
             borderRadius: BorderRadius.circular(12),
             boxShadow: [
               BoxShadow(
                 color: Colors.black.withOpacity(0.1),
                 blurRadius: 8,
                 offset: Offset(0, 3),
               ),
             ],
           ),
           child: Padding(
             padding: EdgeInsets.all(12),
             child: Image.asset(
               'assets/icon/$logoPath',
               fit: BoxFit.contain,
               errorBuilder: (context, error, stackTrace) {
                 return Container(
                   decoration: BoxDecoration(
                     color: Colors.grey[200],
                     borderRadius: BorderRadius.circular(8),
                   ),
                   child: Icon(
                     Icons.business,
                     color: Colors.grey[600],
                     size: 30,
                   ),
                 );
               },
             ),
           ),
         );
       },
     );
   }

   Widget _buildSuccessfulCandidateCard(SuccessfulCandidate candidate) {
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
            // Profile Avatar and Company Logo Row
            Row(
              children: [
                // Profile Avatar (Circle)
                Container(
                  width: 30,
                  height: 30,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.grey[300]!, width: 2),
                  ),
                  child: ClipOval(
                    child: Container(
                      color: const Color(0xFF6A11CB),
                      child: Center(
                        child: Text(
                          candidate.candidateName.isNotEmpty 
                              ? candidate.candidateName[0].toUpperCase()
                              : '?',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                SizedBox(width: 4),
                // Company Logo
                Container(
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: const Color(0xFF2575FC),
                    borderRadius: BorderRadius.circular(3),
                  ),
                  child: Center(
                    child: Text(
                      candidate.companyName.isNotEmpty 
                          ? candidate.companyName[0].toUpperCase()
                          : '?',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 8,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
                SizedBox(width: 3),
                // Company Name
                Expanded(
                  child: Text(
                    candidate.companyName,
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
            // Candidate Name
            Text(
              candidate.candidateName,
              style: GoogleFonts.poppins(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: Colors.black87,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 2),
            // Job Location
            Text(
              candidate.jobLocation,
              style: GoogleFonts.poppins(
                fontSize: 8,
                color: Colors.grey[600],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 2),
            // Salary
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 9),
                SizedBox(width: 1),
                Text(
                  candidate.salary,
                  style: GoogleFonts.poppins(
                    fontSize: 9,
                    fontWeight: FontWeight.bold,
                    color: Colors.green[600],
                  ),
                ),
              ],
            ),
            SizedBox(height: 2),
            // Success Badge
            Container(
              padding: EdgeInsets.symmetric(horizontal: 2, vertical: 1),
              decoration: BoxDecoration(
                color: Colors.green[50],
                borderRadius: BorderRadius.circular(3),
                border: Border.all(
                  color: Colors.green[300]!,
                ),
              ),
              child: Text(
                '✅ Placed',
                style: GoogleFonts.poppins(
                  fontSize: 6,
                  fontWeight: FontWeight.w600,
                  color: Colors.green[700],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Job _convertNewJobToJob(NewJob newJob) {
    return Job(
      id: newJob.id,
      companyName: 'Company',
      companyLogoUrl: '',
      studentName: '',
      district: '',
      package: newJob.salary,
      profile: newJob.jobPost,
      jobTitle: newJob.jobPost,
      location: '',
      jobType: 'full_time',
      experienceLevel: newJob.education,
      skillsRequired: [],
      jobDescription: 'Education: ${newJob.education}',
      createdAt: DateTime.now(),
    );
  }

  Widget _buildNewJobCard(NewJob job) {
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
            // Job Type Badge
            Container(
              padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: job.isHigherJob ? Colors.blue[50] : Colors.green[50],
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: job.isHigherJob ? Colors.blue[300]! : Colors.green[300]!,
                ),
              ),
              child: Text(
                job.isHigherJob ? 'Higher Job' : 'Local Job',
                style: TextStyle(
                  color: job.isHigherJob ? Colors.blue[700] : Colors.green[700],
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            SizedBox(height: 8),
            // Job Post
            Text(
              job.jobPost,
              style: GoogleFonts.poppins(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: 8),
            // Education
            Row(
              children: [
                Icon(Icons.school, color: Colors.blue[600], size: 14),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    job.education,
                    style: GoogleFonts.poppins(
                      fontSize: 10,
                      color: Colors.grey[600],
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            SizedBox(height: 8),
            // Salary
            Row(
              children: [
                Icon(Icons.currency_rupee, color: Colors.green[600], size: 14),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    job.salary,
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Colors.green[600],
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            Spacer(),
            // Apply Button or Status
            if (userJobApplications.containsKey(job.id))
              GestureDetector(
                onTap: () => _showJobStatusModal(_convertNewJobToJob(job)),
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getStatusColor(userJobApplications[job.id]!).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: _getStatusColor(userJobApplications[job.id]!).withOpacity(0.3),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        _getStatusIcon(userJobApplications[job.id]!),
                        size: 12,
                        color: _getStatusColor(userJobApplications[job.id]!),
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Status',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: _getStatusColor(userJobApplications[job.id]!),
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              Container(
                width: double.infinity,
                height: 32,
                child: ElevatedButton(
                  onPressed: () {
                    _showJobApplicationModal(_convertNewJobToJob(job));
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: job.isHigherJob ? Colors.blue : Colors.green,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: Text(
                    'Apply Now',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
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
                    color: _getStatusColor(userJobApplications[job.id]!).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: _getStatusColor(userJobApplications[job.id]!).withOpacity(0.3)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        _getStatusIcon(userJobApplications[job.id]!),
                        size: 12,
                        color: _getStatusColor(userJobApplications[job.id]!),
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Status',
                        style: GoogleFonts.poppins(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: _getStatusColor(userJobApplications[job.id]!),
                        ),
                      ),
                    ],
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
    // Check if user has already applied for this job
    bool hasApplied = userJobApplications.containsKey(job.id);
    String applicationStatus = userJobApplications[job.id] ?? '';
    
    // Debug logging for status button logic
    print('DEBUG: Building job card for Job ID: ${job.id}, Title: ${job.jobTitle}');
    print('DEBUG: Has applied: $hasApplied, Status: $applicationStatus');
    print('DEBUG: userJobApplications map: $userJobApplications');
    print('DEBUG: Current job ID being checked: ${job.id}');
    print('DEBUG: Keys in userJobApplications: ${userJobApplications.keys.toList()}');
    
    // Determine button color based on package amount (but don't show amount)
    Color buttonColor;
    
    // Extract numeric value from package string (e.g., "12LPA" -> 12)
    final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
    
    if (packageValue != null && packageValue >= 10) {
      // 10+ LPA jobs get orange button (higher package)
      buttonColor = Colors.orange;
    } else {
      // Below 10 LPA jobs get green button (local jobs)
      buttonColor = Colors.green;
    }
    
    // Determine status button color and text
    Color statusButtonColor = Colors.grey; // Default color
    String statusText = 'APPLIED'; // Default text
    
    if (hasApplied) {
      switch (applicationStatus.toLowerCase()) {
        case 'accepted':
          statusButtonColor = Colors.green;
          statusText = 'Status'; // Always show "Status" for clickable button
          break;
        case 'pending':
          statusButtonColor = Colors.orange;
          statusText = 'Status'; // Always show "Status" for clickable button
          break;
        case 'shortlisted':
          statusButtonColor = Colors.blue;
          statusText = 'Status'; // Always show "Status" for clickable button
          break;
        case 'rejected':
          statusButtonColor = Colors.red;
          statusText = 'Status'; // Always show "Status" for clickable button
          break;
        default:
          statusButtonColor = Colors.grey;
          statusText = 'Status'; // Always show "Status" for clickable button
      }
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
            // Apply Button or Status Button
            if (!hasApplied)
              // Show Apply Button if not applied
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
              )
            else
              // Show Status Button if already applied
              GestureDetector(
                onTap: () => _showApplicationStatus(job, applicationStatus),
                child: Container(
                  width: double.infinity,
                  padding: EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: statusButtonColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: statusButtonColor.withOpacity(0.3)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        _getStatusIcon(applicationStatus),
                        size: 14,
                        color: statusButtonColor,
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Status',
                        style: GoogleFonts.poppins(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: statusButtonColor,
                        ),
                      ),
                    ],
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
                        child: Row(
                          children: [
                            // // Test Payment Button
                            // Container(
                            //   margin: EdgeInsets.only(right: 10),
                            //   child: _buildAnimatedIconButton(
                            //     icon: Icons.payment,
                            //     onPressed: () {
                            //       print('DEBUG: Test payment button pressed');
                            //       _testPaymentGateway();
                            //     },
                            //   ),
                            // ),
                            // Profile Button
                            _buildAnimatedIconButton(
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
                          ],
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
                                _successfulCandidatesHeading,
                                style: GoogleFonts.poppins(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Row(
                                children: [
                                  Text(
                                    _successfulCandidatesSubHeading,
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
                            child: successfulCandidates.isNotEmpty 
                              ? GestureDetector(
                                  onPanStart: (_) {
                                    print('DEBUG: User interaction detected, pausing all auto-scroll');
                                    _pauseAllAutoScroll();
                                  },
                                  onPanEnd: (_) {
                                    print('DEBUG: User interaction ended, resuming all auto-scroll in 2 seconds');
                                    Future.delayed(Duration(seconds: 2), () {
                                      if (mounted) {
                                        print('DEBUG: Resuming all auto-scroll after user interaction');
                                        _resumeAllAutoScroll();
                                      }
                                    });
                                  },
                                  onTap: () {
                                    // Pause auto-scroll on tap and resume after delay
                                    _pauseAllAutoScroll();
                                    Future.delayed(Duration(seconds: 2), () {
                                      if (mounted) {
                                        _resumeAllAutoScroll();
                                      }
                                    });
                                  },
                                  child: ListView.builder(
                                    controller: _successfulCandidatesScrollController,
                                    scrollDirection: Axis.horizontal,
                                    physics: NeverScrollableScrollPhysics(), // Disable manual scrolling for marquee effect
                                    itemCount: successfulCandidates.length * 3, // Triple the items for seamless loop
                                    itemBuilder: (context, index) {
                                      final candidate = successfulCandidates[index % successfulCandidates.length];
                                      return Container(
                                        width: 180,
                                        margin: EdgeInsets.only(right: 15),
                                        child: _buildSuccessfulCandidateCard(candidate),
                                      );
                                    },
                                  ),
                                )
                              : Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.people_outline,
                                        color: Colors.white70,
                                        size: 28,
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        'No successful candidates yet',
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
                                    builder: (context) => SuccessfulCandidatesScreen(),
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
                            child: newHigherPackageJobs.isNotEmpty 
                              ? ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(), // Enable manual scrolling
                                  itemCount: newHigherPackageJobs.length,
                                  itemBuilder: (context, index) {
                                    final job = newHigherPackageJobs[index];
                                    return Container(
                                      width: 180,
                                      margin: EdgeInsets.only(right: 15),
                                      child: _buildNewJobCard(job),
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
                                      jobs: newHigherPackageJobs.map((job) => _convertNewJobToJob(job)).toList(),
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
                            child: newLocalJobs.isNotEmpty 
                              ? ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(), // Enable manual scrolling
                                  itemCount: newLocalJobs.length,
                                  itemBuilder: (context, index) {
                                    final job = newLocalJobs[index];
                                    return Container(
                                      width: 180,
                                      margin: EdgeInsets.only(right: 15),
                                      child: _buildNewJobCard(job),
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
                                     builder: (context) => AllJobsPage(
                                       jobs: newLocalJobs.map((job) => _convertNewJobToJob(job)).toList(),
                                       title: 'Local Jobs',
                                       jobType: 'local_job',
                                     ),
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
                           
                           // Company Logos Section (New Section)
                           SizedBox(height: 20),
                           Text(
                             'Our Partner Companies',
                             style: GoogleFonts.poppins(
                               color: Colors.white,
                               fontSize: 20,
                               fontWeight: FontWeight.bold,
                             ),
                           ),
                           SizedBox(height: 12),
                           Container(
                             height: 80,
                             child: _buildCompanyLogosSection(),
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
      amountText = '₹2000.0';
      amount = 2000.0;
    } else {
      instructions = '''
1. Play Smart services only works in company job requirements.

2. Play Smart services working  All Over India.

3. We provide Job for  candidates on local Place  or  elsewhere

4. We provide job opportunities for candidates according to their education.

5. We provide  2 to 3 Interview calls within Month for candidates.

6. We provide you  job opportunities That means we provide you a Service  The registration fee for    them is 1000.

7. Rs.1000 Registration charges Will be limited for one year.

8. The fee of Rs. 1000 is non-refundable.

9. If all the above are acceptable then  register today. The company will contact you today for a job    according to your education and provide you with further information.
      ''';
      amountText = '1000.00';
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
      // Get user token from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? prefs.getString('userToken') ?? prefs.getString('authToken');
      
      if (token == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Please login to submit application'),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }

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

      // Determine if this is a new job (from new_jobs table) or old job
      bool isNewJob = job.companyName.isEmpty || job.companyName == 'Company';
      String jobType = 'higher_job'; // Default to higher_job
      
      // If it's a new job, determine the type based on the job data
      if (isNewJob) {
        // Check if this job exists in our new jobs list
        NewJob? newJob = newJobs.firstWhere(
          (nj) => nj.id == job.id,
          orElse: () => NewJob(
            id: job.id,
            jobPost: job.jobTitle,
            salary: job.package,
            education: 'Not specified',
            jobType: 'higher_job',
            createdAt: DateTime.now().toString(),
          ),
        );
        jobType = newJob.jobType;
      }

      // Prepare data for submission
      final data = {
        'job_id': job.id,
        'job_type': jobType,
        'student_name': formData['name'] ?? '',
        'email': formData['email'] ?? '',
        'phone': formData['phone'] ?? '',
        'experience': formData['experience'] ?? '',
        'skills': formData['skills'] ?? '',
        'referral_code': referralCode.isNotEmpty ? referralCode : '',
        'district': 'Mumbai', // Default location
      };

      print('DEBUG: Submitting application data: $data');

      // Send to backend to store in database with authorization token
      final response = await http.post(
        Uri.parse('https://playsmart.co.in/submit_job_application_new.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
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
          final jobType = responseData['data']['job_type'];
          
          print('DEBUG: Application submitted successfully. ID: $applicationId, Job Type: $jobType');
          
          // Store the application ID for payment tracking
          _currentApplicationId = applicationId;
          
          // Upload files if they were selected
          if (photoPath != null && resumePath != null) {
            await _uploadFilesToServer(applicationId, photoPath, resumePath);
          }
          
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

  // Add this new method to upload files
  Future<void> _uploadFilesToServer(int applicationId, String photoPath, String resumePath) async {
    try {
      // Upload photo
      if (photoPath.isNotEmpty) {
        final photoFile = File(photoPath);
        if (await photoFile.exists()) {
          final photoBytes = await photoFile.readAsBytes();
          final photoName = 'photo_${applicationId}_${DateTime.now().millisecondsSinceEpoch}.jpg';
          
          // Save photo to server
          final photoResponse = await http.post(
            Uri.parse('https://playsmart.co.in/Admin/uploads/photos/'),
            headers: {'Content-Type': 'application/octet-stream'},
            body: photoBytes,
          );
          
          if (photoResponse.statusCode == 200) {
            print('DEBUG: Photo uploaded successfully');
          }
        }
      }
      
      // Upload resume
      if (resumePath.isNotEmpty) {
        final resumeFile = File(resumePath);
        if (await resumeFile.exists()) {
          final resumeBytes = await resumeFile.readAsBytes();
          final resumeName = 'resume_${applicationId}_${DateTime.now().millisecondsSinceEpoch}.pdf';
          
          // Save resume to server
          final resumeResponse = await http.post(
            Uri.parse('https://playsmart.co.in/Admin/uploads/resumes/'),
            headers: {'Content-Type': 'application/octet-stream'},
            body: resumeBytes,
          );
          
          if (resumeResponse.statusCode == 200) {
            print('DEBUG: Resume uploaded successfully');
          }
        }
      }
      
      // Update file paths in database
      final updateResponse = await http.post(
        Uri.parse('https://playsmart.co.in/upload_files_new.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'application_id': applicationId,
          'photo_path': 'Admin/uploads/photos/photo_${applicationId}_${DateTime.now().millisecondsSinceEpoch}.jpg',
          'resume_path': 'Admin/uploads/resumes/resume_${applicationId}_${DateTime.now().millisecondsSinceEpoch}.pdf',
        }),
      );
      
      if (updateResponse.statusCode == 200) {
        print('DEBUG: File paths updated in database');
      }
      
    } catch (e) {
      print('DEBUG: Error uploading files: $e');
    }
  }

  void _openPaymentGateway(Job job, double amount, String referralCode) async {
    try {
      print('DEBUG: _openPaymentGateway called with amount: $amount');
      
      // Get user token from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? prefs.getString('userToken') ?? prefs.getString('authToken');
      
      if (token == null) {
        throw Exception('Please login to proceed with payment');
      }
      
      // FIRST: Create Razorpay order on your backend
      final orderResponse = await http.post(
        Uri.parse('https://playsmart.co.in/create_razorpay_order.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'amount': (amount * 100).toInt(), // Amount in paise
          'currency': 'INR',
          'job_id': job.id,
          'job_title': job.jobTitle,
          'user_email': '', // Get from SharedPreferences if available
        }),
      ).timeout(Duration(seconds: 15));

      print('DEBUG: Order response status: ${orderResponse.statusCode}');
      print('DEBUG: Order response body: ${orderResponse.body}');
      print('DEBUG: Order response headers: ${orderResponse.headers}');

      if (orderResponse.statusCode != 200) {
        throw Exception('Failed to create payment order. Status: ${orderResponse.statusCode}, Body: ${orderResponse.body}');
      }

      // Check if response is valid JSON
      if (orderResponse.body.trim().isEmpty) {
        throw Exception('Empty response from server');
      }

      // Check if response starts with HTML (error page)
      if (orderResponse.body.trim().startsWith('<')) {
        throw Exception('Server returned HTML instead of JSON. This usually means a PHP error occurred. Response: ${orderResponse.body.substring(0, 200)}...');
      }

      Map<String, dynamic> orderData;
      try {
        orderData = jsonDecode(orderResponse.body);
      } catch (e) {
        print('ERROR: Failed to parse JSON response: $e');
        print('ERROR: Response body: ${orderResponse.body}');
        throw Exception('Invalid JSON response from server: ${orderResponse.body.substring(0, 200)}...');
      }
      if (!orderData['success']) {
        throw Exception('Order creation failed: ${orderData['message']}');
      }

      final orderId = orderData['order_id'] ?? orderData['data']?['order_id'];
      if (orderId == null) {
        throw Exception('Order ID not found in response. Response: ${jsonEncode(orderData)}');
      }
      print('DEBUG: Created Razorpay order: $orderId');
      
      // Create payment options with the order ID
      var options = {
        'key': 'rzp_live_fgQr0ACWFbL4pN',
        'amount': (amount * 100).toInt(), // Amount in paise
        'currency': 'INR',
        'name': 'PlaySmart Services',
        'description': 'Job Application Fee for ${job.jobTitle}',
        'order_id': orderId, // CRITICAL: Include the order ID
        'prefill': {
          'contact': '',
          'email': '',
        },
        'external': {
          'wallets': ['paytm']
        }
      };
      
      print('DEBUG: Payment options created: ${jsonEncode(options)}');
      
      // Create a new Razorpay instance
      final razorpay = Razorpay();
      
      // Set up event handlers
      razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, (PaymentSuccessResponse response) async {
        print('DEBUG: Payment success: ${response.paymentId}');
        await _handlePaymentSuccessWithCapture(response, job, amount, referralCode);
        razorpay.clear();
      });
      
      razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, (PaymentFailureResponse response) {
        print('DEBUG: Payment error: ${response.message}');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment failed: ${response.message}'),
            backgroundColor: Colors.red,
          ),
        );
        razorpay.clear();
      });
      
      razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, (ExternalWalletResponse response) {
        print('DEBUG: External wallet: ${response.walletName}');
        razorpay.clear();
      });
      
      print('DEBUG: Attempting to open Razorpay...');
      razorpay.open(options);
      print('DEBUG: Razorpay opened successfully');
      
    } catch (e) {
      print('ERROR: Error in _openPaymentGateway: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening payment gateway: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  // Flag to prevent login redirect after payment
  bool _paymentJustCompleted = false;
  
  // New method to handle payment success with capture
  Future<void> _handlePaymentSuccessWithCapture(
    PaymentSuccessResponse response, 
    Job job, 
    double amount, 
    String referralCode
  ) async {
    try {
      print('DEBUG: Processing payment success with capture...');
      
      // Get user token from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? prefs.getString('userToken') ?? prefs.getString('authToken');
      
      if (token == null) {
        print('DEBUG: No token found for payment capture');
        return;
      }
      
      // Capture the payment on your backend
      final captureResponse = await http.post(
        Uri.parse('https://playsmart.co.in/capture_payment_clean.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'payment_id': response.paymentId,
          'order_id': response.orderId,
          'signature': response.signature,
          'job_id': job.id,
          'amount': amount,
          'referral_code': referralCode,
        }),
      ).timeout(Duration(seconds: 15));

      if (captureResponse.statusCode == 200) {
        final captureData = jsonDecode(captureResponse.body);
        if (captureData['success']) {
          print('DEBUG: Payment captured successfully');
          
          // Set flag to prevent login redirect
          _paymentJustCompleted = true;
          
          // Show success message
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ Payment successful! Payment ID: ${response.paymentId}'),
              backgroundColor: Colors.green,
              duration: Duration(seconds: 3),
            ),
          );
          
          // Close any open modals
          Navigator.of(context).pop();
          
          // Update local application status and refresh data
          setState(() {
            // Update the job application status in the map to show status button
            userJobApplications[job.id] = 'pending';
          });
          

          
          // Refresh job applications to show updated status
          await fetchJobApplications();
          
          // Also refresh jobs to update the UI
          await fetchJobs();
          
          // Show success dialog
          _showPaymentSuccessDialog(job, response.paymentId!);
          

          
        } else {
          print('DEBUG: Payment capture failed: ${captureData['message']}');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Payment capture failed: ${captureData['message']}'),
              backgroundColor: Colors.red,
              duration: Duration(seconds: 5),
            ),
          );
        }
      } else {
        print('DEBUG: Payment capture HTTP error: ${captureResponse.statusCode}');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Payment capture failed. Please contact support.'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 5),
          ),
        );
      }
    } catch (e) {
      print('ERROR: Error capturing payment: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Error processing payment: $e'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 5),
        ),
      );
    }
  }
  

  
  // Show payment success dialog
  void _showPaymentSuccessDialog(Job job, String paymentId) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green, size: 30),
              SizedBox(width: 10),
              Text('Payment Successful!'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Your job application has been submitted successfully!'),
              SizedBox(height: 10),
              Text('Job: ${job.jobTitle}'),
              Text('Payment ID: $paymentId'),
              SizedBox(height: 10),
              Text(
                'Your application is now being processed. You will receive updates via email.',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                // Only close the dialog, don't close the main screen
                Navigator.of(context).pop();
                
                // Refresh the main screen data to show updated status
                if (mounted) {
                  setState(() {});
                }
                
                // Force refresh to show status button
                Future.delayed(Duration(milliseconds: 100), () {
                  if (mounted) {
                    fetchJobApplications();
                    fetchJobs();
                  }
                });
              },
              child: Text('OK'),
            ),
          ],
        );
      },
    );
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
                      child: _buildSimpleJobCard(job),
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

  Widget _buildSimpleJobCard(Job job) {
    // Determine button color based on package amount
    Color buttonColor;
    final packageValue = double.tryParse(job.package.replaceAll(RegExp(r'[^\d.]'), ''));
    
    if (packageValue != null && packageValue >= 10) {
      buttonColor = Colors.orange; // Higher package jobs (10LPA+)
    } else {
      buttonColor = Colors.green; // Local jobs (below 10LPA)
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
            Container(
              width: double.infinity,
              padding: EdgeInsets.symmetric(vertical: 8),
              decoration: BoxDecoration(
                color: buttonColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: buttonColor.withOpacity(0.3)),
              ),
              child: Text(
                'View Details',
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: buttonColor,
                ),
                textAlign: TextAlign.center,
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
    final registrationFee = isHighPackage ? '₹2000' : '₹2';
    
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
        // Store user email for payment processing
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_email', _emailController.text);
        print('DEBUG: User email stored: ${_emailController.text}');
        
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

4. **Payment Process**: Complete the payment of ₹0.2 to proceed with job matching.

5. **Job Matching**: After payment confirmation, we'll match you with suitable opportunities.

6. **Support**: Our team will guide you through the entire process.

**Referral Program**: If you used a referral code, the referrer will receive 20% commission.
      ''';
      amountText = '₹0.2';
      amount = 0.2;
    } else {
      instructions = '''
1. **Application Review**: Your application has been submitted and stored in our database.

2. **Document Verification**: Our team will verify your uploaded documents (photo and resume).
      amountText = '₹0.2';
      amount = 0.2;

4. **Payment Process**: Complete the payment of ₹0.1 to proceed with job placement.

5. **Job Placement**: After payment confirmation, we'll connect you with local employers.

6. **Support**: Our team will provide ongoing support throughout your job search.

**Referral Program**: If you used a referral code, the referrer will receive 20% commission on your registration fee.
      ''';
      amountText = '₹0.1';
      amount = 0.1;
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

  void _openPaymentGateway(Job job, double amount, String referralCode) async {
    try {
      print('DEBUG: _openPaymentGateway called with amount: $amount');
      
      // Get user token from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? prefs.getString('userToken') ?? prefs.getString('authToken');
      
      if (token == null) {
        throw Exception('Please login to proceed with payment');
      }
      
      // FIRST: Create Razorpay order on your backend
      final orderResponse = await http.post(
        Uri.parse('https://playsmart.co.in/create_razorpay_order.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'amount': (amount * 100).toInt(), // Amount in paise
          'currency': 'INR',
          'job_id': job.id,
          'job_title': job.jobTitle,
          'user_email': '', // Get from SharedPreferences if available
        }),
      ).timeout(Duration(seconds: 15));

      print('DEBUG: Order response status: ${orderResponse.statusCode}');
      print('DEBUG: Order response body: ${orderResponse.body}');
      print('DEBUG: Order response headers: ${orderResponse.headers}');

      if (orderResponse.statusCode != 200) {
        throw Exception('Failed to create payment order. Status: ${orderResponse.statusCode}, Body: ${orderResponse.body}');
      }

      // Check if response is valid JSON
      if (orderResponse.body.trim().isEmpty) {
        throw Exception('Empty response from server');
      }

      // Check if response starts with HTML (error page)
      if (orderResponse.body.trim().startsWith('<')) {
        throw Exception('Server returned HTML instead of JSON. This usually means a PHP error occurred. Response: ${orderResponse.body.substring(0, 200)}...');
      }

      Map<String, dynamic> orderData;
      try {
        orderData = jsonDecode(orderResponse.body);
      } catch (e) {
        print('ERROR: Failed to parse JSON response: $e');
        print('ERROR: Response body: ${orderResponse.body}');
        throw Exception('Invalid JSON response from server: ${orderResponse.body.substring(0, 200)}...');
      }
      if (!orderData['success']) {
        throw Exception('Order creation failed: ${orderData['message']}');
      }

      final orderId = orderData['order_id'] ?? orderData['data']?['order_id'];
      if (orderId == null) {
        throw Exception('Order ID not found in response. Response: ${jsonEncode(orderData)}');
      }
      print('DEBUG: Created Razorpay order: $orderId');
      
      // Create payment options with the order ID
      var options = {
        'key': 'rzp_live_fgQr0ACWFbL4pN',
        'amount': (amount * 100).toInt(), // Amount in paise
        'currency': 'INR',
        'name': 'PlaySmart Services',
        'description': 'Job Application Fee for ${job.jobTitle}',
        'order_id': orderId, // CRITICAL: Include the order ID
        'prefill': {
          'contact': '',
          'email': '',
        },
        'external': {
          'wallets': ['paytm']
        }
      };
      
      print('DEBUG: Payment options created: ${jsonEncode(options)}');
      
      // Create a new Razorpay instance
      final razorpay = Razorpay();
      
      // Set up event handlers
      razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, (PaymentSuccessResponse response) async {
        print('DEBUG: Payment success: ${response.paymentId}');
        Navigator.pop(context); // Close the modal
        
        // Handle payment success with capture
        await _handlePaymentSuccessWithCapture(response, job, amount, referralCode);
        
        // Clean up
        razorpay.clear();
      });
      
      razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, (PaymentFailureResponse response) {
        print('DEBUG: Payment error: ${response.message}');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment failed: ${response.message}'),
            backgroundColor: Colors.red,
          ),
        );
        // Clean up
        razorpay.clear();
      });
      
      razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, (ExternalWalletResponse response) {
        print('DEBUG: External wallet: ${response.walletName}');
        // Clean up
        razorpay.clear();
      });
      
      print('DEBUG: Attempting to open Razorpay...');
      razorpay.open(options);
      print('DEBUG: Razorpay opened successfully');
      
    } catch (e) {
      print('ERROR: Error in _openPaymentGateway: $e');
      print('ERROR: Full error details: ${e.toString()}');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error opening payment gateway: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
  
  // Handle payment success with capture for job application form
  Future<void> _handlePaymentSuccessWithCapture(
    PaymentSuccessResponse response, 
    Job job, 
    double amount, 
    String referralCode
  ) async {
    try {
      print('DEBUG: Processing payment success with capture from form...');
      
      // Get user token from SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? prefs.getString('userToken') ?? prefs.getString('authToken');
      
      if (token == null) {
        print('DEBUG: No token found for payment capture');
        return;
      }
      
      // Capture the payment on your backend
      final captureResponse = await http.post(
        Uri.parse('https://playsmart.co.in/capture_payment_clean.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'payment_id': response.paymentId,
          'order_id': response.orderId,
          'signature': response.signature,
          'job_id': job.id,
          'amount': amount,
          'referral_code': referralCode,
        }),
      ).timeout(Duration(seconds: 15));

      if (captureResponse.statusCode == 200) {
        final captureData = jsonDecode(captureResponse.body);
        if (captureData['success']) {
          print('DEBUG: Payment captured successfully from form');
          
          // Show success message
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ Payment successful! Payment ID: ${response.paymentId}'),
              backgroundColor: Colors.green,
              duration: Duration(seconds: 3),
            ),
          );
          
          // Show success dialog
          _showPaymentSuccessDialog(job, response.paymentId!);
          
        } else {
          print('DEBUG: Payment capture failed from form: ${captureData['message']}');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Payment capture failed: ${captureData['message']}'),
              backgroundColor: Colors.red,
              duration: Duration(seconds: 5),
            ),
          );
        }
      } else {
        print('DEBUG: Payment capture HTTP error from form: ${captureResponse.statusCode}');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Payment capture failed. Please contact support.'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 5),
          ),
        );
      }
    } catch (e) {
      print('ERROR: Error capturing payment from form: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Error processing payment: $e'),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 5),
        ),
      );
    }
  }
  
  // Show payment success dialog for job application form
  void _showPaymentSuccessDialog(Job job, String paymentId) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green, size: 30),
              SizedBox(width: 10),
              Text('Payment Successful!'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Your job application has been submitted successfully!'),
              SizedBox(height: 10),
              Text('Job: ${job.jobTitle}'),
              Text('Payment ID: $paymentId'),
              SizedBox(height: 10),
              Text(
                'Your application is now being processed. You will receive updates via email.',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                // Only close the dialog, don't close the form
                Navigator.of(context).pop();
                
                // Close the form and go back to main screen
                Navigator.of(context).pop();
                
                // Force refresh to show status button
                Future.delayed(Duration(milliseconds: 100), () {
                  if (mounted) {
                    // This will refresh the main screen data
                    setState(() {});
                  }
                });
              },
              child: Text('OK'),
            ),
          ],
        );
      },
    );
  }
}