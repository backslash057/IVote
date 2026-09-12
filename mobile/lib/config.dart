const String kDefaultBaseUrl = 'http://127.0.0.1:8080';

/// URL du backend. Surchargable au lancement :
///   flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8080
const String kBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: kDefaultBaseUrl);