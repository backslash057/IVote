class Campaign {
  final int id;
  final String title;
  final String description;
  final String imageUrl;
  final String status;
  final String organizerName;
  final int candidateCount;
  final int totalVotes;
  final int pricePerVote;
  final String? dateCloture;
  final String? categories;

  const Campaign({
    required this.id,
    required this.title,
    required this.description,
    required this.imageUrl,
    required this.status,
    required this.organizerName,
    required this.candidateCount,
    required this.totalVotes,
    required this.pricePerVote,
    this.dateCloture,
    this.categories,
  });

  bool get isActive => status == 'active';
  bool get isDraft => status == 'draft';
  bool get isScheduled => status == 'scheduled';
  bool get isEnded => status == 'ended' || status == 'closed';

  static String _asString(dynamic value, String fallback) {
    if (value == null) return fallback;
    return value.toString();
  }

  static int _asInt(dynamic value, int fallback) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    final parsed = int.tryParse('$value');
    return parsed ?? fallback;
  }

  factory Campaign.fromJson(Map<String, dynamic> json) {
    return Campaign(
      id: _asInt(json['campaign_id'] ?? json['id'], 0),
      title: _asString(json['title'], ''),
      description: _asString(json['description'], 'Participez à cette campagne de vote.'),
      imageUrl: _asString(
        json['image_url'],
        'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80',
      ),
      status: _asString(json['status'], ''),
      organizerName: _asString(json['organizer_name'], 'Organisateur IVote'),
      candidateCount: _asInt(json['candidate_count'], 0),
      totalVotes: _asInt(json['totalVotes'] ?? json['total_votes'], 0),
      pricePerVote: _asInt(json['price_per_vote'], 100),
      dateCloture: json['date_cloture'] as String?,
      categories: json['categories'] as String?,
    );
  }
}