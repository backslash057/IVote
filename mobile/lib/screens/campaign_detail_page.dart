import 'package:flutter/material.dart';
import '../config.dart';
import '../models/campaign.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';
import '../widgets/vote_sheet.dart';

class CampaignDetailPage extends StatefulWidget {
  final int campaignId;

  const CampaignDetailPage({super.key, required this.campaignId});

  @override
  State<CampaignDetailPage> createState() => _CampaignDetailPageState();
}

class _CampaignDetailPageState extends State<CampaignDetailPage> {
  final CampaignService _service = CampaignService();
  late Future<CampaignDetail> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    setState(() {
      _future = _service.fetchCampaignDetail(widget.campaignId);
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

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Détails de la Campagne',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: theme.colorScheme.onSurface),
        ),
        actions: [
          IconButton(
            onPressed: () => ThemeController.toggleTheme(),
            icon: Icon(
              ThemeController.isDarkMode ? Icons.wb_sunny_outlined : Icons.nightlight_round_outlined,
              size: 20,
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: FutureBuilder<CampaignDetail>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.error_outline_rounded, color: AppColors.redAccent, size: 40),
                    const SizedBox(height: 12),
                    Text(
                      'Erreur de chargement',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '${snapshot.error}'.replaceAll('Exception: ', ''),
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
                  ],
                ),
              ),
            );
          }

          final detail = snapshot.data!;
          final isActive = detail.status == 'active';

          return RefreshIndicator(
            onRefresh: () async => _load(),
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              children: [
                _buildHeroBanner(theme, detail, isActive),
                const SizedBox(height: 24),

                Text(
                  'Candidats en compétition',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
                ),
                const SizedBox(height: 12),

                if (detail.categories.isEmpty)
                  Center(
                    child: Padding(
                      padding: const EdgeInsets.all(32.0),
                      child: Text(
                        'Aucun candidat inscrit dans cette campagne.',
                        style: TextStyle(color: theme.colorScheme.onSurfaceVariant, fontSize: 13),
                      ),
                    ),
                  )
                else
                  ...detail.categories.map((category) => _buildCategorySection(theme, detail, category, isActive)),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeroBanner(ThemeData theme, CampaignDetail detail, bool isActive) {
    String? imageUrl = detail.imageUrl;
    if (imageUrl != null && imageUrl.startsWith('/')) {
      imageUrl = '$kBaseUrl$imageUrl';
    }

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (imageUrl != null && imageUrl.isNotEmpty)
            Image.network(
              imageUrl,
              height: 200,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => const SizedBox.shrink(),
            ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Par ${detail.organizerName ?? 'Organisateur'}',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.primary),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: theme.colorScheme.outline),
                      ),
                      child: Text(
                        isActive ? '● En Direct' : 'Terminé',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: isActive ? AppColors.greenAccent : theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  detail.title,
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
                ),
                const SizedBox(height: 6),
                Text(
                  detail.description,
                  style: TextStyle(fontSize: 13, height: 1.5, color: theme.colorScheme.onSurfaceVariant),
                ),
                const SizedBox(height: 16),
                Divider(color: theme.colorScheme.outline),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _buildStatCol(theme, 'PRIX DU VOTE', '${detail.pricePerVote} FCFA'),
                    _buildStatCol(theme, 'TOTAL DES VOTES', _formatNumber(detail.totalVotes)),
                    _buildStatCol(theme, 'CANDIDATS', '${detail.candidateCount}'),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCol(ThemeData theme, String label, String value) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface)),
        const SizedBox(height: 2),
        Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurfaceVariant)),
      ],
    );
  }

  Widget _buildCategorySection(ThemeData theme, CampaignDetail detail, CampaignCategory category, bool isActive) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: theme.colorScheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              category.name,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.primary),
            ),
          ),
          const SizedBox(height: 12),
          ...category.candidates.map((cand) => _buildCandidateCard(theme, detail, cand, isActive)),
        ],
      ),
    );
  }

  Widget _buildCandidateCard(ThemeData theme, CampaignDetail detail, Candidate cand, bool isActive) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: theme.colorScheme.surfaceContainerHighest,
                child: Text(
                  '#${cand.number}',
                  style: TextStyle(fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      cand.name,
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                    ),
                    if (cand.theme != null && cand.theme!.isNotEmpty)
                      Text(
                        cand.theme!,
                        style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant),
                      ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${cand.percentage.toStringAsFixed(1)}%',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: theme.colorScheme.primary),
                  ),
                  Text(
                    '${_formatNumber(cand.votes)} votes',
                    style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: cand.percentage / 100,
              backgroundColor: theme.colorScheme.surfaceContainerHighest,
              valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
              minHeight: 6,
            ),
          ),
          if (isActive) ...[
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              height: 38,
              child: ElevatedButton.icon(
                onPressed: () async {
                  final voted = await showVoteSheet(context, campaign: detail, candidate: cand);
                  if (voted == true) {
                    _load();
                    if (!mounted) return;
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: const Text('Vote enregistré avec succès !'),
                        backgroundColor: AppColors.greenAccent,
                        behavior: SnackBarBehavior.floating,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    );
                  }
                },
                icon: const Icon(Icons.how_to_vote_rounded, size: 16),
                label: const Text('Voter pour ce candidat', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: theme.colorScheme.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}