import 'package:flutter/material.dart';
import 'controller/local_jobs_controller.dart';
import 'Models/job.dart';

class LocalJobsScreen extends StatefulWidget {
  const LocalJobsScreen({Key? key}) : super(key: key);

  @override
  State<LocalJobsScreen> createState() => _LocalJobsScreenState();
}

class _LocalJobsScreenState extends State<LocalJobsScreen> {
  List<Job> _localJobs = [];
  bool _isLoading = false;
  String? _errorMessage;
  
  // Pagination
  int _currentPage = 1;
  bool _hasNextPage = false;
  bool _hasPrevPage = false;
  
  // Filters
  String? _selectedLocation;
  String? _selectedJobType;
  String? _selectedExperience;
  
  // Search
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  
  @override
  void initState() {
    super.initState();
    _loadLocalJobs();
  }
  
  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }
  
  Future<void> _loadLocalJobs({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _currentPage = 1;
        _localJobs.clear();
      });
    }
    
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    
    try {
      final result = await LocalJobsController.fetchLocalJobs(
        page: _currentPage,
        location: _selectedLocation,
        jobType: _selectedJobType,
        experience: _selectedExperience,
      );
      
      if (result['success']) {
        final List<Job> newJobs = result['jobs'];
        final pagination = result['pagination'];
        
        setState(() {
          if (refresh) {
            _localJobs = newJobs;
          } else {
            _localJobs.addAll(newJobs);
          }
          
          _hasNextPage = pagination['has_next'] ?? false;
          _hasPrevPage = pagination['has_prev'] ?? false;
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = result['message'];
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error loading jobs: $e';
        _isLoading = false;
      });
    }
  }
  
  Future<void> _searchJobs() async {
    if (_searchQuery.trim().isEmpty) {
      _loadLocalJobs(refresh: true);
      return;
    }
    
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _currentPage = 1;
      _localJobs.clear();
    });
    
    try {
      final result = await LocalJobsController.searchLocalJobs(
        query: _searchQuery.trim(),
        page: _currentPage,
        location: _selectedLocation,
        jobType: _selectedJobType,
        experience: _selectedExperience,
      );
      
      if (result['success']) {
        final List<Job> newJobs = result['jobs'];
        final pagination = result['pagination'];
        
        setState(() {
          _localJobs = newJobs;
          _hasNextPage = pagination['has_next'] ?? false;
          _hasPrevPage = pagination['has_prev'] ?? false;
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = result['message'];
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error searching jobs: $e';
        _isLoading = false;
      });
    }
  }
  
  void _loadNextPage() {
    if (_hasNextPage && !_isLoading) {
      setState(() {
        _currentPage++;
      });
      _loadLocalJobs();
    }
  }
  
  void _loadPrevPage() {
    if (_hasPrevPage && !_isLoading) {
      setState(() {
        _currentPage--;
      });
      _loadLocalJobs();
    }
  }
  
  void _showFiltersDialog() {
    showDialog(
      context: context,
      builder: (context) => _buildFiltersDialog(),
    );
  }
  
  Widget _buildFiltersDialog() {
    return AlertDialog(
      title: const Text('Filter Jobs'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Location Filter
            DropdownButtonFormField<String>(
              decoration: const InputDecoration(
                labelText: 'Location',
                border: OutlineInputBorder(),
              ),
              value: _selectedLocation,
              items: [
                const DropdownMenuItem(value: null, child: Text('All Locations')),
                ...LocalJobsController.getLocalPopularLocations()
                    .map((location) => DropdownMenuItem(
                          value: location,
                          child: Text(location),
                        ))
                    .toList(),
              ],
              onChanged: (value) {
                setState(() {
                  _selectedLocation = value;
                });
              },
            ),
            const SizedBox(height: 16),
            
            // Job Type Filter
            DropdownButtonFormField<String>(
              decoration: const InputDecoration(
                labelText: 'Job Type',
                border: OutlineInputBorder(),
              ),
              value: _selectedJobType,
              items: [
                const DropdownMenuItem(value: null, child: Text('All Job Types')),
                ...LocalJobsController.getLocalJobTypes()
                    .map((type) => DropdownMenuItem(
                          value: type,
                          child: Text(type),
                        ))
                    .toList(),
              ],
              onChanged: (value) {
                setState(() {
                  _selectedJobType = value;
                });
              },
            ),
            const SizedBox(height: 16),
            
            // Experience Filter
            DropdownButtonFormField<String>(
              decoration: const InputDecoration(
                labelText: 'Experience Level',
                border: OutlineInputBorder(),
              ),
              value: _selectedExperience,
              items: [
                const DropdownMenuItem(value: null, child: Text('All Experience Levels')),
                ...LocalJobsController.getLocalExperienceLevels()
                    .map((exp) => DropdownMenuItem(
                          value: exp,
                          child: Text(exp),
                        ))
                    .toList(),
              ],
              onChanged: (value) {
                setState(() {
                  _selectedExperience = value;
                });
              },
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () {
            Navigator.of(context).pop();
          },
          child: const Text('Cancel'),
        ),
        TextButton(
          onPressed: () {
            setState(() {
              _selectedLocation = null;
              _selectedJobType = null;
              _selectedExperience = null;
            });
          },
          child: const Text('Clear All'),
        ),
        ElevatedButton(
          onPressed: () {
            Navigator.of(context).pop();
            _loadLocalJobs(refresh: true);
          },
          child: const Text('Apply Filters'),
        ),
      ],
    );
  }
  
  Widget _buildJobCard(Job job) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Company Logo
                if (job.companyLogoUrl != null && job.companyLogoUrl!.isNotEmpty)
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        job.companyLogoUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) {
                          return Container(
                            color: Colors.grey.shade200,
                            child: const Icon(Icons.business, color: Colors.grey),
                          );
                        },
                      ),
                    ),
                  )
                else
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade200,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.business, color: Colors.grey),
                  ),
                
                const SizedBox(width: 12),
                
                // Job Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        job.jobTitle ?? 'Job Title',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        job.companyName ?? 'Company Name',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey.shade700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 16),
            
            // Package and Location
            Row(
              children: [
                Icon(Icons.attach_money, color: Colors.green.shade600, size: 20),
                const SizedBox(width: 8),
                Text(
                  job.package ?? 'Package not specified',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Colors.green.shade700,
                  ),
                ),
                const Spacer(),
                Icon(Icons.location_on, color: Colors.red.shade600, size: 20),
                const SizedBox(width: 8),
                Text(
                  job.location ?? 'Location not specified',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 12),
            
            // Job Type and Experience
            Row(
              children: [
                Icon(Icons.work, color: Colors.blue.shade600, size: 20),
                const SizedBox(width: 8),
                Text(
                  job.jobType ?? 'Job Type not specified',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                ),
                const Spacer(),
                Icon(Icons.timeline, color: Colors.orange.shade600, size: 20),
                const SizedBox(width: 8),
                Text(
                  job.experienceLevel ?? 'Experience not specified',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 16),
            
            // Skills
            if (job.skillsRequired != null && job.skillsRequired!.isNotEmpty)
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Skills Required:',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey.shade700,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: (job.skillsRequired is List
                            ? job.skillsRequired as List
                            : [job.skillsRequired.toString()])
                        .take(5)
                        .map((skill) => Chip(
                              label: Text(
                                skill.toString(),
                                style: const TextStyle(fontSize: 12),
                              ),
                              backgroundColor: Colors.blue.shade50,
                              side: BorderSide(color: Colors.blue.shade200),
                            ))
                        .toList(),
                  ),
                ],
              ),
            
            const SizedBox(height: 16),
            
            // Apply Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  // Navigate to job application screen
                  // You can implement this based on your app's navigation
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Apply for ${job.jobTitle ?? 'this job'}'),
                      duration: const Duration(seconds: 2),
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue.shade600,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: const Text(
                  'Apply Now',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Local Jobs'),
        backgroundColor: Colors.blue.shade600,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFiltersDialog,
            tooltip: 'Filter Jobs',
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Container(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Search local jobs...',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                    ),
                    onSubmitted: (value) {
                      _searchQuery = value;
                      _searchJobs();
                    },
                  ),
                ),
                const SizedBox(width: 12),
                ElevatedButton(
                  onPressed: _searchJobs,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue.shade600,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text('Search'),
                ),
              ],
            ),
          ),
          
          // Active Filters Display
          if (_selectedLocation != null || _selectedJobType != null || _selectedExperience != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Wrap(
                spacing: 8,
                runSpacing: 4,
                children: [
                  if (_selectedLocation != null)
                    Chip(
                      label: Text('Location: $_selectedLocation'),
                      onDeleted: () {
                        setState(() {
                          _selectedLocation = null;
                        });
                        _loadLocalJobs(refresh: true);
                      },
                    ),
                  if (_selectedJobType != null)
                    Chip(
                      label: Text('Type: $_selectedJobType'),
                      onDeleted: () {
                        setState(() {
                          _selectedJobType = null;
                        });
                        _loadLocalJobs(refresh: true);
                      },
                    ),
                  if (_selectedExperience != null)
                    Chip(
                      label: Text('Experience: $_selectedExperience'),
                      onDeleted: () {
                        setState(() {
                          _selectedExperience = null;
                        });
                        _loadLocalJobs(refresh: true);
                      },
                    ),
                ],
              ),
            ),
          
          // Content
          Expanded(
            child: _isLoading && _localJobs.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _errorMessage != null && _localJobs.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.error_outline,
                              size: 64,
                              color: Colors.grey.shade400,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _errorMessage!,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey.shade600,
                              ),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: () => _loadLocalJobs(refresh: true),
                              child: const Text('Retry'),
                            ),
                          ],
                        ),
                      )
                    : _localJobs.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.work_off,
                                  size: 64,
                                  color: Colors.grey.shade400,
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  'No local jobs found',
                                  style: TextStyle(
                                    fontSize: 18,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Try adjusting your filters or search terms',
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.grey.shade500,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: () => _loadLocalJobs(refresh: true),
                            child: ListView.builder(
                              itemCount: _localJobs.length + (_hasNextPage ? 1 : 0),
                              itemBuilder: (context, index) {
                                if (index == _localJobs.length) {
                                  // Load more button
                                  return Padding(
                                    padding: const EdgeInsets.all(16),
                                    child: Center(
                                      child: ElevatedButton(
                                        onPressed: _hasNextPage ? _loadNextPage : null,
                                        child: const Text('Load More Jobs'),
                                      ),
                                    ),
                                  );
                                }
                                
                                return _buildJobCard(_localJobs[index]);
                              },
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _loadLocalJobs(refresh: true),
        backgroundColor: Colors.blue.shade600,
        foregroundColor: Colors.white,
        child: const Icon(Icons.refresh),
        tooltip: 'Refresh Jobs',
      ),
    );
  }
} 