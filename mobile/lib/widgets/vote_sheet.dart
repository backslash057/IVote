import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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
  final _phoneController = TextEditingController();
  bool _isProcessing = false;
  String? _errorMessage;

  int get _calculatedPrice {
    final base = widget.campaign.pricePerVote * _voteCount;
    double discount = 1.0;
    if (_voteCount >= 50) {
      discount = 0.8;
    } else if (_voteCount >= 20) {
      discount = 0.85;
    } else if (_voteCount >= 10) {
      discount = 0.9;
    }
    return (base * discount).round();
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  bool get _phoneIsValid {
    final digits = _phoneController.text.replaceAll(RegExp(r'\D'), '');
    if (digits.length == 9) return digits.startsWith('6');
    if (digits.length == 12) return digits.startsWith('2376');
    return false;
  }

  Future<void> _submitVote() async {
    if (!_phoneIsValid) {
      setState(() => _errorMessage =
          'Indiquez le numéro Mobile Money au format 6XXXXXXXX ou 2376XXXXXXXX.');
      return;
    }

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
        phoneNumber: _phoneController.text,
      );

      if (!mounted) return;

      if (res['success'] == true) {
        if (res['pending'] == true) {
          final confirmed = await showModalBottomSheet<bool>(
            context: context,
            isDismissible: false,
            enableDrag: false,
            backgroundColor: Colors.transparent,
            builder: (_) => _PaymentPendingSheet(
                transactionRef: res['transaction_ref']?.toString() ?? ''),
          );
          if (!mounted) return;
          if (confirmed == true) {
            Navigator.of(context).pop(true);
          } else {
            setState(() => _errorMessage =
                'Paiement non confirmé. Le vote n\'a pas été enregistré.');
          }
        } else {
          Navigator.of(context).pop(true);
        }
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

          // Numéro Mobile Money
          Text(
            'Numéro Mobile Money',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(13),
            ],
            decoration: InputDecoration(
              hintText: 'Ex : 679123456',
              prefixText: '+237 ',
              prefixStyle: TextStyle(color: theme.colorScheme.onSurfaceVariant),
              filled: true,
              fillColor: theme.colorScheme.surfaceContainerHighest,
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: theme.colorScheme.outline),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: theme.colorScheme.outline),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: theme.colorScheme.primary),
              ),
            ),
            style: TextStyle(color: theme.colorScheme.onSurface),
          ),
          const SizedBox(height: 16),

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

/// Bottom sheet affichée pendant la confirmation USSD sur le téléphone du
/// votant. Sonde l'API toutes les 3 s jusqu'à confirmation ou échec.
class _PaymentPendingSheet extends StatefulWidget {
  final String transactionRef;

  const _PaymentPendingSheet({required this.transactionRef});

  @override
  State<_PaymentPendingSheet> createState() => _PaymentPendingSheetState();
}

class _PaymentPendingSheetState extends State<_PaymentPendingSheet> {
  static const _pollInterval = Duration(seconds: 3);
  static const _maxPolls = 60;

  Timer? _timer;
  int _pollCount = 0;
  bool _finished = false;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(_pollInterval, (_) => _check());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    if (_finished) return;
    _pollCount++;
    if (_pollCount > _maxPolls) {
      _finish(false);
      return;
    }

    Map<String, dynamic>? data;
    try {
      data = await CampaignService().paymentStatus(widget.transactionRef);
    } catch (_) {
      // Réseau instable : on retente au prochain tick.
      return;
    }
    if (!mounted) return;

    final status = data['status']?.toString().toUpperCase() ?? 'PENDING';
    if (_finished) return;

    if (status == 'SUCCESSFUL') {
      _finish(true);
    } else if (status == 'FAILED' ||
        status == 'EXPIRED' ||
        status == 'CANCELLED') {
      _finish(false);
    }
  }

  void _finish(bool success) {
    if (_finished) return;
    _finished = true;
    _timer?.cancel();
    if (mounted) Navigator.of(context).pop(success);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
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
          const SizedBox(height: 24),
          Center(
            child: Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: theme.colorScheme.primary.withOpacity(0.12),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(
                Icons.phone_in_talk_rounded,
                size: 32,
                color: theme.colorScheme.primary,
              ),
            ),
          ),
          const SizedBox(height: 20),
          Text(
            'Paiement en attente de confirmation',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Validez la demande Mobile Money reçue sur votre téléphone.\n'
            'Le vote sera enregistré automatiquement…',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: theme.colorScheme.onSurfaceVariant),
          ),
          const SizedBox(height: 20),
          const Center(
            child: SizedBox(
              width: 26,
              height: 26,
              child: CircularProgressIndicator(strokeWidth: 2.5),
            ),
          ),
        ],
      ),
    );
  }
}