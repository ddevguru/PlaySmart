class FeaturedContent {
  final int id;
  final String title;
  final String description;
  final String imageUrl;
  final String actionText;
  final String actionUrl;
  final bool isActive;
  final DateTime createdAt;

  FeaturedContent({
    required this.id,
    required this.title,
    required this.description,
    required this.imageUrl,
    required this.actionText,
    required this.actionUrl,
    required this.isActive,
    required this.createdAt,
  });

  factory FeaturedContent.fromJson(Map<String, dynamic> json) {
    return FeaturedContent(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      imageUrl: json['image_url'],
      actionText: json['action_text'],
      actionUrl: json['action_url'],
      isActive: json['is_active'] == 1,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'image_url': imageUrl,
      'action_text': actionText,
      'action_url': actionUrl,
      'is_active': isActive ? 1 : 0,
      'created_at': createdAt.toIso8601String(),
    };
  }
} 