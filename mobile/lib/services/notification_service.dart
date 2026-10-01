import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config.dart';
import 'auth_service.dart';

class NotificationService {
  Future<Map<String, String>> _headers() async {
    final token = await AuthService().getToken();
    return {
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token'
    };
  }

  Future<List<Map<String, dynamic>>> fetchNotifications() async {
    final response = await http.get(Uri.parse('$kBaseUrl/api/notifications'),
        headers: await _headers());
    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return List<Map<String, dynamic>>.from(json['data'] ?? const []);
    }
    throw Exception(
        json['message'] ?? 'Impossible de charger les notifications.');
  }

  Future<int> unreadCount() async {
    final response = await http.get(
      Uri.parse('$kBaseUrl/api/notifications/unread-count'),
      headers: await _headers(),
    );
    final json = jsonDecode(response.body);
    if (response.statusCode == 200 && json['success'] == true) {
      return (json['count'] as num?)?.toInt() ?? 0;
    }
    throw Exception(
        json['message'] ?? 'Impossible de charger les notifications.');
  }

  Future<void> markRead(int id) async {
    await http.post(Uri.parse('$kBaseUrl/api/notifications/$id/read'),
        headers: await _headers());
  }

  Future<void> markAllRead() async {
    await http.post(Uri.parse('$kBaseUrl/api/notifications/read-all'),
        headers: await _headers());
  }
}
