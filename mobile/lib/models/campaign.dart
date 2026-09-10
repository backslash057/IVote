class Campaign {
  final int id;
  final String title;
  final String description;
  final String imageUrl;
  final String status;
  final String organizerName;
  final int candidateCount;
  final int totalVotes;

  Campaign({
    required this.id,
    required this.title,
    required this.description,
    required this.imageUrl,
    required this.status,
    required this.organizerName,
    required this.candidateCount,
    required this.totalVotes,
  });

  bool get isActive => status == 'active';
  bool get isScheduled => status == 'scheduled';

  factory Campaign.fromJson(Map<String, dynamic> json) {
    return Campaign(
      id: (json['campaign_id'] ?? json['id'] ?? 0) as int,
      title: json['title'] ?? '',
      description: json['description'] ?? 'Participez à cette campagne de vote.',
      imageUrl: json['image_url'] ??
          'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=1400&auto=format&fit=crop&q=80',
      status: json['status'] ?? '',
      organizerName: json['organizer_name'] ?? 'Organisateur IVote',
      candidateCount: (json['candidate_count'] ?? 0) as int,
      totalVotes: (json['totalVotes'] ?? json['total_votes'] ?? 0) as int,
    );
  }
}