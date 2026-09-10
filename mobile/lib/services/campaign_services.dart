import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config.dart';
import '../models/campaign.dart';

class CampaignService {
  Future<List<Campaign>> fetchCampaigns() async {
    final uri = Uri.parse('$backendUrl/api/campaigns');
    final response = await http.get(uri);

    if (response.statusCode == 200) {
      final dynamic decoded = json.decode(response.body);
      final List<dynamic> list = decoded is List ? decoded : (decoded['data'] ?? []);
      return list.map((item) => Campaign.fromJson(item)).toList();
    } else {
      throw Exception('Erreur de chargement des campagnes (${response.statusCode})');
    }
  }
}