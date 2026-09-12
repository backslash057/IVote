import '../models/user.dart';
import 'api_client.dart';
import 'session.dart';

class AuthService {
  final ApiClient _api = ApiClient();

  Future<AppUser> login(String email, String password) async {
    final result = await _api.post('/api/auth/login', body: {
      'email': email,
      'password': password,
    });
    return _applySession(result);
  }

  Future<AppUser> signup(String name, String email, String password) async {
    final result = await _api.post('/api/auth/signup', body: {
      'name': name,
      'email': email,
      'password': password,
    });
    return _applySession(result);
  }

  Future<AppUser?> restoreSession() async {
    final token = Session.token;
    if (token == null) return null;
    try {
      final result = await _api.get('/api/auth/me');
      final rawUser = result['user'];
      if (rawUser is Map<String, dynamic>) {
        Session.user = AppUser.fromJson(rawUser);
        return Session.user;
      }
    } catch (_) {
      // Token invalide ou expiré : on nettoie la session.
      Session.token = null;
      Session.user = null;
    }
    return null;
  }

  void logout() {
    Session.token = null;
    Session.user = null;
  }

  AppUser _applySession(Map<String, dynamic> result) {
    final token = result['token'];
    final rawUser = result['user'];
    if (token is String && rawUser is Map<String, dynamic>) {
      Session.token = token;
      Session.user = AppUser.fromJson(rawUser);
      return Session.user!;
    }
    throw ApiException('Réponse inattendue du serveur.');
  }
}