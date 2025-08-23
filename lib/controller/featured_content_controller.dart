import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:playsmart/Models/featured_content.dart';

class FeaturedContentController {
  static const String baseUrl = 'https://playsmart.co.in';

  // Fetch all active featured content
  static Future<List<FeaturedContent>> fetchFeaturedContent() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/fetch_featured_content.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return (data['data'] as List)
              .map((json) => FeaturedContent.fromJson(json))
              .toList();
        } else {
          throw Exception(data['message'] ?? 'Failed to fetch featured content');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error fetching featured content: $e');
      throw Exception('Failed to fetch featured content: $e');
    }
  }

  // Add new featured content (for admin use)
  static Future<Map<String, dynamic>> addFeaturedContent({
    required String title,
    required String description,
    required String imageUrl,
    required String actionText,
    required String actionUrl,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/add_featured_content.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'title': title,
          'description': description,
          'image_url': imageUrl,
          'action_text': actionText,
          'action_url': actionUrl,
        }),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to add featured content');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error adding featured content: $e');
      throw Exception('Failed to add featured content: $e');
    }
  }

  // Update existing featured content (for admin use)
  static Future<Map<String, dynamic>> updateFeaturedContent({
    required int id,
    String? title,
    String? description,
    String? imageUrl,
    String? actionText,
    String? actionUrl,
    bool? isActive,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/update_featured_content.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'id': id,
          if (title != null) 'title': title,
          if (description != null) 'description': description,
          if (imageUrl != null) 'image_url': imageUrl,
          if (actionText != null) 'action_text': actionText,
          if (actionUrl != null) 'action_url': actionUrl,
          if (isActive != null) 'is_active': isActive ? 1 : 0,
        }),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        } else {
          throw Exception(data['message'] ?? 'Failed to update featured content');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error updating featured content: $e');
      throw Exception('Failed to update featured content: $e');
    }
  }

  // Delete featured content (for admin use)
  static Future<bool> deleteFeaturedContent(int id) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/delete_featured_content.php'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'id': id}),
      ).timeout(Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      print('Error deleting featured content: $e');
      throw Exception('Failed to delete featured content: $e');
    }
  }
} 