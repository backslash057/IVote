import 'package:flutter/material.dart';

import '../models/campaign_detail.dart';
import '../services/campaign_service.dart';
import '../theme.dart';
import '../utils/price.dart';
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
    _future = _service.fetchCampaignDetail(widget.campaignId);
  }

  void _reload() {
    setState(_load);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: FutureBuilder<CampaignDetail>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(
              body: Center(
                child: CircularProgressIndicator(color: AppColors.emerald),
              ),
            );
          }
          if (snapshot.hasError) {
            return Scaffold(
              appBar: AppBar(title: const Text('Campagne')),
              body: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.cloud_off,
                        color: AppColors.danger, size: 40),
                    const SizedBox(height: 12),
                    Text(
                      '${snapshot.error}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 16),
                    OutlinedButton.icon(
                      onPressed: _reload,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Réessayer'),
                    ),
                  ],
                ),
              ),
            );
          }

          final detail = snapshot.data!;
          return CustomScrollView(
            slivers: [
              SliverAppBar(
                pinned: true,
                expandedHeight: 220,
                backgroundColor: AppColors.surfaceAlt,
                leading: const BackButton(color: Colors.white),
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      _banner(detail),
                      const DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [
                              Colors.transparent,
                              AppColors.bg,
                            ],
                            stops: [0.5, 1.0],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _statusBadge(detail),
                      const SizedBox(height: 12),
                      Text(
                        detail.title,
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: -0.5,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        detail.organizerName,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppColors.emeraldLight,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        detail.description,
                        style: const TextStyle(
                          fontSize: 13,
                          height: 1.5,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _statsRow(detail),
                      const SizedBox(height: 28),
                      if (!detail.isActive)
                        _closedNotice(detail)
                      else
                        ...detail.categories.map(_categorySection),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _banner(CampaignDetail detail) {
    return Image.network(
      detail.imageUrl,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => Container(
        color: AppColors.surfaceAlt,
        alignment: Alignment.center,
        child: const Icon(Icons.how_to_vote,
            color: AppColors.emerald, size: 48),
      ),
    );
  }

  Widget _statusBadge(CampaignDetail detail) {
    final (label, color) = detail.isActive
        ? ('● En Direct', AppColors.emerald)
        : detail.isDraft
            ? ('Brouillon', AppColors.amberDark)
            : ('Terminé', AppColors.surfaceAlt);
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _statsRow(CampaignDetail detail) {
    return Row(
      children: [
        _statBox(formatCount(detail.totalVotes), 'Votes'),
        const SizedBox(width: 10),
        _statBox('${detail.candidateCount}', 'Candidats'),
        const SizedBox(width: 10),
        _statBox(formatFcfa(detail.pricePerVote), 'Prix / vote'),
      ],
    );
  }

  Widget _statBox(String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          children: [
            Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                color: Colors.white,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _closedNotice(CampaignDetail detail) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: const Row(
        children: [
          Icon(Icons.lock_clock, color: AppColors.amberDark),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Les votes sont fermés pour cette campagne. Consultez les résultats ci-dessous.',
              style: TextStyle(fontSize: 13, color: AppColors.textMuted),
            ),
          ),
        ],
      ),
    );
  }

  Widget _categorySection(CampaignCategory category) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                category.name,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              Text(
                '${category.candidates.length} candidat${category.candidates.length > 1 ? 's' : ''}',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
        ...category.candidates.map((candidate) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _CandidateTile(
                candidate: candidate,
                onVote: () => _startVote(candidate),
              ),
            )),
        const SizedBox(height: 16),
      ],
    );
  }

  Future<void> _startVote(Candidate candidate) async {
    final detail = await _future;
    if (!mounted) return;
    final voted = await showVoteSheet(
      context,
      campaign: detail,
      candidate: candidate,
    );
    if (voted == true && mounted) _reload();
  }
}

class _CandidateTile extends StatelessWidget {
  final Candidate candidate;
  final VoidCallback onVote;

  const _CandidateTile({required this.candidate, required this.onVote});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          _avatar(),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'N° ${candidate.number} · ${candidate.name}',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                    fontSize: 14,
                  ),
                ),
                if (candidate.theme != null && candidate.theme!.isNotEmpty)
                  Text(
                    candidate.theme!,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.emeraldLight,
                    ),
                  ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Text(
                      formatCount(candidate.votes),
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        color: AppColors.amber,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'votes · ${candidate.percentage.toStringAsFixed(1)}%',
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                ClipRRect(
                  borderRadius: BorderRadius.circular(99),
                  child: LinearProgressIndicator(
                    value: (candidate.percentage / 100).clamp(0.0, 1.0),
                    minHeight: 5,
                    backgroundColor: AppColors.surfaceAlt,
                    color: AppColors.emerald,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            onPressed: onVote,
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              backgroundColor: AppColors.emerald,
            ),
            child: const Text('Voter', style: TextStyle(fontSize: 12)),
          ),
        ],
      ),
    );
  }

  Widget _avatar() {
    if (candidate.imageUrl.isEmpty) {
      return _initialAvatar();
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Image.network(
        candidate.imageUrl,
        width: 48,
        height: 48,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) => _initialAvatar(),
      ),
    );
  }

  Widget _initialAvatar() {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: AppColors.emerald,
        borderRadius: BorderRadius.circular(12),
      ),
      alignment: Alignment.center,
      child: Text(
        candidate.name.isNotEmpty ? candidate.name[0].toUpperCase() : '?',
        style: const TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: Colors.white,
        ),
      ),
    );
  }
}