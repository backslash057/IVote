import 'package:flutter/material.dart';
import '../models/campaign.dart';

class HeroSection extends StatelessWidget {
  final List<Campaign> campaigns;
  final VoidCallback? onAction;

  const HeroSection({super.key, required this.campaigns, this.onAction});

  @override
  Widget build(BuildContext context) {
    final activeCount = campaigns.where((c) => c.isActive).length;
    final totalVotes = campaigns.fold<int>(0, (sum, c) => sum + c.totalVotes);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A), // slate-900
        borderRadius: BorderRadius.circular(24), // rounded-3xl
        border: Border.all(color: const Color(0xFF1E293B)), // border-slate-800
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Toutes les Campagnes de Vote',
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Exprimez votre voix en toute transparence avec un règlement direct par Orange Money ou MTN Mobile Money.',
            style: TextStyle(
              fontSize: 12,
              color: Color(0xFF94A3B8), // text-slate-400
              height: 1.4,
            ),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: _buildMetricBox(
                  count: '$activeCount',
                  label: 'Campagnes actives',
                  valueColor: const Color(0xFF34D399), // emerald-400
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMetricBox(
                  count: '${campaigns.length}',
                  label: 'Campagnes',
                  valueColor: const Color(0xFFFBBF24), // amber-400
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildMetricBox(
                  count: _formatNumber(totalVotes),
                  label: 'Total Votes',
                  valueColor: Colors.white,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetricBox({
    required String count,
    required String label,
    required Color valueColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
      decoration: BoxDecoration(
        color: const Color(0xFF020617).withOpacity(0.5), // bg-slate-950/50
        borderRadius: BorderRadius.circular(16), // rounded-2xl
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: Column(
        children: [
          Text(
            count,
            style: TextStyle(
              fontFamily: 'monospace',
              fontSize: 18,
              fontWeight: FontWeight.w900,
              color: valueColor,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 10,
              color: Color(0xFF94A3B8), // slate-400
            ),
          ),
        ],
      ),
    );
  }

  String _formatNumber(int n) {
    return n.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]} ',
    );
  }
}