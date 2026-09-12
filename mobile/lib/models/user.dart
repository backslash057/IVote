class AppUser {
  final int userId;
  final String name;
  final String email;

  const AppUser({
    required this.userId,
    required this.name,
    required this.email,
  });

  factory AppUser.fromJson(Map<String, dynamic> json) {
    return AppUser(
      userId: json['user_id'] is int
          ? json['user_id'] as int
          : int.tryParse('${json['user_id']}') ?? 0,
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
    );
  }
}

class VoteRecord {
  final String transactionRef;
  final int votesAdded;
  final int amountFcfa;

  const VoteRecord({
    required this.transactionRef,
    required this.votesAdded,
    required this.amountFcfa,
  });

  factory VoteRecord.fromJson(Map<String, dynamic> json) {
    int read(String key, int fallback) {
      final value = json[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      return int.tryParse('$value') ?? fallback;
    }

    return VoteRecord(
      transactionRef: (json['transaction_ref'] ?? '').toString(),
      votesAdded: read('votes_added', 0),
      amountFcfa: read('amount_fcfa', 0),
    );
  }
}