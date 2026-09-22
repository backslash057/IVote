import 'package:flutter/material.dart';

class AppColors {
  // --- Mode Clair ---
  static const Color primaryBlueLight = Color(0xFF2668FC);
  static const Color primaryBlueLightHover = Color(0xFF1550D4);
  static const Color backgroundLight = Color(0xFFFFFFFF);
  static const Color backgroundSecondaryLight = Color(0xFFF8F9FA);
  static const Color foregroundLight = Color(0xFF1A1A1A);
  static const Color foregroundSecondaryLight = Color(0xFF4A4A4A);
  static const Color borderLight = Color(0xFFE0E0E0);

  // --- Mode Sombre ---
  static const Color primaryBlueDark = Color(0xFF4D84FD);
  static const Color primaryBlueDarkHover = Color(0xFF2668FC);
  static const Color backgroundDark = Color(0xFF0F1419);
  static const Color backgroundSecondaryDark = Color(0xFF1A1F2E);
  static const Color foregroundDark = Color(0xFFE8E8E8);
  static const Color foregroundSecondaryDark = Color(0xFFA8A8A8);
  static const Color borderDark = Color(0xFF2A2F3A);

  // --- Accents communs ---
  static const Color greenAccent = Color(0xFF22C55E);
  static const Color greenAccentLight = Color(0xFF4ADE80);
  static const Color orangeAccent = Color(0xFFF97316);
  static const Color redAccent = Color(0xFFEF4444);
}

class ThemeController {
  static final ValueNotifier<ThemeMode> themeMode =
      ValueNotifier<ThemeMode>(ThemeMode.light);

  static void toggleTheme() {
    themeMode.value =
        themeMode.value == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
  }

  static bool get isDarkMode => themeMode.value == ThemeMode.dark;
}

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      fontFamily: 'Plus Jakarta Sans',
      scaffoldBackgroundColor: AppColors.backgroundLight,
      colorScheme: const ColorScheme.light(
        primary: AppColors.primaryBlueLight,
        surface: AppColors.backgroundLight,
        surfaceContainerHighest: AppColors.backgroundSecondaryLight,
        outline: AppColors.borderLight,
        onSurface: AppColors.foregroundLight,
        onSurfaceVariant: AppColors.foregroundSecondaryLight,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.backgroundLight,
        elevation: 0,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.backgroundLight,
        selectedItemColor: AppColors.primaryBlueLight,
        unselectedItemColor: AppColors.foregroundSecondaryLight,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
    );
  }

  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      fontFamily: 'Plus Jakarta Sans',
      scaffoldBackgroundColor: AppColors.backgroundDark,
      colorScheme: const ColorScheme.dark(
        primary: AppColors.primaryBlueDark,
        surface: AppColors.backgroundDark,
        surfaceContainerHighest: AppColors.backgroundSecondaryDark,
        outline: AppColors.borderDark,
        onSurface: AppColors.foregroundDark,
        onSurfaceVariant: AppColors.foregroundSecondaryDark,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.backgroundDark,
        elevation: 0,
        scrolledUnderElevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.backgroundDark,
        selectedItemColor: AppColors.primaryBlueDark,
        unselectedItemColor: AppColors.foregroundSecondaryDark,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
    );
  }
}