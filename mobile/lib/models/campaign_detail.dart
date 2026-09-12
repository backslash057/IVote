class CampaignDetail {
  final int id;
  final String title;
  final String description;
  final String imageUrl;
  final String status;
  final String organizerName;
  final int pricePerVote;
  final bool isDraft;
  final String? dateCloture;
  final int totalVotes;
  final int candidateCount;
  final List<CampaignCategory> categories;

  const CampaignDetail({
    required this.id,
    required this.title,
    required this.description,
    required this.imageUrl,
    required this.status,
    required this.organizerName,
    required this.pricePerVote,
    required this.isDraft,
    required this.totalVotes,
    required this.candidateCount,
    required this.categories,
    this.dateCloture,
  });

  bool get isActive => status == 'active';

  static int _toInt(dynamic value, int fallback) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse('$value') ?? fallback;
  }

  static String _toStr(dynamic value, String fallback) =>
      value == null ? fallback : value.toString();

  factory CampaignDetail.fromJson(Map<String, dynamic> json) {
    final rawCategories = json['categories'];
    final categories = rawCategories is List
        ? rawCategories
            .whereType<Map<String, dynamic>>()
            .map(CampaignCategory.fromJson)
            .toList()
        : <CampaignCategory>[];

    return CampaignDetail(
      id: _toInt(json['campaign_id'] ?? json['id'], 0),
      title: _toStr(json['title'], ''),
      description: _toStr(json['description'], ''),
      imageUrl: _toStr(
        json['image_url'],
        'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80',
      ),
      status: _toStr(json['computed_status'] ?? json['status'], ''),
      organizerName: _toStr(json['organizer_name'], ''),
      pricePerVote: _toInt(json['price_per_vote'], 100),
      isDraft: _toInt(json['is_draft'], 0) == 1,
      dateCloture: json['date_cloture'] as String?,
      totalVotes: _toInt(json['totalVotes'], 0),
      candidateCount: _toInt(json['candidateCount'], categories.length),
      categories: categories,
    );
  }
}

class CampaignCategory {
  final int id;
  final String name;
  final List<Candidate> candidates;

  const CampaignCategory({
    required this.id,
    required this.name,
    required this.candidates,
  });

  factory CampaignCategory.fromJson(Map<String, dynamic> json) {
    final rawCandidates = json['candidates'];
    return CampaignCategory(
      id: CampaignDetail._toInt(json['id'] ?? json['category_id'], 0),
      name: CampaignDetail._toStr(json['name'], ''),
      candidates: (rawCandidates is List)
          ? rawCandidates
              .whereType<Map<String, dynamic>>()
              .map(Candidate.fromJson)
              .toList()
          : <Candidate>[],
    );
  }
}

class Candidate {
  final int id;
  final int number;
  final String name;
  final int? age;
  final String? theme;
  final String? description;
  final String? bio;
  final String imageUrl;
  final int votes;
  final double percentage;

  const Candidate({
    required this.id,
    required this.number,
    required this.name,
    required this.imageUrl,
    required this.votes,
    required this.percentage,
    this.age,
    this.theme,
    this.description,
    this.bio,
  });

  factory Candidate.fromJson(Map<String, dynamic> json) {
    final rawAge = json['age'];
    return Candidate(
      id: CampaignDetail._toInt(json['id'] ?? json['candidate_id'], 0),
      number: CampaignDetail._toInt(json['candidate_number'] ?? json['number'], 0),
      name: CampaignDetail._toStr(json['name'], ''),
      age: rawAge == null ? null : CampaignDetail._toInt(rawAge, 0),
      theme: json['theme'] as String?,
      description: json['description'] as String?,
      bio: json['bio'] as String?,
      imageUrl: CampaignDetail._toStr(
        json['image_url'],
        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&auto=format&fit=crop&q=80',
      ),
      votes: CampaignDetail._toInt(json['votes'], 0),
      percentage: (json['percentage'] is num)
          ? (json['percentage'] as num).toDouble()
          : double.tryParse('${json['percentage']}') ?? 0,
    );
  }
}