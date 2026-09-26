class Campaign {
  final int id;
  final String title;
  final String description;
  final String? imageUrl;
  final int pricePerVote;
  final String status;
  final String? organizerName;
  final int candidateCount;
  final int totalVotes;
  final String? categories;

  Campaign({
    required this.id,
    required this.title,
    required this.description,
    this.imageUrl,
    required this.pricePerVote,
    required this.status,
    this.organizerName,
    required this.candidateCount,
    required this.totalVotes,
    this.categories,
  });

  factory Campaign.fromJson(Map<String, dynamic> json) {
    return Campaign(
      id: int.tryParse(json['campaign_id']?.toString() ?? '0') ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      imageUrl: json['image_url'],
      pricePerVote: int.tryParse(json['price_per_vote']?.toString() ?? '100') ?? 100,
      status: json['status'] ?? (json['is_draft'] == 1 ? 'draft' : 'active'),
      organizerName: json['organizer_name'],
      candidateCount: int.tryParse(json['candidate_count']?.toString() ?? '0') ?? 0,
      totalVotes: int.tryParse(json['totalVotes']?.toString() ?? '0') ?? 0,
      categories: json['categories'],
    );
  }
}

class CampaignDetail {
  final int id;
  final String title;
  final String description;
  final String? imageUrl;
  final int pricePerVote;
  final String status;
  final String? organizerName;
  final String? dateCloture;
  final int totalVotes;
  final int candidateCount;
  final List<CampaignCategory> categories;

  CampaignDetail({
    required this.id,
    required this.title,
    required this.description,
    this.imageUrl,
    required this.pricePerVote,
    required this.status,
    this.organizerName,
    this.dateCloture,
    required this.totalVotes,
    required this.candidateCount,
    required this.categories,
  });

  factory CampaignDetail.fromJson(Map<String, dynamic> json) {
    final categoriesList = (json['categories'] as List<dynamic>?)
            ?.map((c) => CampaignCategory.fromJson(c as Map<String, dynamic>))
            .toList() ??
        [];

    return CampaignDetail(
      id: int.tryParse(json['campaign_id']?.toString() ?? '0') ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      imageUrl: json['image_url'],
      pricePerVote: int.tryParse(json['price_per_vote']?.toString() ?? '100') ?? 100,
      status: json['computed_status'] ?? json['status'] ?? 'active',
      organizerName: json['organizer_name'],
      dateCloture: json['date_cloture'],
      totalVotes: int.tryParse(json['totalVotes']?.toString() ?? '0') ?? 0,
      candidateCount: int.tryParse(json['candidateCount']?.toString() ?? '0') ?? 0,
      categories: categoriesList,
    );
  }
}

class CampaignCategory {
  final int id;
  final String name;
  final List<Candidate> candidates;

  CampaignCategory({
    required this.id,
    required this.name,
    required this.candidates,
  });

  factory CampaignCategory.fromJson(Map<String, dynamic> json) {
    final candidatesList = (json['candidates'] as List<dynamic>?)
            ?.map((c) => Candidate.fromJson(c as Map<String, dynamic>))
            .toList() ??
        [];

    return CampaignCategory(
      id: int.tryParse(json['id']?.toString() ?? json['category_id']?.toString() ?? '0') ?? 0,
      name: json['name'] ?? '',
      candidates: candidatesList,
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
  final String? imageUrl;
  final int votes;
  final double percentage;

  Candidate({
    required this.id,
    required this.number,
    required this.name,
    this.age,
    this.theme,
    this.description,
    this.bio,
    this.imageUrl,
    required this.votes,
    required this.percentage,
  });

  factory Candidate.fromJson(Map<String, dynamic> json) {
    return Candidate(
      id: int.tryParse(json['id']?.toString() ?? json['candidate_id']?.toString() ?? '0') ?? 0,
      number: int.tryParse(json['number']?.toString() ?? json['candidate_number']?.toString() ?? '0') ?? 0,
      name: json['name'] ?? '',
      age: json['age'] != null ? int.tryParse(json['age'].toString()) : null,
      theme: json['theme'],
      description: json['description'],
      bio: json['bio'],
      imageUrl: json['image_url'],
      votes: int.tryParse(json['votes']?.toString() ?? '0') ?? 0,
      percentage: (json['percentage'] is num) ? (json['percentage'] as num).toDouble() : 0.0,
    );
  }
}