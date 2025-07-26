import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:playsmart/Auth/login_screen.dart';
import 'package:playsmart/Models/contest.dart';
import 'package:playsmart/controller/mega-contest-controller.dart';
import 'package:playsmart/controller/mini-contest-controller.dart';
import 'package:playsmart/profile_Screen.dart';
import 'package:playsmart/splash_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'quiz_screen.dart';
import 'mega_quiz_screen.dart';
import 'mega_result_screen.dart';

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
  double userBalance = 0.0;
  List<Contest> miniContests = [];
  List<Contest> megaContests = [];
  final ContestController _miniContestController = ContestController();
  final MegaContestController _megaContestController = MegaContestController();
  Timer? _refreshTimer;
  Map<int, Map<String, dynamic>> _megaContestStatus = {};

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
    _initializeAnimations();
    fetchUserBalance();
    fetchContests();
    _startRefreshTimer();
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

  @override
  void dispose() {
    _animationController.dispose();
    _floatingIconsController.dispose();
    _pulseController.dispose();
    _refreshTimer?.cancel();
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
          
          // Use server status directly
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
            // Fallback: Show contests that are within 2 hours of start time
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
          
          // Get contest start time
          final startDateTime = DateTime.tryParse(status['start_datetime'] ?? '') ?? contest.startDateTime ?? DateTime.now();
          final now = DateTime.now();
          final minutesUntilStart = startDateTime.difference(now).inMinutes;
          final minutesSinceStart = now.difference(startDateTime).inMinutes;
          
          // Show contests that are:
          // 1. Joinable (within join window)
          // 2. User has joined (regardless of time)
          // 3. User has participated (regardless of time)
          // 4. Within 30 minutes before start time (so users can see upcoming contests)
          // 5. Within 2 hours after start time (so users can see recent contests)
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
      print('DEBUG: Refresh timer triggered. Fetching user balance and contests...');
      await fetchUserBalance();
      await fetchContests();
    });
  }

  Future<String?> getMatchId(int contestId) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token == null) return null;
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

  // Show rankings popup when joining mega contest
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
      // If rankings fetch fails, join directly
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Insufficient balance to join contest')),
      );
      return;
    }
    
    try {
      await updateLastActivity();
      final joinData = contest.type == 'mega'
          ? await _megaContestController.joinMegaContest(contest.id, contest.entryFee)
          : await _miniContestController.joinContest(contest.id, contest.entryFee, contest.type);
      
      final String? matchId = joinData['match_id']?.toString();
      if (matchId == null || matchId.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: Match ID not received from server')),
        );
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error joining contest: $e')),
      );
    }
  }

  Future<void> startMegaContest(Contest contest) async {
    await updateLastActivity();
    final matchId = await getMatchId(contest.id);
    if (matchId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: Match ID not found')),
      );
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
          // Delay fetchContests to allow server to commit scorer
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error starting contest: ${result['message']}')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error starting contest: $e')),
      );
    }
  }

  Future<void> viewMegaResults(Contest contest) async {
    try {
      await updateLastActivity();
      final matchId = await getMatchId(contest.id);
      if (matchId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: Match ID not found')),
        );
        return;
      }
      
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      if (token == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: No token found')),
        );
        return;
      }
      
      final response = await http.get(
        Uri.parse('https://playsmart.co.in/mega/fetch_results.php?session_token=$token&contest_id=${contest.id}&match_id=$matchId'),
      ).timeout(Duration(seconds: 10));
      
      if (response.statusCode != 200) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error fetching results: HTTP ${response.statusCode}')),
        );
        return;
      }
      
      final resultData = jsonDecode(response.body);
      if (!resultData['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error fetching results: ${resultData['message']}')),
        );
        return;
      }
      
      // Parse numeric fields safely
      double? parseToDouble(dynamic value) {
        if (value == null) return null;
        if (value is num) return value.toDouble();
        if (value is String) return double.tryParse(value);
        return null;
      }
      
      // Navigate to MegaResultScreen and wait for result
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
      
      // Only set has_viewed_results if results were successfully viewed
      if (resultViewed == true) {
        setState(() {
          _megaContestStatus[contest.id]!['has_viewed_results'] = true;
          _megaContestStatus[contest.id]!['isWinner'] = resultData['is_winner'] ?? false;
          _megaContestStatus[contest.id]!['isTie'] = resultData['is_tie'] ?? false;
          _megaContestStatus[contest.id]!['opponentName'] = resultData['opponent_name'];
          _megaContestStatus[contest.id]!['opponentScore'] = parseToDouble(resultData['opponent_score']);
          print('DEBUG: Set has_viewed_results to true for contest ${contest.id}');
        });
        
        // Results viewed - no timer needed to remove contest
        print('DEBUG: Results viewed for contest ${contest.id}');
      }
      
      // Refresh contests to ensure consistent state
      Future.delayed(Duration(seconds: 1), () {
        if (mounted) {
          fetchContests();
        }
      });
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('ERROR: Failed to view results for contest ${contest.id}: $e')),
      );
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
                      Container(
                        padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.account_balance_wallet, color: Colors.amber, size: 20),
                            SizedBox(width: 5),
                            Text(
                              '₹${userBalance.toStringAsFixed(2)}',
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            SizedBox(width: 10),
                            IconButton(
                              icon: Icon(Icons.refresh, color: Colors.white, size: 20),
                              onPressed: fetchUserBalance,
                            ),
                          ],
                        ),
                      ),
                      FadeTransition(
                        opacity: _fadeAnimation,
                        child: _buildAnimatedIconButton(
                          icon: Icons.logout,
                          onPressed: () async {
                            final prefs = await SharedPreferences.getInstance();
                            await prefs.clear();
                            Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => LoginScreen()));
                          },
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 20),
                  FadeTransition(
                    opacity: _fadeAnimation,
                    child: SlideTransition(
                      position: _slideAnimation,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          AnimatedBuilder(
                            animation: _pulseController,
                            builder: (context, child) {
                              return Transform.scale(
                                scale: 1.0 + (_pulseController.value * 0.05),
                                child: ShaderMask(
                                  shaderCallback: (bounds) => LinearGradient(
                                    colors: [Colors.amber, Colors.yellow],
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                  ).createShader(bounds),
                                  child: Text(
                                    'Play Smart Services',
                                    style: GoogleFonts.poppins(
                                      fontSize: 32,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.white,
                                      shadows: [
                                        Shadow(
                                          color: Colors.black.withOpacity(0.3),
                                          offset: Offset(0, 3),
                                          blurRadius: 6,
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                          Text(
                            'Test your knowledge & win big!',
                            style: GoogleFonts.poppins(
                              color: Colors.white.withOpacity(0.8),
                              fontSize: 16,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  SizedBox(height: 30),
                  Expanded(
                    child: ListView(
                      physics: BouncingScrollPhysics(),
                      children: [
                        if (miniContests.isNotEmpty) ...[
                          Text(
                            'Mini Contests',
                            style: GoogleFonts.poppins(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 10),
                          ...miniContests.asMap().entries.map((entry) {
                            final index = entry.key;
                            final contest = entry.value;
                            return _buildContestCard(contest, index, isMega: false);
                          }),
                        ],
                        if (megaContests.isNotEmpty) ...[
                          SizedBox(height: 20),
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
                        if (miniContests.isEmpty && megaContests.isEmpty)
                          Center(child: CircularProgressIndicator(color: Colors.white)),
                      ],
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
    
    // isStartWindowOpen means the contest has started and user has joined but not submitted
    final bool isStartWindowOpen = isActive && !hasSubmitted;
    // canJoinMega: Mega contest is joinable, user hasn't joined, and there's still time to join (more than 1 minute until start)
    final bool canJoinMega = isMega && isJoinable && !hasJoined && minutesUntilStart > 1;
    // canStartMega: Mega contest has been joined, start time has been reached, and user hasn't submitted
    final bool canStartMega = isMega && hasJoined && isStartTimeReached && !hasSubmitted;
    // canViewResultsMega: Mega contest has been submitted, but results haven't been viewed
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
      // Check if we have status data, if not use fallback logic
      final hasStatusData = _megaContestStatus.containsKey(contest.id);
      
      if (!hasStatusData) {
        // Fallback logic when status is not available
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
          // User has joined but contest hasn't started yet
          buttonText = 'Waiting to Start (${minutesUntilStart}m)';
          buttonColor = Colors.orange;
          buttonEnabled = false;
        } else if (isStartTimeReached) {
          // User has joined and start time has been reached - show Start Now
          buttonText = 'Start Now';
          buttonColor = Colors.green;
          buttonEnabled = true;
        } else {
          // User has joined and contest should be active
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
        // Contest is within 2 hours of start time but not joinable (within 1 minute of start)
        buttonText = 'Joining Closed';
        buttonColor = Colors.grey.withOpacity(0.5);
        buttonEnabled = false;
      } else if (isMega && !hasJoined && minutesUntilStart > 1 && minutesUntilStart <= 30) {
        // Contest is within 30 minutes before start but not joinable (server says not joinable)
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
                      // Fallback logic when status is not available
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
                        // Display Total Winning Amount instead of Rankings
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
} 