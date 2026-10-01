import 'package:flutter/material.dart';
import '../config.dart';
import '../models/campaign.dart';
import '../services/auth_service.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';
import '../widgets/skeleton_card.dart';
import 'auth/login_page.dart';
import 'auth/signup_page.dart';
import 'campaign_detail_page.dart';
import 'campaign_manage_page.dart';
import 'create_campaign_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  final CampaignService _campaignService = CampaignService();
  late Future<List<Campaign>> _future;

  @override
  void initState() {
    super.initState();
    _loadCampaigns();
  }

  void _loadCampaigns() {
    if (AuthService().isAuthenticated) {
      setState(() {
        _future = _campaignService.fetchMyCampaigns();
      });
    }
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

    return ValueListenableBuilder<Map<String, dynamic>?>(
      valueListenable: AuthService().currentUser,
      builder: (context, user, _) {
        if (user == null) {
          return _buildGuestView(theme);
        }

        return RefreshIndicator(
          color: theme.colorScheme.primary,
          onRefresh: () async => _loadCampaigns(),
          child: FutureBuilder<List<Campaign>>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return ListView(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                  children: [
                    _buildHeroPlaceholder(theme),
                    const SizedBox(height: 24),
                    const SkeletonCard(),
                    const SizedBox(height: 16),
                    const SkeletonCard(),
                  ],
                );
              }

              if (snapshot.hasError) {
                return _buildErrorState(
                    theme, '${snapshot.error}'.replaceAll('Exception: ', ''));
              }

              final campaigns = snapshot.data ?? [];
              final activeCount =
                  campaigns.where((c) => c.status == 'active').length;
              final totalVotes =
                  campaigns.fold<int>(0, (sum, c) => sum + c.totalVotes);

              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                children: [
                  // 1. Hero Header & Stats Banner
                  _buildHeroHeader(
                    theme: theme,
                    activeCount: activeCount,
                    totalCampaigns: campaigns.length,
                    totalVotes: totalVotes,
                  ),
                  const SizedBox(height: 24),

                  // 2. Liste des campagnes organisateur ou Empty State
                  if (campaigns.isEmpty)
                    _buildEmptyState(theme)
                  else
                    ...campaigns.map((camp) => Padding(
                          padding: const EdgeInsets.only(bottom: 20),
                          child: _buildOrganizerCard(theme, camp),
                        )),
                ],
              );
            },
          ),
        );
      },
    );
  }

  // --- VUE INVITÉ (NON CONNECTÉ) ---
  Widget _buildGuestView(ThemeData theme) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Container(
          padding: const EdgeInsets.all(32.0),
          decoration: BoxDecoration(
            color: theme.colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: theme.colorScheme.outline),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: Icon(Icons.bar_chart_rounded,
                    size: 36, color: theme.colorScheme.primary),
              ),
              const SizedBox(height: 20),
              Text(
                'Espace Organisateur',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: theme.colorScheme.onSurface,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Connectez-vous pour piloter vos campagnes, ajouter des candidats et suivre les votes en direct.',
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 13,
                    height: 1.5,
                    color: theme.colorScheme.onSurfaceVariant),
              ),
              const SizedBox(height: 28),
              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    final res = await Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const LoginPage()),
                    );
                    if (res == true) _loadCampaigns();
                  },
                  icon: const Icon(Icons.login_rounded, size: 18),
                  label: const Text('Se Connecter',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.colorScheme.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                height: 46,
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final res = await Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const SignupPage()),
                    );
                    if (res == true) _loadCampaigns();
                  },
                  icon: const Icon(Icons.person_add_alt_1_rounded, size: 18),
                  label: const Text('Créer un compte',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: theme.colorScheme.onSurface,
                    side: BorderSide(color: theme.colorScheme.outline),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // --- HERO HEADER & STATS BANNER ---
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
            'Gestion de vos Campagnes',
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: theme.colorScheme.onSurface,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Supervisez vos scrutins en temps réel, lancez de nouveaux votes monétisés et suivez la collecte par Mobile Money en toute simplicité.',
            style: TextStyle(
              fontSize: 12,
              height: 1.5,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 16),

          // Bouton Créer une campagne
          ElevatedButton.icon(
            onPressed: () async {
              final createdId = await Navigator.of(context).push<int>(
                  MaterialPageRoute(
                      builder: (_) => const CreateCampaignPage()));
              if (createdId != null && mounted) _loadCampaigns();
            },
            icon: const Icon(Icons.add_rounded, size: 18),
            label: const Text('Nouvelle Campagne',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            style: ElevatedButton.styleFrom(
              backgroundColor: theme.colorScheme.primary,
              foregroundColor: Colors.white,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 18),

          // 3 Cartes de Statistiques
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
                  label: 'CRÉÉES',
                  value: totalCampaigns.toString(),
                  valueColor: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildStatItem(
                  theme: theme,
                  label: 'VOTES REÇUS',
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

  // --- CARTE DE CAMPAGNE ORGANISATEUR ---
  Widget _buildOrganizerCard(ThemeData theme, Campaign campaign) {
    final status = campaign.status;
    final isActive = status == 'active';
    final isDraft = status == 'draft';
    final statusLabel = switch (status) {
      'active' => 'En direct',
      'draft' => 'Brouillon',
      'suspended' => 'Suspendue',
      'pending_review' => 'En validation',
      'archived' => 'Archivée',
      _ => 'Terminée',
    };
    final statusColor = isActive
        ? AppColors.greenAccent
        : isDraft
            ? AppColors.orangeAccent
            : status == 'suspended'
                ? AppColors.redAccent
                : theme.colorScheme.onSurfaceVariant;

    String? imageUrl = campaign.imageUrl;
    if (imageUrl != null && imageUrl.startsWith('/')) {
      imageUrl = '$kBaseUrl$imageUrl';
    }

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: theme.colorScheme.outline),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image & Badge Statut
          Stack(
            children: [
              Container(
                height: 180,
                width: double.infinity,
                color: theme.colorScheme.surfaceContainerHighest,
                child: imageUrl != null && imageUrl.isNotEmpty
                    ? Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => _buildNoImage(theme),
                      )
                    : _buildNoImage(theme),
              ),
              Positioned(
                top: 12,
                right: 12,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surface.withOpacity(0.92),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: theme.colorScheme.outline),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (isActive) ...[
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: AppColors.greenAccent,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                      ],
                      Text(
                        statusLabel,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: statusColor,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),

          // Informations Textuelles
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  campaign.organizerName ?? 'Comité Organisateur',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: theme.colorScheme.primary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  campaign.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: theme.colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  campaign.description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.4,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                if (status == 'suspended') ...[
                  const SizedBox(height: 8),
                  Text(
                    'Campagne suspendue : ${campaign.suspensionReason ?? 'contactez le support pour le motif.'}',
                    style: const TextStyle(
                        fontSize: 11, color: AppColors.redAccent),
                  ),
                ],
                const SizedBox(height: 16),
                Divider(color: theme.colorScheme.outline, height: 1),
                const SizedBox(height: 12),

                // Statistiques Candidats & Votes
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.groups_outlined,
                          size: 16,
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          '${campaign.candidateCount} candidat${campaign.candidateCount > 1 ? 's' : ''}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                    Row(
                      children: [
                        Icon(
                          Icons.check_circle_rounded,
                          size: 16,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${_formatNumber(campaign.totalVotes)} votes',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                // Boutons d'actions : Gérer & Page Publique
                Row(
                  children: [
                    Expanded(
                      child: SizedBox(
                        height: 40,
                        child: ElevatedButton.icon(
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) =>
                                    CampaignManagePage(campaignId: campaign.id),
                              ),
                            );
                          },
                          icon: const Icon(Icons.settings_outlined, size: 16),
                          label: const Text('Gérer',
                              style: TextStyle(
                                  fontSize: 12, fontWeight: FontWeight.bold)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: theme.colorScheme.primary,
                            foregroundColor: Colors.white,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: SizedBox(
                        height: 40,
                        child: OutlinedButton.icon(
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) =>
                                    CampaignDetailPage(campaignId: campaign.id),
                              ),
                            );
                          },
                          icon: const Icon(Icons.open_in_new_rounded, size: 15),
                          label: const Text('Page Publique',
                              style: TextStyle(
                                  fontSize: 11, fontWeight: FontWeight.bold)),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: theme.colorScheme.onSurface,
                            side: BorderSide(color: theme.colorScheme.outline),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                    ),
                    if (isDraft) ...[
                      const SizedBox(width: 4),
                      IconButton(
                        onPressed: () => _confirmDeleteCampaign(campaign),
                        tooltip: 'Supprimer le brouillon',
                        icon: const Icon(Icons.delete_outline_rounded),
                        color: AppColors.redAccent,
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDeleteCampaign(Campaign campaign) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer le brouillon ?'),
        content: Text(
            'La campagne « ${campaign.title} » sera supprimée définitivement.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Supprimer')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await _campaignService.deleteCampaign(campaign.id);
      if (mounted) _loadCampaigns();
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
          backgroundColor: AppColors.redAccent));
    }
  }

  Widget _buildNoImage(ThemeData theme) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(
          Icons.image_not_supported_outlined,
          size: 36,
          color: theme.colorScheme.onSurfaceVariant.withOpacity(0.5),
        ),
        const SizedBox(height: 4),
        Text(
          'Aucune image',
          style: TextStyle(
            fontSize: 11,
            color: theme.colorScheme.onSurfaceVariant.withOpacity(0.7),
          ),
        ),
      ],
    );
  }

  Widget _buildHeroPlaceholder(ThemeData theme) {
    return Container(
      height: 180,
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
                child: const Icon(Icons.error_outline_rounded,
                    color: AppColors.redAccent, size: 32),
              ),
              const SizedBox(height: 14),
              Text(
                'Impossible de charger vos campagnes',
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
                style: TextStyle(
                    fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
              ),
              const SizedBox(height: 18),
              ElevatedButton.icon(
                onPressed: _loadCampaigns,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Réessayer'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: theme.colorScheme.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
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
            'Aucune campagne créée',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Vous n\'avez pas encore configuré de scrutin de vote. Créez et personnalisez votre première campagne en quelques clics.',
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
