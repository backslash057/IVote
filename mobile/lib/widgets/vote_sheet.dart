import 'package:flutter/material.dart';
import '../models/campaign.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';

Future<bool?> showVoteSheet(BuildContext context, {
  required CampaignDetail campaign,
  required Candidate candidate,
}) {
  return showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => _VoteBottomSheet(campaign: campaign, candidate: candidate),
  );
}

class _VoteBottomSheet extends StatefulWidget {
  final CampaignDetail campaign;
  final Candidate candidate;

  const _VoteBottomSheet({required this.campaign, required this.candidate});

  @override
  State<_VoteBottomSheet> createState() => _VoteBottomSheetState();
}

class _VoteBottomSheetState extends State<_VoteBottomSheet> {
  int _voteCount = 1;
  String _paymentMethod = 'mtn_momo';
  bool _isProcessing = false;
  String? _errorMessage;

  int get _calculatedPrice {
    final base = widget.campaign.pricePerVote * _voteCount;
    double discount = 1.0;
    if (_voteCount >= 50) discount = 0.8;
    else if (_voteCount >= 20) discount = 0.85;
    else if (_voteCount >= 10) discount = 0.9;
    return (base * discount).round();
  }

  Future<void> _submitVote() async {
    setState(() {
      _isProcessing = true;
      _errorMessage = null;
    });

    try {
      final res = await CampaignService().recordVote(
        campaignId: widget.campaign.id,
        candidateId: widget.candidate.id,
        voteCount: _voteCount,
        paymentMethod: _paymentMethod,
      );

      if (!mounted) return;

      if (res['success'] == true) {
        Navigator.of(context).pop(true);
      } else {
        setState(() => _errorMessage = res['message'] ?? 'Échec du paiement.');
      }
    } catch (e) {
      setState(() => _errorMessage = e.toString().replaceAll('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: theme.colorScheme.outline,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 18),

          Text(
            'Voter pour ${widget.candidate.name}',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Candidat N°${widget.candidate.number} • ${widget.campaign.title}',
            style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
          ),
          const SizedBox(height: 20),

          if (_errorMessage != null) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.redAccent.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.redAccent, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 16),
          ],

          // Sélecteur de nombre de votes
          Text(
            'Nombre de voix',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
          ),
          const SizedBox(height: 8),
          Row(
            children: [1, 5, 10, 20, 50].map((count) {
              final isSelected = _voteCount == count;
              return Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 2.0),
                  child: InkWell(
                    onTap: () => setState(() => _voteCount = count),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: isSelected ? theme.colorScheme.primary : theme.colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: isSelected ? theme.colorScheme.primary : theme.colorScheme.outline,
                        ),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        '$count',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : theme.colorScheme.onSurface,
                        ),
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),

          // Méthode de paiement
          Text(
            'Moyen de paiement',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _buildPaymentTile(
                  label: 'MTN MoMo',
                  id: 'mtn_momo',
                  icon: Icons.phone_android_rounded,
                  theme: theme,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildPaymentTile(
                  label: 'Orange Money',
                  id: 'orange_money',
                  icon: Icons.account_balance_wallet_rounded,
                  theme: theme,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Récapitulatif & Bouton
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: theme.colorScheme.outline),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Total à payer', style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant)),
                    const SizedBox(height: 2),
                    Text(
                      '$_calculatedPrice FCFA',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
                    ),
                  ],
                ),
                ElevatedButton(
                  onPressed: _isProcessing ? null : _submitVote,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.colorScheme.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  ),
                  child: _isProcessing
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('Confirmer le Vote', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentTile({
    required String label,
    required String id,
    required IconData icon,
    required ThemeData theme,
  }) {
    final isSelected = _paymentMethod == id;

    return InkWell(
      onTap: () => setState(() => _paymentMethod = id),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
        decoration: BoxDecoration(
          color: isSelected ? theme.colorScheme.primary.withOpacity(0.1) : theme.colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? theme.colorScheme.primary : theme.colorScheme.outline,
            width: isSelected ? 1.5 : 1.0,
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 16, color: isSelected ? theme.colorScheme.primary : theme.colorScheme.onSurfaceVariant),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: isSelected ? theme.colorScheme.primary : theme.colorScheme.onSurface,
              ),
            ),
          ],
        ),
      ),
    );
  }
}