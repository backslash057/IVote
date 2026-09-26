import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config.dart';

class AuthService {
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  static const String _tokenKey = 'ivote_auth_token';
  static const String _userKey = 'ivote_user_data';

  final ValueNotifier<Map<String, dynamic>?> currentUser = ValueNotifier(null);

  bool get isAuthenticated => currentUser.value != null;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final userString = prefs.getString(_userKey);
    final token = prefs.getString(_tokenKey);

    if (userString != null && token != null) {
      try {
        currentUser.value = jsonDecode(userString);
        // Refresh profile in background
        fetchProfile();
      } catch (_) {
        await logout();
      }
    }
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<Map<String, String>> _getHeaders() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$kBaseUrl/api/auth/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        await _saveAuthData(data['token'], data['user']);
        return {'success': true, 'message': data['message'] ?? 'Connexion réussie.'};
      }
      return {'success': false, 'message': data['message'] ?? 'Identifiants invalides.'};
    } catch (e) {
      return {'success': false, 'message': 'Erreur réseau ou serveur inaccessible.'};
    }
  }

  Future<Map<String, dynamic>> signup(String name, String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$kBaseUrl/api/auth/signup'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'name': name, 'email': email, 'password': password}),
      );

      final data = jsonDecode(response.body);
      if ((response.statusCode == 200 || response.statusCode == 201) && data['success'] == true) {
        await _saveAuthData(data['token'], data['user']);
        return {'success': true, 'message': data['message'] ?? 'Compte créé avec succès.'};
      }
      return {'success': false, 'message': data['message'] ?? 'Erreur lors de la création du compte.'};
    } catch (e) {
      return {'success': false, 'message': 'Erreur réseau ou serveur inaccessible.'};
    }
  }

  Future<Map<String, dynamic>> updateProfile({
    required String name,
    required String email,
    String? currentPassword,
    String? newPassword,
  }) async {
    try {
      final headers = await _getHeaders();
      final body = {
        'name': name,
        'email': email,
        if (currentPassword != null && currentPassword.isNotEmpty)
          'current_password': currentPassword,
        if (newPassword != null && newPassword.isNotEmpty)
          'new_password': newPassword,
      };

      final response = await http.post(
        Uri.parse('$kBaseUrl/api/auth/profile'),
        headers: headers,
        body: jsonEncode(body),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        if (data['token'] != null) {
          await _saveAuthData(data['token'], data['user']);
        }
        return {'success': true, 'message': data['message'] ?? 'Profil mis à jour.'};
      }
      return {'success': false, 'message': data['message'] ?? 'Erreur lors de la mise à jour.'};
    } catch (e) {
      return {'success': false, 'message': 'Erreur de communication avec le serveur.'};
    }
  }

  Future<Map<String, dynamic>?> fetchProfile() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(Uri.parse('$kBaseUrl/api/auth/me'), headers: headers);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['user'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString(_userKey, jsonEncode(data['user']));
          currentUser.value = data['user'];
          return data['user'];
        }
      }
    } catch (_) {}
    return null;
  }

  Future<Map<String, dynamic>?> fetchStats() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(Uri.parse('$kBaseUrl/api/auth/stats'), headers: headers);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) return data['data'];
      }
    } catch (_) {}
    return null;
  }

  Future<void> _saveAuthData(String token, Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user));
    currentUser.value = user;
  }

  Future<void> logout() async {
    try {
      final headers = await _getHeaders();
      await http.post(Uri.parse('$kBaseUrl/api/auth/logout'), headers: headers);
    } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    currentUser.value = null;
  }
}