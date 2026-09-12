import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';
import 'session.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() => message;
}

class ApiClient {
  final String baseUrl;

  ApiClient({String? baseUrl}) : baseUrl = baseUrl ?? kBaseUrl;

  Future<dynamic> get(String path) => _send(() => http.get(_uri(path), headers: _headers()));

  Future<dynamic> post(String path, {Map<String, dynamic>? body}) {
    return _send(() => http.post(
          _uri(path),
          headers: _headers(json: true),
          body: jsonEncode(body ?? const {}),
        ));
  }

  Uri _uri(String path) => Uri.parse('$baseUrl$path');

  Map<String, String> _headers({bool json = false}) {
    return <String, String>{
      'Accept': 'application/json',
      if (json) 'Content-Type': 'application/json',
      if (Session.token != null) 'Authorization': 'Bearer ${Session.token}',
    };
  }

  Future<dynamic> _send(Future<http.Response> Function() request) async {
    http.Response response;
    try {
      response = await request();
    } catch (_) {
      throw ApiException('Impossible de joindre le serveur ($baseUrl).');
    }

    dynamic decoded;
    try {
      decoded = jsonDecode(response.body);
    } catch (_) {
      decoded = null;
    }

    if (response.statusCode >= 200 &&
        response.statusCode < 300 &&
        decoded is Map<String, dynamic> &&
        (decoded['success'] ?? true) == true) {
      return decoded;
    }

    final message = decoded is Map<String, dynamic>
        ? (decoded['message'] as String? ?? 'Erreur serveur (${response.statusCode}).')
        : 'Erreur serveur (${response.statusCode}).';
    throw ApiException(message, statusCode: response.statusCode);
  }
}