import '../models/campaign.dart';
import '../models/campaign_detail.dart';
import '../models/dashboard.dart';
import '../models/user.dart';
import 'api_client.dart';

class CampaignService {
  final ApiClient _api = ApiClient();

  Future<List<Campaign>> fetchCampaigns() async {
    final result = await _api.get('/api/campaigns');
    return _toCampaignList(result['data']);
  }

  Future<CampaignDetail> fetchCampaignDetail(int id) async {
    final result = await _api.get('/api/campaigns/$id');
    final data = result['data'];
    if (data is! Map<String, dynamic>) {
      throw ApiException('Campagne introuvable.');
    }
    return CampaignDetail.fromJson(data);
  }

  Future<List<Campaign>> fetchMyCampaigns() async {
    final result = await _api.get('/api/my-campaigns');
    return _toCampaignList(result['data']);
  }

  Future<DashboardData> dashboard(int campaignId) async {
    final result = await _api.get('/api/campaigns/$campaignId/dashboard');
    final data = result['data'];
    if (data is! Map<String, dynamic>) {
      throw ApiException('Données du tableau de bord indisponibles.');
    }
    return DashboardData.fromJson(data);
  }

  Future<int> createCampaign({
    required String title,
    required String description,
    required int pricePerVote,
    String? dateCloture,
  }) async {
    final result = await _api.post('/api/campaigns', body: {
      'title': title,
      'description': description,
      'price_per_vote': pricePerVote,
      if (dateCloture != null && dateCloture.isNotEmpty) 'date_cloture': dateCloture,
    });
    final id = _toInt(result['campaign_id'], 0);
    return id;
  }

  Future<void> updateCampaign(
    int id, {
    required String title,
    required String description,
    required int pricePerVote,
    String? dateCloture,
  }) async {
    await _api.post('/api/campaigns/$id/update', body: {
      'title': title,
      'description': description,
      'price_per_vote': pricePerVote,
      if (dateCloture != null && dateCloture.isNotEmpty) 'date_cloture': dateCloture,
    });
  }

  Future<void> setStatus(int id, String action) async {
    await _api.post('/api/campaigns/$id/status', body: {'action': action});
  }

  Future<void> relaunch(int id, String dateCloture) async {
    await _api.post('/api/campaigns/$id/relaunch', body: {'date_cloture': dateCloture});
  }

  Future<void> addCandidate(
    int campaignId, {
    required String name,
    required int candidateNumber,
    int? age,
    String? theme,
    String? categoryName,
    String? newCategoryName,
  }) async {
    await _api.post('/api/campaigns/$campaignId/candidates', body: {
      'name': name,
      'candidate_number': candidateNumber,
      if (age != null) 'age': age,
      if (theme != null && theme.isNotEmpty) 'theme': theme,
      if (categoryName != null && categoryName.isNotEmpty) 'category_name': categoryName,
      if (newCategoryName != null && newCategoryName.isNotEmpty) 'new_category_name': newCategoryName,
    });
  }

  Future<void> createPayout(
    int campaignId, {
    required int amount,
    required String paymentMethod,
    required String accountHolder,
    required String walletNumber,
  }) async {
    await _api.post('/api/campaigns/$campaignId/payouts', body: {
      'amount': amount,
      'payment_method': paymentMethod,
      'account_holder': accountHolder,
      'wallet_number': walletNumber,
    });
  }

  Future<VoteRecord> recordVote(
    int campaignId, {
    required int candidateId,
    required int voteCount,
    required String paymentMethod,
  }) async {
    final result = await _api.post('/api/campaigns/$campaignId/votes', body: {
      'candidate_id': candidateId,
      'vote_count': voteCount,
      'payment_method': paymentMethod,
    });
    return VoteRecord.fromJson(result);
  }

  List<Campaign> _toCampaignList(dynamic data) {
    if (data is! List) return const [];
    return data
        .whereType<Map<String, dynamic>>()
        .map(Campaign.fromJson)
        .toList();
  }

  int _toInt(dynamic value, int fallback) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse('$value') ?? fallback;
  }
}