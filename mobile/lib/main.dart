import 'package:flutter/material.dart';
import 'theme/app_theme.dart';
import 'screens/main_navigation_screen.dart';
import 'services/auth_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AuthService().init();
  runApp(const IVoteApp());
}

class IVoteApp extends StatelessWidget {
  const IVoteApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<ThemeMode>(
      valueListenable: ThemeController.themeMode,
      builder: (context, currentMode, _) {
        return MaterialApp(
          title: 'IVote',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.lightTheme,
          darkTheme: AppTheme.darkTheme,
          themeMode: currentMode,
          home: const MainNavigationScreen(),
        );
      },
    );
  }
}