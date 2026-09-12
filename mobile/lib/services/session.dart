import '../models/user.dart';

/// Session en mémoire (token JWT + utilisateur courant).
/// Non persistée : l'organisateur se reconnecte à chaque ouverture de l'app.
class Session {
  static String? token;
  static AppUser? user;

  static bool get isLoggedIn => token != null && user != null;
}