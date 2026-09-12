import 'package:flutter/material.dart';

import '../config.dart';
import '../models/campaign.dart';
import '../services/campaign_service.dart';
import '../theme.dart';
import '../widgets/campaign_card.dart';
import '../widgets/empty_state.dart';
import '../widgets/hero_section.dart';
import 'campaign_detail_page.dart';
import 'organizer_home_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  final CampaignService _service = CampaignService();
  late Future<List<Campaign>> _future;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchCampaigns();
  }

  void _reload() {
    setState(() {
      _future = _service.fetchCampaigns();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PreferredSize(
        preferredSize: const Size.fromHeight(64),
        child: Container(
          decoration: const BoxDecoration(
            border: Border(bottom: BorderSide(color: AppColors.surfaceAlt)),
          ),
          child: AppBar(
            title: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: AppColors.emerald,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  alignment: Alignment.center,
                  child: const Icon(Icons.how_to_vote,
                      size: 18, color: Colors.white),
                ),
                const SizedBox(width: 12),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'IVote',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      'Vote & Paiement Mobile Money',
                      style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ],
            ),
            actions: [
              PopupMenuButton<String>(
                icon: const Icon(Icons.menu, color: Colors.white),
                color: AppColors.surface,
                onSelected: (value) {
                  if (value == 'refresh') _reload();
                  if (value == 'organizer') {
                    Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => const OrganizerHomePage(),
                      ),
                    );
                  }
                },
                itemBuilder: (context) => const [
                  PopupMenuItem(value: 'refresh', child: Text('Actualiser')),
                  PopupMenuItem(value: 'organizer', child: Text('Espace organisateur')),
                ],
              ),
            ],
          ),
        ),
      ),
      body: FutureBuilder<List<Campaign>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppColors.emerald),
            );
          }
          if (snapshot.hasError) {
            return _ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }

          final campaigns = snapshot.data ?? [];

          return RefreshIndicator(
            color: AppColors.emerald,
            onRefresh: () async => _reload(),
            child: ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
              itemCount: campaigns.isEmpty ? 2 : campaigns.length + 1,
              itemBuilder: (context, index) {
                if (index == 0) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 24),
                    child: HeroSection(
                      campaigns: campaigns,
                      onAction: _reload,
                    ),
                  );
                }
                if (campaigns.isEmpty) {
                  return const EmptyState();
                }
                final campaign = campaigns[index - 1];
                return Padding(
                  padding: const EdgeInsets.only(bottom: 20),
                  child: CampaignCard(
                    campaign: campaign,
                    onTap: () => _openDetail(campaign),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }

  void _openDetail(Campaign campaign) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => CampaignDetailPage(campaignId: campaign.id),
      ),
    );
  }
}

class _ErrorRetry extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorRetry({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, color: AppColors.danger, size: 40),
            const SizedBox(height: 12),
            const Text(
              'Impossible de charger les campagnes',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const SizedBox(height: 6),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Réessayer'),
            ),
            const SizedBox(height: 12),
            const Text(
              kBaseUrl,
              style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
            ),
          ],
        ),
      ),
    );
  }
}