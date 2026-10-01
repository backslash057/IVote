import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'dashboard_page.dart';
import 'home_page.dart';
import 'profile_page.dart';
import 'revenues_page.dart';
import 'notifications_page.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';

class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;
  Future<int> _unreadCount = Future.value(0);
  final _notificationService = NotificationService();

  final List<Widget> _pages = const [
    HomePage(),
    DashboardPage(),
    RevenuesPage(), // <-- Onglet 3 : Revenus & Retraits
    ProfilePage(),
  ];

  @override
  void initState() {
    super.initState();
    AuthService().currentUser.addListener(_refreshUnreadCount);
    _refreshUnreadCount();
  }

  @override
  void dispose() {
    AuthService().currentUser.removeListener(_refreshUnreadCount);
    super.dispose();
  }

  void _refreshUnreadCount() {
    _unreadCount = AuthService().isAuthenticated
        ? _notificationService.unreadCount().catchError((_) => 0)
        : Future.value(0);
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final borderSide =
        BorderSide(color: theme.colorScheme.outline.withOpacity(0.5));

    return ValueListenableBuilder<Map<String, dynamic>?>(
      valueListenable: AuthService().currentUser,
      builder: (context, user, _) {
        final isSuperAdmin = user?['role'] == 'super_admin';
        final pages =
            isSuperAdmin ? const <Widget>[HomePage(), ProfilePage()] : _pages;
        final selectedIndex = _currentIndex < pages.length ? _currentIndex : 0;
        final items = isSuperAdmin
            ? const [
                BottomNavigationBarItem(
                  icon: Icon(Icons.how_to_vote_outlined),
                  activeIcon: Icon(Icons.how_to_vote_rounded),
                  label: 'Accueil',
                ),
                BottomNavigationBarItem(
                  icon: Icon(Icons.person_outline_rounded),
                  activeIcon: Icon(Icons.person_rounded),
                  label: 'Compte',
                ),
              ]
            : const [
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
              ];

        return Scaffold(
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
                  child: const Icon(Icons.auto_awesome_rounded,
                      size: 20, color: Colors.white),
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
              FutureBuilder<int>(
                future: _unreadCount,
                builder: (context, snapshot) => IconButton(
                  onPressed: AuthService().isAuthenticated
                      ? () async {
                          await Navigator.of(context).push(MaterialPageRoute(
                              builder: (_) => const NotificationsPage()));
                          _refreshUnreadCount();
                        }
                      : null,
                  tooltip: 'Notifications',
                  icon: Badge(
                    isLabelVisible: (snapshot.data ?? 0) > 0,
                    label: Text('${snapshot.data ?? 0}'),
                    child: const Icon(Icons.notifications_none_rounded),
                  ),
                ),
              ),
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
          body: IndexedStack(
            index: selectedIndex,
            children: pages,
          ),
          bottomNavigationBar: Container(
            decoration: BoxDecoration(
              border: Border(top: borderSide),
            ),
            child: BottomNavigationBar(
              currentIndex: selectedIndex,
              onTap: (index) => setState(() => _currentIndex = index),
              items: items,
            ),
          ),
        );
      },
    );
  }
}
