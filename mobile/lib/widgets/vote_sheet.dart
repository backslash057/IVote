import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/campaign_detail.dart';
import '../models/user.dart';
import '../services/api_client.dart';
import '../services/campaign_service.dart';
import '../theme.dart';
import '../utils/price.dart';

enum _VoteStep { form, ussd, loading, success }

class VoteSheet extends StatefulWidget {
  final CampaignDetail campaign;
  final Candidate candidate;
  final int pricePerVote;

  const VoteSheet({
    super.key,
    required this.campaign,
    required this.candidate,
    required this.pricePerVote,
  });

  @override
  State<VoteSheet> createState() => _VoteSheetState();
}

class _VoteSheetState extends State<VoteSheet> {
  final CampaignService _service = CampaignService();

  _VoteStep _step = _VoteStep.form;
  int _votes = 10;
  String _paymentMethod = 'mtn_momo';
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _nameController = TextEditingController();
  VoteRecord? _result;
  String? _error;

  int get _price => votePrice(_votes, widget.pricePerVote);

  @override
  void dispose() {
    _phoneController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  void _setVotes(int value) {
    setState(() {
      _votes = value.clamp(1, 500);
    });
  }

  Future<void> _submitVote() async {
    setState(() {
      _step = _VoteStep.loading;
      _error = null;
    });

    try {
      final result = await _service.recordVote(
        widget.campaign.id,
        candidateId: widget.candidate.id,
        voteCount: _votes,
        paymentMethod: _paymentMethod,
      );
      if (!mounted) return;
      setState(() {
        _result = result;
        _step = _VoteStep.success;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _step = _VoteStep.ussd;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        constraints: const BoxConstraints(maxHeight: 620),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _draggedHandle(),
            const SizedBox(height: 12),
            _buildStep(context),
          ],
        ),
      ),
    );
  }

  Widget _draggedHandle() {
    return Center(
      child: Container(
        width: 40,
        height: 4,
        decoration: BoxDecoration(
          color: AppColors.surfaceAlt,
          borderRadius: BorderRadius.circular(99),
        ),
      ),
    );
  }

  Widget _buildStep(BuildContext context) {
    switch (_step) {
      case _VoteStep.form:
        return _buildForm(context);
      case _VoteStep.ussd:
        return _buildUssd(context);
      case _VoteStep.loading:
        return _buildLoading();
      case _VoteStep.success:
        return _buildSuccess(context);
    }
  }

  Widget _header(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
      ],
    );
  }

  Widget _buildForm(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _header(
          'Voter pour ${widget.candidate.name}',
          'N° ${widget.candidate.number} · ${widget.campaign.title}',
        ),
        const SizedBox(height: 16),
        Text(
          'Nombre de votes',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: Colors.grey.shade300,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [1, 10, 20, 50, 100].map((v) {
            final selected = _votes == v;
            return ChoiceChip(
              label: Text('$v'),
              selected: selected,
              backgroundColor: AppColors.surfaceAlt,
              selectedColor: AppColors.emerald,
              labelStyle: TextStyle(
                color: selected ? Colors.white : AppColors.textMuted,
                fontWeight: FontWeight.bold,
              ),
              onSelected: (_) => _setVotes(v),
            );
          }).toList(),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                maxLength: 15,
                decoration: const InputDecoration(
                  labelText: 'Numéro Mobile Money',
                  counterText: '',
                  hintText: '6XX XX XX XX',
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: TextField(
                controller: _nameController,
                decoration: const InputDecoration(
                  labelText: 'Nom (optionnel)',
                  counterText: '',
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 14),
        Text(
          'Mode de paiement',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: Colors.grey.shade300,
          ),
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            _paymentOption('mtn_momo', 'MTN MoMo'),
            const SizedBox(width: 10),
            _paymentOption('orange_money', 'Orange Money'),
          ],
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '$_votes votes',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    '${((1 - discountFor(_votes)) * 100).round()}% offerts',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
              Text(
                formatFcfa(_price),
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                  color: AppColors.amber,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Paiement simulé : aucune transaction ne sera réellement débitée pour le moment.',
          style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
        ),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: () {
            if (_phoneController.text.trim().length < 8) {
              _showError('Veuillez renseigner un numéro Mobile Money valide.');
              return;
            }
            setState(() {
              _error = null;
              _step = _VoteStep.ussd;
            });
          },
          child: const Text('Confirmer le paiement'),
        ),
      ],
    );
  }

  Widget _paymentOption(String method, String label) {
    final selected = _paymentMethod == method;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => setState(() => _paymentMethod = method),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? AppColors.emerald : AppColors.surfaceAlt,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected ? AppColors.emeraldLight : AppColors.border,
            ),
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: selected ? Colors.white : AppColors.textMuted,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildUssd(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _header('Valider sur votre téléphone', 'Simulation de la popup USSD'),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: [
              Icon(
                _paymentMethod == 'mtn_momo'
                    ? Icons.cell_tower
                    : Icons.phone_android,
                color: AppColors.emeraldLight,
                size: 40,
              ),
              const SizedBox(height: 8),
              Text(
                'POPUP ${_paymentMethod == 'mtn_momo' ? 'MTN_MOMO' : 'ORANGE_MONEY'}',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 16),
              _ussdRow('Numéro', _phoneController.text.trim()),
              _ussdRow('Votes', '$_votes'),
              _ussdRow('Montant', formatFcfa(_price)),
              _ussdRow('Candidat', widget.candidate.name),
            ],
          ),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(top: 10),
            child: Text(
              _error!,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 12, color: AppColors.danger),
            ),
          ),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _submitVote,
          child: const Text("J'ai validé le paiement"),
        ),
        const SizedBox(height: 8),
        TextButton(
          onPressed: () => setState(() {
            _error = null;
            _step = _VoteStep.form;
          }),
          child: const Text(
            'Modifier',
            style: TextStyle(color: AppColors.textSecondary),
          ),
        ),
      ],
    );
  }

  Widget _ussdRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoading() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 40),
      child: Center(
        child: CircularProgressIndicator(color: AppColors.emerald),
      ),
    );
  }

  Widget _buildSuccess(BuildContext context) {
    final result = _result;
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Icon(Icons.check_circle, color: AppColors.emerald, size: 56),
        const SizedBox(height: 10),
        const Center(
          child: Text(
            'Vote enregistré !',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: [
              _ussdRow('Votes ajoutés', '+${result?.votesAdded ?? _votes}'),
              _ussdRow('Montant', formatFcfa(result?.amountFcfa ?? _price)),
              _ussdRow('Référence', result?.transactionRef ?? '--'),
            ],
          ),
        ),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: () => Navigator.of(context).pop(true),
          child: const Text('Terminé'),
        ),
      ],
    );
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}

Future<bool?> showVoteSheet(BuildContext context, {
  required CampaignDetail campaign,
  required Candidate candidate,
}) {
  return showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: AppColors.surfaceAlt,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (context) => VoteSheet(
      campaign: campaign,
      candidate: candidate,
      pricePerVote: campaign.pricePerVote,
    ),
  );
}