import 'package:flutter/material.dart';

import '../models/campaign.dart';
import '../models/user.dart';
import '../services/campaign_service.dart';
import '../services/session.dart';
import '../theme.dart';
import 'campaign_admin_page.dart';
import 'create_campaign_page.dart';
import 'login_page.dart';
import 'signup_page.dart';

class OrganizerHomePage extends StatefulWidget {
  const OrganizerHomePage({super.key});

  @override
  State<OrganizerHomePage> createState() => _OrganizerHomePageState();
}

class _OrganizerHomePageState extends State<OrganizerHomePage> {
  final CampaignService _service = CampaignService();
  late Future<List<Campaign>> _future;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  void _refresh() {
    _future = Session.isLoggedIn ? _service.fetchMyCampaigns() : Future.value(const []);
  }

  Future<void> _openLogin() async {
    await Navigator.of(context).push(
      MaterialPageRoute<AppUser>(builder: (_) => const LoginPage()),
    );
    if (mounted) setState(_refresh);
  }

  Future<void> _openSignup() async {
    await Navigator.of(context).push(
      MaterialPageRoute<AppUser>(builder: (_) => const SignupPage()),
    );
    if (mounted) setState(_refresh);
  }

  Future<void> _createCampaign() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const CreateCampaignPage()),
    );
    if (mounted) setState(_refresh);
  }

  Future<void> _openAdmin(Campaign campaign) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => CampaignAdminPage(campaign: campaign),
      ),
    );
    if (mounted) setState(_refresh);
  }

  @override
  Widget build(BuildContext context) {
    final user = Session.user;
    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: Text(user == null ? 'Espace organisateur' : 'Bonjour ${user.name}'),
        actions: [
          if (user != null)
            IconButton(
              tooltip: 'Se déconnecter',
              icon: const Icon(Icons.logout, color: AppColors.danger),
              onPressed: () {
                Session.token = null;
                Session.user = null;
                setState(_refresh);
              },
            ),
        ],
      ),
      body: user == null ? _buildLoggedOut() : _buildDashboard(),
    );
  }

  Widget _buildLoggedOut() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: AppColors.emerald,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(Icons.how_to_vote, color: Colors.white, size: 26),
              ),
              const SizedBox(height: 14),
              const Text(
                'Espace organisateur',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Connectez-vous pour créer et gérer vos campagnes, suivre les votes et demander des retraits.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _openLogin,
                  child: const Text('Se connecter'),
                ),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: _openSignup,
                child: const Text(
                  'Créer un compte',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDashboard() {
    return FutureBuilder<List<Campaign>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(
            child: CircularProgressIndicator(color: AppColors.emerald),
          );
        }
        if (snapshot.hasError) {
          return Center(
            child: Text(
              '${snapshot.error}',
              style: const TextStyle(color: AppColors.danger, fontSize: 13),
            ),
          );
        }
        final campaigns = snapshot.data ?? [];
        return RefreshIndicator(
          color: AppColors.emerald,
          onRefresh: () async => setState(_refresh),
          child: ListView.builder(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            itemCount: campaigns.length + 1,
            itemBuilder: (context, index) {
              if (index == 0) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: ElevatedButton.icon(
                    onPressed: _createCampaign,
                    icon: const Icon(Icons.add),
                    label: const Text('Nouvelle campagne'),
                  ),
                );
              }
              final campaign = campaigns[index - 1];
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _MyCampaignTile(
                  campaign: campaign,
                  onTap: () => _openAdmin(campaign),
                ),
              );
            },
          ),
        );
      },
    );
  }
}

class _MyCampaignTile extends StatelessWidget {
  final Campaign campaign;
  final VoidCallback onTap;

  const _MyCampaignTile({required this.campaign, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final (label, color) = campaign.isActive
        ? ('En direct', AppColors.emerald)
        : campaign.isDraft
            ? ('Brouillon', AppColors.amberDark)
            : ('Terminé', AppColors.surfaceAlt);

    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(99),
              ),
              child: Text(
                label,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    campaign.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    campaign.description,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.textSecondary),
          ],
        ),
      ),
    );
  }
}