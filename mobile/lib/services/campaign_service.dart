import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../config.dart';
import '../models/campaign.dart';
import 'auth_service.dart';

class CampaignService {
  Future<Map<String, String>> _getHeaders() async {
    final token = await AuthService().getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<List<Campaign>> fetchCampaigns() async {
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/campaigns'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final List<dynamic> data = json['data'] ?? [];
      return data.map((item) => Campaign.fromJson(item)).toList();
    } else {
      throw Exception(
          'Erreur lors du chargement des campagnes (${response.statusCode})');
    }
  }

  Future<List<Campaign>> fetchMyCampaigns() async {
    final headers = await _getHeaders();
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/my-campaigns'),
      headers: headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final List<dynamic> data = json['data'] ?? [];
      return data.map((item) => Campaign.fromJson(item)).toList();
    } else if (response.statusCode == 401) {
      throw Exception('Non authentifié. Veuillez vous reconnecter.');
    } else {
      throw Exception('Impossible de charger vos campagnes.');
    }
  }

  Future<Map<String, dynamic>> createCampaign({
    required String title,
    required String description,
    required int pricePerVote,
    String? dateCloture,
  }) async {
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns'),
      headers: await _getHeaders(),
      body: jsonEncode({
        'title': title,
        'description': description,
        'price_per_vote': pricePerVote,
        if (dateCloture != null) 'date_cloture': dateCloture,
      }),
    );
    final json = jsonDecode(response.body);
    if ((response.statusCode == 200 || response.statusCode == 201) &&
        json['success'] == true) return json;
    throw Exception(json['message'] ?? 'Impossible de créer la campagne.');
  }

  Future<void> deleteCampaign(int id) async {
    final response = await http.delete(Uri.parse('$kBaseUrl/api/campaigns/$id'),
        headers: await _getHeaders());
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true)
      throw Exception(
          json['message'] ?? 'Impossible de supprimer la campagne.');
  }

  Future<Map<String, dynamic>> addCategory(int campaignId, String name) async {
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$campaignId/categories'),
      headers: await _getHeaders(),
      body: jsonEncode({'name': name}),
    );
    final json = jsonDecode(response.body);
    if ((response.statusCode == 200 || response.statusCode == 201) &&
        json['success'] == true) return json;
    throw Exception(json['message'] ?? 'Impossible de créer la catégorie.');
  }

  Future<void> updateCategory(int id, String name) async {
    final response = await http.put(Uri.parse('$kBaseUrl/api/categories/$id'),
        headers: await _getHeaders(), body: jsonEncode({'name': name}));
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true) {
      throw Exception(
          json['message'] ?? 'Impossible de modifier la catégorie.');
    }
  }

  Future<void> deleteCategory(int id) async {
    final response = await http.delete(
        Uri.parse('$kBaseUrl/api/categories/$id'),
        headers: await _getHeaders());
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true) {
      throw Exception(
          json['message'] ?? 'Impossible de supprimer la catégorie.');
    }
  }

  Future<void> updateCandidate({
    required int candidateId,
    required String name,
    required int candidateNumber,
    int? categoryId,
    int? age,
    String? theme,
    String? description,
    String? bio,
  }) async {
    final response = await http.put(
      Uri.parse('$kBaseUrl/api/candidates/$candidateId'),
      headers: await _getHeaders(),
      body: jsonEncode({
        'name': name,
        'candidate_number': candidateNumber,
        'category_id': categoryId,
        'age': age,
        'theme': theme,
        'description': description,
        'bio': bio,
      }),
    );
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true) {
      throw Exception(json['message'] ?? 'Impossible de modifier le candidat.');
    }
  }

  Future<void> deleteCandidate(int id) async {
    final response = await http.delete(
        Uri.parse('$kBaseUrl/api/candidates/$id'),
        headers: await _getHeaders());
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true) {
      throw Exception(
          json['message'] ?? 'Impossible de supprimer le candidat.');
    }
  }

  Future<CampaignDetail> fetchCampaignDetail(int id) async {
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/campaigns/$id'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['success'] == true && json['data'] != null) {
        return CampaignDetail.fromJson(json['data']);
      }
      throw Exception(json['message'] ?? 'Campagne introuvable.');
    } else {
      throw Exception('Erreur serveur lors de la récupération des détails.');
    }
  }

  /// Récupère toutes les données du dashboard de gestion d'une campagne
  Future<Map<String, dynamic>> fetchCampaignDashboard(int id) async {
    final headers = await _getHeaders();
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/campaigns/$id/dashboard'),
      headers: headers,
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json['data'];
    } else {
      throw Exception(
          json['message'] ?? 'Impossible de charger les données de gestion.');
    }
  }

  /// Met à jour les infos d'une campagne
  Future<Map<String, dynamic>> updateCampaign({
    required int id,
    required String title,
    required String description,
    required int pricePerVote,
    String? dateCloture,
  }) async {
    final headers = await _getHeaders();
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$id/update'),
      headers: headers,
      body: jsonEncode({
        'title': title,
        'description': description,
        'price_per_vote': pricePerVote,
        'date_cloture': dateCloture,
      }),
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json;
    }
    throw Exception(json['message'] ?? 'Erreur lors de la modification.');
  }

  /// Change le statut (publish ou close)
  Future<Map<String, dynamic>> updateCampaignStatus(
      int id, String action) async {
    final headers = await _getHeaders();
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$id/status'),
      headers: headers,
      body: jsonEncode({'action': action}),
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json;
    }
    throw Exception(
        json['message'] ?? 'Erreur lors de la mise à jour du statut.');
  }

  /// Relance une campagne
  Future<Map<String, dynamic>> relaunchCampaign(
      int id, String dateCloture) async {
    final headers = await _getHeaders();
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$id/relaunch'),
      headers: headers,
      body: jsonEncode({'date_cloture': dateCloture}),
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json;
    }
    throw Exception(json['message'] ?? 'Erreur lors de la relance.');
  }

  /// Ajout d'un candidat avec support multipart pour photo optionnelle
  Future<Map<String, dynamic>> addCandidate({
    required int campaignId,
    required String name,
    required int candidateNumber,
    int? age,
    String? theme,
    String? description,
    String? bio,
    int? categoryId,
    String? newCategoryName,
    File? imageFile,
  }) async {
    final token = await AuthService().getToken();
    final uri = Uri.parse('$kBaseUrl/api/campaigns/$campaignId/candidates');
    final request = http.MultipartRequest('POST', uri);

    if (token != null) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    request.headers['Accept'] = 'application/json';

    request.fields['name'] = name;
    request.fields['candidate_number'] = candidateNumber.toString();
    if (age != null) request.fields['age'] = age.toString();
    if (theme != null && theme.isNotEmpty) request.fields['theme'] = theme;
    if (description != null && description.isNotEmpty)
      request.fields['description'] = description;
    if (bio != null && bio.isNotEmpty) request.fields['bio'] = bio;
    if (categoryId != null)
      request.fields['category_id'] = categoryId.toString();
    if (newCategoryName != null && newCategoryName.isNotEmpty) {
      request.fields['new_category_name'] = newCategoryName;
    }

    if (imageFile != null) {
      request.files
          .add(await http.MultipartFile.fromPath('image', imageFile.path));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    final json = jsonDecode(response.body);

    if ((response.statusCode == 200 || response.statusCode == 201) &&
        json['success'] == true) {
      return json;
    }
    throw Exception(json['message'] ?? 'Erreur lors de l\'ajout du candidat.');
  }

  /// Récupère la synthèse financière globale et par campagne
  Future<Map<String, dynamic>> fetchRevenues() async {
    final headers = await _getHeaders();
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/organizer/revenues'),
      headers: headers,
    );

    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return json['data'];
    }
    throw Exception(
        json['message'] ?? 'Impossible de charger les données financières.');
  }

  /// Effectue une demande de retrait
  Future<Map<String, dynamic>> requestPayout({
    required int campaignId,
    required int amount,
    required String paymentMethod,
    required String accountHolder,
    required String walletNumber,
  }) async {
    final headers = await _getHeaders();
    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$campaignId/payouts'),
      headers: headers,
      body: jsonEncode({
        'amount': amount,
        'payment_method': paymentMethod,
        'account_holder': accountHolder,
        'wallet_number': walletNumber,
      }),
    );

    final json = jsonDecode(response.body);
    if ((response.statusCode == 200 || response.statusCode == 201) &&
        json['success'] == true) {
      return json;
    }
    throw Exception(json['message'] ?? 'Erreur lors de la demande de retrait.');
  }

  Future<Map<String, dynamic>> recordVote({
    required int campaignId,
    required int candidateId,
    required int voteCount,
    required String paymentMethod,
    String? phoneNumber,
  }) async {
    final headers = await _getHeaders();
    final body = jsonEncode({
      'candidate_id': candidateId,
      'vote_count': voteCount,
      'payment_method': paymentMethod,
      if (phoneNumber != null && phoneNumber.isNotEmpty) 'phone_number': phoneNumber,
    });

    final response = await http.post(
      Uri.parse('$kBaseUrl/api/campaigns/$campaignId/votes'),
      headers: headers,
      body: body,
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 || response.statusCode == 201) {
      return data;
    } else {
      throw Exception(
          data['message'] ?? 'Erreur lors de l\'enregistrement du vote.');
    }
  }

  /// Sonde l'état d'un paiement AangaraaPay initié (paiement réel).
  Future<Map<String, dynamic>> paymentStatus(String transactionRef) async {
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/votes/status/${Uri.encodeComponent(transactionRef)}'),
      headers: {'Accept': 'application/json'},
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return data;
    }
    throw Exception(
        data['message'] ?? 'Impossible de récupérer le statut du paiement.');
  }
}
