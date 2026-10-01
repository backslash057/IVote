import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config.dart';
import 'auth_service.dart';

class AdminService {
  Future<Map<String, String>> _headers() async {
    final token = await AuthService().getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token'
    };
  }

  Future<Map<String, dynamic>> overview() async => _get('/api/admin/overview');
  Future<List<dynamic>> users() async => _list('/api/admin/users');
  Future<List<dynamic>> campaigns() async => _list('/api/admin/campaigns');
  Future<List<dynamic>> payouts() async => _list('/api/admin/payouts');

  Future<Map<String, dynamic>> _get(String path) async {
    final response =
        await http.get(Uri.parse('$kBaseUrl$path'), headers: await _headers());
    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true)
      return Map<String, dynamic>.from(json['data'] ?? {});
    throw Exception(json['message'] ?? 'Erreur administrateur.');
  }

  Future<List<dynamic>> _list(String path) async {
    final response =
        await http.get(Uri.parse('$kBaseUrl$path'), headers: await _headers());
    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true)
      return List<dynamic>.from(json['data'] ?? []);
    throw Exception(json['message'] ?? 'Erreur administrateur.');
  }

  Future<void> updateUserStatus(int id, String status, String reason) async =>
      _post(
          '/api/admin/users/$id/status', {'status': status, 'reason': reason});
  Future<void> moderateCampaign(int id, String action, String reason) async =>
      _post('/api/admin/campaigns/$id/moderate',
          {'action': action, 'reason': reason});

  Future<void> reviewPayout(int id, String action, {String? reason}) async =>
      _post('/api/admin/payouts/$id/review', {
        'action': action,
        if (reason != null) 'reason': reason,
      });

  Future<void> sendAnnouncement({
    required String title,
    required String message,
    required bool broadcast,
    int? recipientId,
  }) async =>
      _post('/api/admin/notifications', {
        'title': title,
        'message': message,
        'broadcast': broadcast,
        if (!broadcast && recipientId != null) 'recipient_id': recipientId,
      });

  Future<void> _post(String path, Map<String, dynamic> body) async {
    final response = await http.post(Uri.parse('$kBaseUrl$path'),
        headers: await _headers(), body: jsonEncode(body));
    final json = jsonDecode(response.body);
    if (response.statusCode != 200 || json['success'] != true)
      throw Exception(json['message'] ?? 'Action impossible.');
  }
}
