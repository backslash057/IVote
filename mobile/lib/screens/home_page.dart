import 'package:flutter/material.dart';
import '../models/campaign.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';
import '../widgets/campaign_card.dart';
import '../widgets/skeleton_card.dart';
import 'campaign_detail_page.dart';

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
    _load();
  }

  void _load() {
    setState(() {
      _future = _service.fetchCampaigns();
    });
  }

  String _formatNumber(num number) {
    return number.toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (Match m) => '${m[1]} ',
        );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    // On retourne directement le RefreshIndicator (plus de Scaffold/AppBar imbriqué)
    return RefreshIndicator(
      color: theme.colorScheme.primary,
      onRefresh: () async => _load(),
      child: FutureBuilder<List<Campaign>>(
        future: _future,
        builder: (context, snapshot) {
          // État de chargement (Skeletons)
          if (snapshot.connectionState == ConnectionState.waiting) {
            return ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              children: [
                _buildHeroPlaceholder(theme),
                const SizedBox(height: 24),
                const SkeletonCard(),
                const SizedBox(height: 16),
                const SkeletonCard(),
              ],
            );
          }

          // État d'erreur
          if (snapshot.hasError) {
            return _buildErrorState(theme, '${snapshot.error}');
          }

          final campaigns = snapshot.data ?? [];

          // Calcul des statistiques
          final activeCount = campaigns.where((c) => c.status == 'active').length;
          final totalVotes = campaigns.fold<int>(0, (sum, c) => sum + c.totalVotes);

          return ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            children: [
              // 1. Hero Banner
              _buildHeroHeader(
                theme: theme,
                activeCount: activeCount,
                totalCampaigns: campaigns.length,
                totalVotes: totalVotes,
              ),
              const SizedBox(height: 24),

              // 2. Liste des Campagnes ou Empty State
              if (campaigns.isEmpty)
                _buildEmptyState(theme)
              else
                ...campaigns.map((camp) => Padding(
                      padding: const EdgeInsets.only(bottom: 20),
                      child: CampaignCard(
                        campaign: camp,
                        onTap: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) =>
                                  CampaignDetailPage(campaignId: camp.id),
                            ),
                          );
                        },
                      ),
                    )),
            ],
          );
        },
      ),
    );
  }

  Widget _buildHeroHeader({
    required ThemeData theme,
    required int activeCount,
    required int totalCampaigns,
    required int totalVotes,
  }) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Toutes les Campagnes de Vote',
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: theme.colorScheme.onSurface,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Découvrez les scrutins actifs, soutenez vos candidats favoris et réglez vos voix en direct via MTN MoMo et Orange Money.',
            style: TextStyle(
              fontSize: 12,
              height: 1.5,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _buildStatItem(
                  theme: theme,
                  label: 'ACTIVES',
                  value: activeCount.toString(),
                  valueColor: AppColors.greenAccent,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildStatItem(
                  theme: theme,
                  label: 'TOTAL',
                  value: totalCampaigns.toString(),
                  valueColor: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildStatItem(
                  theme: theme,
                  label: 'VOTES',
                  value: _formatNumber(totalVotes),
                  valueColor: theme.colorScheme.onSurface,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem({
    required ThemeData theme,
    required String label,
    required String value,
    required Color valueColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: valueColor,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              fontWeight: FontWeight.w700,
              color: theme.colorScheme.onSurfaceVariant,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeroPlaceholder(ThemeData theme) {
    return Container(
      height: 160,
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: theme.colorScheme.outline),
      ),
    );
  }

  Widget _buildErrorState(ThemeData theme, String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28.0),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: theme.colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: theme.colorScheme.outline),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.redAccent.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.error_outline_rounded, color: AppColors.redAccent, size: 32),
              ),
              const SizedBox(height: 14),
              Text(
                'Impossible de charger les campagnes',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                  color: theme.colorScheme.onSurface,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                message,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
              ),
              const SizedBox(height: 18),
              ElevatedButton.icon(
                onPressed: _load,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Réessayer'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: theme.colorScheme.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              shape: BoxShape.circle,
              border: Border.all(color: theme.colorScheme.outline),
            ),
            child: Icon(Icons.how_to_vote_outlined,
                size: 36, color: theme.colorScheme.onSurfaceVariant),
          ),
          const SizedBox(height: 16),
          Text(
            'Aucune campagne disponible',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Il n\'y a actuellement aucun concours ou scrutin public ouvert au vote.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }
}