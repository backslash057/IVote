class DashboardData {
  final int totalVotes;
  final int totalRevenue;
  final int platformFee;
  final int availableBalance;
  final List<AdminCandidate> candidates;
  final List<TransactionRecord> transactions;
  final List<PayoutRequest> payoutRequests;

  const DashboardData({
    required this.totalVotes,
    required this.totalRevenue,
    required this.platformFee,
    required this.availableBalance,
    required this.candidates,
    required this.transactions,
    required this.payoutRequests,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final stats = json['stats'] as Map<String, dynamic>? ?? const {};
    final rawCandidates = json['candidates'];
    final rawTransactions = json['transactions'];
    final rawPayouts = json['payoutRequests'];

    int read(Map<String, dynamic> map, String key) {
      final value = map[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      return int.tryParse('$value') ?? 0;
    }

    return DashboardData(
      totalVotes: read(stats, 'totalVotes'),
      totalRevenue: read(stats, 'totalRevenue'),
      platformFee: read(stats, 'platformFee'),
      availableBalance: read(stats, 'availableBalance'),
      candidates: rawCandidates is List
          ? rawCandidates
              .whereType<Map<String, dynamic>>()
              .map(AdminCandidate.fromJson)
              .toList()
          : const [],
      transactions: rawTransactions is List
          ? rawTransactions
              .whereType<Map<String, dynamic>>()
              .map(TransactionRecord.fromJson)
              .toList()
          : const [],
      payoutRequests: rawPayouts is List
          ? rawPayouts
              .whereType<Map<String, dynamic>>()
              .map(PayoutRequest.fromJson)
              .toList()
          : const [],
    );
  }
}

class AdminCandidate {
  final int id;
  final String name;
  final int number;
  final String categoryName;
  final int votes;
  final int revenue;
  final double percentage;

  const AdminCandidate({
    required this.id,
    required this.name,
    required this.number,
    required this.categoryName,
    required this.votes,
    required this.revenue,
    required this.percentage,
  });

  factory AdminCandidate.fromJson(Map<String, dynamic> json) {
    int read(String key) {
      final value = json[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      return int.tryParse('$value') ?? 0;
    }

    return AdminCandidate(
      id: read('candidate_id'),
      name: (json['name'] ?? '').toString(),
      number: read('candidate_number'),
      categoryName: (json['category_name'] ?? '').toString(),
      votes: read('total_votes'),
      revenue: read('candidate_revenue'),
      percentage: (json['percentage'] is num)
          ? (json['percentage'] as num).toDouble()
          : double.tryParse('${json['percentage']}') ?? 0,
    );
  }
}

class TransactionRecord {
  final String transactionRef;
  final String candidateName;
  final String paymentMethod;
  final int voteCount;
  final int amount;
  final String createdAt;

  const TransactionRecord({
    required this.transactionRef,
    required this.candidateName,
    required this.paymentMethod,
    required this.voteCount,
    required this.amount,
    required this.createdAt,
  });

  factory TransactionRecord.fromJson(Map<String, dynamic> json) {
    int read(String key) {
      final value = json[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      return int.tryParse('$value') ?? 0;
    }

    return TransactionRecord(
      transactionRef: (json['transaction_ref'] ?? '').toString(),
      candidateName: (json['candidate_name'] ?? '').toString(),
      paymentMethod: (json['payment_method'] ?? '').toString(),
      voteCount: read('vote_count'),
      amount: read('amount_fcfa'),
      createdAt: (json['created_at'] ?? '').toString(),
    );
  }
}

class PayoutRequest {
  final int id;
  final int amount;
  final String paymentMethod;
  final String accountHolder;
  final String walletNumber;
  final String status;
  final String createdAt;

  const PayoutRequest({
    required this.id,
    required this.amount,
    required this.paymentMethod,
    required this.accountHolder,
    required this.walletNumber,
    required this.status,
    required this.createdAt,
  });

  String get statusLabel => switch (status) {
        'pending' => 'En attente',
        'completed' => 'Déjà payé',
        'rejected' => 'Rejetée',
        _ => status,
      };

  factory PayoutRequest.fromJson(Map<String, dynamic> json) {
    int read(String key) {
      final value = json[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      return int.tryParse('$value') ?? 0;
    }

    return PayoutRequest(
      id: read('payout_id'),
      amount: read('amount_fcfa'),
      paymentMethod: (json['payment_method'] ?? '').toString(),
      accountHolder: (json['account_holder'] ?? '').toString(),
      walletNumber: (json['wallet_number'] ?? '').toString(),
      status: (json['status'] ?? 'pending').toString(),
      createdAt: (json['created_at'] ?? '').toString(),
    );
  }
}