import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/campaign.dart';
import '../models/dashboard.dart';
import '../services/api_client.dart';
import '../services/campaign_service.dart';
import '../theme.dart';
import '../utils/price.dart';

class CampaignAdminPage extends StatefulWidget {
  final Campaign campaign;

  const CampaignAdminPage({super.key, required this.campaign});

  @override
  State<CampaignAdminPage> createState() => _CampaignAdminPageState();
}

class _CampaignAdminPageState extends State<CampaignAdminPage> {
  final CampaignService _service = CampaignService();
  late Future<DashboardData> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    _future = _service.dashboard(widget.campaign.id);
  }

  void _reload() => setState(_load);

  Future<void> _runAction(Future<void> Function() action) async {
    try {
      await action();
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: Text(
          widget.campaign.title,
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          IconButton(
            tooltip: 'Actualiser',
            icon: const Icon(Icons.refresh),
            onPressed: _reload,
          ),
        ],
      ),
      body: FutureBuilder<DashboardData>(
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
          final data = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _StatsGrid(data: data),
              const SizedBox(height: 12),
              _actionsRow(),
              const SizedBox(height: 20),
              _Section(
                title: 'Candidats',
                trailing: TextButton.icon(
                  onPressed: () => _addCandidateDialog(),
                  icon: const Icon(Icons.add, size: 16),
                  label: const Text('Ajouter'),
                ),
                child: data.candidates.isEmpty
                    ? _empty('Aucun candidat pour le moment.')
                    : Column(
                        children: data.candidates
                            .map((c) => _candidateRow(c))
                            .toList(),
                      ),
              ),
              const SizedBox(height: 20),
              _Section(
                title: 'Transactions',
                child: data.transactions.isEmpty
                    ? _empty('Aucune transaction.')
                    : Column(
                        children: data.transactions
                            .map((t) => _transactionRow(t))
                            .toList(),
                      ),
              ),
              const SizedBox(height: 20),
              _Section(
                title: 'Retraits',
                trailing: TextButton.icon(
                  onPressed: () => _addPayoutDialog(data.availableBalance),
                  icon: const Icon(Icons.add, size: 16),
                  label: const Text('Demander'),
                ),
                child: data.payoutRequests.isEmpty
                    ? _empty('Aucune demande de retrait.')
                    : Column(
                        children: data.payoutRequests
                            .map((p) => _payoutRow(p))
                            .toList(),
                      ),
              ),
              const SizedBox(height: 20),
            ],
          );
        },
      ),
    );
  }

  Widget _actionsRow() {
    final isActive = widget.campaign.isActive;
    final isDraft = widget.campaign.isDraft;
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        if (isDraft)
          _actionButton(
            label: 'Publier',
            icon: Icons.public,
            color: AppColors.emerald,
            onTap: () => _runAction(() => _service.setStatus(widget.campaign.id, 'publish')),
          ),
        if (isActive)
          _actionButton(
            label: 'Clôturer',
            icon: Icons.lock_clock,
            color: AppColors.amberDark,
            onTap: () => _runAction(() => _service.setStatus(widget.campaign.id, 'close')),
          ),
        _actionButton(
          label: 'Modifier',
          icon: Icons.edit_outlined,
          color: AppColors.surfaceAlt,
          onTap: _editCampaignDialog,
        ),
      ],
    );
  }

  Widget _actionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Material(
      color: color,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, color: Colors.white, size: 16),
              const SizedBox(width: 6),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _candidateRow(AdminCandidate candidate) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'N° ${candidate.number} · ${candidate.name}',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                    fontSize: 13,
                  ),
                ),
                Text(
                  candidate.categoryName,
                  style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${formatCount(candidate.votes)} votes',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: AppColors.amber,
                ),
              ),
              Text(
                formatFcfa(candidate.revenue),
                style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _transactionRow(TransactionRecord t) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          const Icon(Icons.receipt_long, color: AppColors.textSecondary, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${t.candidateName} · +${t.voteCount} votes',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                    fontSize: 13,
                  ),
                ),
                Text(
                  '${t.transactionRef} · ${t.paymentMethod}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
          Text(
            formatFcfa(t.amount),
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: AppColors.emeraldLight,
            ),
          ),
        ],
      ),
    );
  }

  Widget _payoutRow(PayoutRequest p) {
    final statusColor = switch (p.status) {
      'pending' => AppColors.amber,
      'completed' => AppColors.emerald,
      'rejected' => AppColors.danger,
      _ => AppColors.textSecondary,
    };
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${p.accountHolder} · ${p.walletNumber}',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                    fontSize: 13,
                  ),
                ),
                Text(
                  '#PO-${p.id} · ${p.paymentMethod}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                formatFcfa(p.amount),
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              Text(
                p.statusLabel,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: statusColor,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _empty(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Text(
        message,
        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
      ),
    );
  }

  // ---- Dialogues ----

  Future<void> _editCampaignDialog() async {
    final title = TextEditingController(text: widget.campaign.title);
    final description = TextEditingController(text: widget.campaign.description);
    final price = TextEditingController(text: '${widget.campaign.pricePerVote}');

    await showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppColors.surface,
        title: const Text('Modifier la campagne'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: title,
              decoration: const InputDecoration(labelText: 'Titre'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: description,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Description'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: price,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Prix par vote (FCFA)'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              final p = int.tryParse(price.text.trim());
              if (p == null || p <= 0) return;
              Navigator.of(context).pop();
              await _runAction(() => _service.updateCampaign(
                    widget.campaign.id,
                    title: title.text.trim(),
                    description: description.text.trim(),
                    pricePerVote: p,
                  ));
            },
            child: const Text('Enregistrer'),
          ),
        ],
      ),
    );
  }

  Future<void> _addCandidateDialog() async {
    // Charge les catégories existantes via le dashboard pour la sélection.
    DashboardData data;
    try {
      data = await _future;
    } catch (_) {
      return;
    }
    if (!mounted) return;
    final categoryNames =
        data.candidates.map((c) => c.categoryName).toSet().toList();

    final name = TextEditingController();
    final number = TextEditingController();
    final age = TextEditingController();
    String? selectedCategory = categoryNames.isNotEmpty ? categoryNames.first : null;
    String newCategoryName = '';

    await showDialog<void>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          backgroundColor: AppColors.surface,
          title: const Text('Nouveau candidat'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: name,
                  decoration: const InputDecoration(labelText: 'Nom'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: number,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(labelText: 'N° de dossard'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: age,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(labelText: 'Âge (optionnel)'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: selectedCategory,
                  decoration: const InputDecoration(labelText: 'Catégorie'),
                  items: [
                    ...categoryNames.map(
                      (c) => DropdownMenuItem(value: c, child: Text(c)),
                    ),
                    const DropdownMenuItem(
                      value: '__new__',
                      child: Text('+ Nouvelle catégorie'),
                    ),
                  ],
                  onChanged: (value) => setState(() {
                    selectedCategory = value;
                    if (value == '__new__') newCategoryName = '';
                  }),
                ),
                if (selectedCategory == '__new__') ...[
                  const SizedBox(height: 12),
                  TextField(
                    decoration: const InputDecoration(
                      labelText: 'Nom de la nouvelle catégorie',
                    ),
                    onChanged: (v) => newCategoryName = v,
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () async {
                final isNewCategory = selectedCategory == '__new__';
                if (name.text.trim().isEmpty || number.text.trim().isEmpty) {
                  return;
                }
                Navigator.of(context).pop();
                await _runAction(() => _service.addCandidate(
                      widget.campaign.id,
                      name: name.text.trim(),
                      candidateNumber: int.parse(number.text.trim()),
                      age: int.tryParse(age.text.trim()),
                      categoryName:
                          (!isNewCategory && selectedCategory != null)
                              ? selectedCategory
                              : null,
                      newCategoryName: isNewCategory ? newCategoryName.trim() : '',
                    ));
              },
              child: const Text('Ajouter'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _addPayoutDialog(int availableBalance) async {
    final amount = TextEditingController();
    final holder = TextEditingController();
    final wallet = TextEditingController();
    String method = 'mtn_momo';

    await showDialog<void>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          backgroundColor: AppColors.surface,
          title: const Text('Demande de retrait'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Solde disponible : ${formatFcfa(availableBalance)}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: AppColors.emeraldLight,
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: amount,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(labelText: 'Montant (FCFA)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: holder,
                  decoration: const InputDecoration(labelText: 'Titulaire du compte'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: wallet,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(labelText: 'N° Mobile Money'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: method,
                  decoration: const InputDecoration(labelText: 'Mode de paiement'),
                  items: const [
                    DropdownMenuItem(value: 'mtn_momo', child: Text('MTN MoMo')),
                    DropdownMenuItem(value: 'orange_money', child: Text('Orange Money')),
                  ],
                  onChanged: (value) => setState(() => method = value ?? method),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () async {
                final a = int.tryParse(amount.text.trim());
                if (a == null || a <= 0) return;
                Navigator.of(context).pop();
                await _runAction(() => _service.createPayout(
                      widget.campaign.id,
                      amount: a,
                      paymentMethod: method,
                      accountHolder: holder.text.trim(),
                      walletNumber: wallet.text.trim(),
                    ));
              },
              child: const Text('Envoyer'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  final DashboardData data;

  const _StatsGrid({required this.data});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _cell(formatCount(data.totalVotes), 'Votes', Colors.white),
        const SizedBox(width: 10),
        _cell(formatFcfa(data.totalRevenue), 'Revenu', AppColors.amber),
        const SizedBox(width: 10),
        _cell(formatFcfa(data.platformFee), 'Commission', AppColors.textMuted),
        const SizedBox(width: 10),
        _cell(formatFcfa(data.availableBalance), 'Solde', AppColors.emeraldLight),
      ],
    );
  }

  Widget _cell(String value, String label, Color valueColor) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 4),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          children: [
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w900,
                color: valueColor,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
            ),
          ],
        ),
      ),
    );
  }
}

class _Section extends StatelessWidget {
  final String title;
  final Widget child;
  final Widget? trailing;

  const _Section({required this.title, required this.child, this.trailing});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              if (trailing != null) trailing!,
            ],
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }
}