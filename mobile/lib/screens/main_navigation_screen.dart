import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'home_page.dart';
import 'placeholder_page.dart';

class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;

  final List<Widget> _pages = const [
    HomePage(),
    PlaceholderPage(
      title: 'Tableau de bord',
      subtitle: 'Gestion et statistiques de vos campagnes organisées.',
      icon: Icons.bar_chart_rounded,
    ),
    PlaceholderPage(
      title: 'Revenus & Retraits',
      subtitle: 'Suivi de vos gains MoMo / OM et demandes de virements.',
      icon: Icons.account_balance_wallet_rounded,
    ),
    PlaceholderPage(
      title: 'Mon Compte',
      subtitle: 'Profil, paramètres et sécurité de votre compte.',
      icon: Icons.person_rounded,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final borderSide = BorderSide(color: theme.colorScheme.outline.withOpacity(0.5));

    return Scaffold(
      // --- NAVBAR UNIQUE & COMMUNE À TOUS LES ONGLETS ---
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: theme.colorScheme.primary,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: theme.colorScheme.primary.withOpacity(0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  )
                ],
              ),
              alignment: Alignment.center,
              child: const Icon(Icons.auto_awesome_rounded, size: 20, color: Colors.white),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'IVote',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: theme.colorScheme.onSurface,
                    letterSpacing: -0.5,
                  ),
                ),
                Text(
                  'Votes Monétisés',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          // Bouton Basculer le thème (Soleil / Lune)
          IconButton(
            onPressed: () => ThemeController.toggleTheme(),
            icon: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: theme.colorScheme.outline),
              ),
              child: Icon(
                ThemeController.isDarkMode
                    ? Icons.wb_sunny_outlined
                    : Icons.nightlight_round_outlined,
                size: 18,
                color: theme.colorScheme.onSurface,
              ),
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),

      // --- CORPS VARIABLE SELON L'ONGLET SÉLECTIONNÉ ---
      body: IndexedStack(
        index: _currentIndex,
        children: _pages,
      ),

      // --- BOTTOM NAVIGATION BAR ---
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          border: Border(top: borderSide),
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) => setState(() => _currentIndex = index),
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.how_to_vote_outlined),
              activeIcon: Icon(Icons.how_to_vote_rounded),
              label: 'Accueil',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.bar_chart_outlined),
              activeIcon: Icon(Icons.bar_chart_rounded),
              label: 'Dashboard',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.account_balance_wallet_outlined),
              activeIcon: Icon(Icons.account_balance_wallet_rounded),
              label: 'Revenus',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline_rounded),
              activeIcon: Icon(Icons.person_rounded),
              label: 'Compte',
            ),
          ],
        ),
      ),
    );
  }
}