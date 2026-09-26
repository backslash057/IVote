import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/campaign_service.dart';
import '../theme/app_theme.dart';
import 'auth/login_page.dart';
import 'auth/signup_page.dart';

class RevenuesPage extends StatefulWidget {
  const RevenuesPage({super.key});

  @override
  State<RevenuesPage> createState() => _RevenuesPageState();
}

class _RevenuesPageState extends State<RevenuesPage> {
  final CampaignService _service = CampaignService();
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    if (AuthService().isAuthenticated) {
      setState(() {
        _future = _service.fetchRevenues();
      });
    }
  }

  String _formatNumber(num number) {
    return number.toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (Match m) => '${m[1]} ',
        );
  }

  // --- POPUP DEMANDE DE RETRAIT ---
  void _showPayoutModal(Map<String, dynamic> campaign) {
    final amountCtrl = TextEditingController();
    final holderCtrl = TextEditingController();
    final walletCtrl = TextEditingController();
    String paymentMethod = 'mtn_momo';
    bool isProcessing = false;
    final available = (campaign['available_balance'] as int?) ?? 0;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final theme = Theme.of(ctx);

          return Container(
            padding: EdgeInsets.only(
              left: 24,
              right: 24,
              top: 24,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            ),
            child: SingleChildScrollView(
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
                  const SizedBox(height: 16),
                  Text(
                    'Demande de Retrait',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
                  ),
                  Text(
                    campaign['title'] ?? '',
                    style: TextStyle(fontSize: 12, color: theme.colorScheme.primary, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 14),

                  // Solde disponible
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.greenAccent.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Solde Retirable :', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        Text('${_formatNumber(available)} FCFA', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.greenAccent)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Montant
                  Text('Montant à retirer (FCFA) *', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: amountCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      hintText: 'Ex: 25000',
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Méthode de paiement
                  Text('Opérateur Mobile Money *', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () => setModalState(() => paymentMethod = 'mtn_momo'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: paymentMethod == 'mtn_momo' ? theme.colorScheme.primary.withOpacity(0.1) : theme.colorScheme.surfaceContainerHighest,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: paymentMethod == 'mtn_momo' ? theme.colorScheme.primary : theme.colorScheme.outline),
                            ),
                            alignment: Alignment.center,
                            child: const Text('MTN MoMo', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: InkWell(
                          onTap: () => setModalState(() => paymentMethod = 'orange_money'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: paymentMethod == 'orange_money' ? theme.colorScheme.primary.withOpacity(0.1) : theme.colorScheme.surfaceContainerHighest,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: paymentMethod == 'orange_money' ? theme.colorScheme.primary : theme.colorScheme.outline),
                            ),
                            alignment: Alignment.center,
                            child: const Text('Orange Money', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  Text('Nom complet du titulaire du compte *', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: holderCtrl,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      hintText: 'Ex: Jean Dupont',
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),

                  Text('Numéro de téléphone du compte *', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 4),
                  TextField(
                    controller: walletCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: theme.colorScheme.surfaceContainerHighest,
                      border: InputBorder.none,
                      hintText: 'Ex: 6XXXXXXXX',
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Remarque
                  Text(
                    'Remarque : Les virements sont traités sous 24h ouvrées. La commission plateforme de 10% a déjà été déduite.',
                    style: TextStyle(fontSize: 10, color: theme.colorScheme.onSurfaceVariant, height: 1.4),
                  ),
                  const SizedBox(height: 18),

                  SizedBox(
                    height: 44,
                    child: ElevatedButton(
                      onPressed: isProcessing
                          ? null
                          : () async {
                              final amount = int.tryParse(amountCtrl.text.trim()) ?? 0;
                              final holder = holderCtrl.text.trim();
                              final wallet = walletCtrl.text.trim();

                              if (amount <= 0 || holder.isEmpty || wallet.isEmpty) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Veuillez remplir tous les champs.')),
                                );
                                return;
                              }

                              if (amount > available) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Montant supérieur au solde retirable.')),
                                );
                                return;
                              }

                              setModalState(() => isProcessing = true);
                              try {
                                final res = await _service.requestPayout(
                                  campaignId: campaign['campaign_id'],
                                  amount: amount,
                                  paymentMethod: paymentMethod,
                                  accountHolder: holder,
                                  walletNumber: wallet,
                                );
                                if (!mounted) return;
                                Navigator.of(ctx).pop();
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(res['message'] ?? 'Demande de retrait initiée !'),
                                    backgroundColor: AppColors.greenAccent,
                                    behavior: SnackBarBehavior.floating,
                                  ),
                                );
                                _load();
                              } catch (e) {
                                if (!mounted) return;
                                Navigator.of(ctx).pop();
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(e.toString().replaceAll('Exception: ', '')),
                                    backgroundColor: AppColors.redAccent,
                                    behavior: SnackBarBehavior.floating,
                                  ),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: isProcessing
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Valider le Retrait', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
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
          onRefresh: () async => _load(),
          child: FutureBuilder<Map<String, dynamic>>(
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
                        Text('${snapshot.error}'.replaceAll('Exception: ', ''), textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton(onPressed: _load, child: const Text('Réessayer')),
                      ],
                    ),
                  ),
                );
              }

              final data = snapshot.data!;
              final globalRevenue = data['global_revenue'] ?? 0;
              final globalAvailable = data['global_available'] ?? 0;
              final campaigns = (data['campaigns'] as List<dynamic>?) ?? [];

              return ListView(
                padding: const EdgeInsets.all(16.0),
                children: [
                  // Synthèse Financière Globale
                  Container(
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
                          'SOLDE DISPONIBLE AU RETRAIT',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurfaceVariant, letterSpacing: 0.5),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${_formatNumber(globalAvailable)} FCFA',
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.greenAccent),
                        ),
                        const SizedBox(height: 10),
                        Divider(color: theme.colorScheme.outline),
                        const SizedBox(height: 6),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Revenus Bruts Collectés :', style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant)),
                            Text('${_formatNumber(globalRevenue)} FCFA', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  Text(
                    'Vos Campagnes & Retraits',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
                  ),
                  const SizedBox(height: 12),

                  if (campaigns.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(28),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Center(
                        child: Text(
                          'Aucune campagne pour le moment.',
                          style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
                        ),
                      ),
                    )
                  else
                    ...campaigns.map((camp) => _buildCompactCampaignCard(theme, camp)),
                ],
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildCompactCampaignCard(ThemeData theme, Map<String, dynamic> camp) {
    final available = (camp['available_balance'] as int?) ?? 0;
    final totalRev = (camp['total_revenue'] as int?) ?? 0;
    final payouts = (camp['payouts'] as List<dynamic>?) ?? [];

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: const Border(),
        collapsedShape: const Border(),
        title: Text(
          camp['title'] ?? '',
          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6.0),
          child: Row(
            children: [
              Text(
                'Retirable : ',
                style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
              ),
              Text(
                '${_formatNumber(available)} F',
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.greenAccent),
              ),
              const Spacer(),
              Text(
                'Brut: ${_formatNumber(totalRev)} F',
                style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant),
              ),
            ],
          ),
        ),
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                ElevatedButton.icon(
                  onPressed: available > 0 ? () => _showPayoutModal(camp) : null,
                  icon: const Icon(Icons.account_balance_wallet_rounded, size: 16),
                  label: const Text('Demander un retrait', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.greenAccent,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Historique des retraits (${payouts.length})',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: theme.colorScheme.onSurface),
                ),
                const SizedBox(height: 6),
                if (payouts.isEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8.0),
                    child: Text('Aucune demande de retrait effectuée.', style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant)),
                  )
                else
                  ...payouts.map((p) {
                    final status = p['status'] ?? 'pending';
                    Color statusColor = AppColors.orangeAccent;
                    String statusLabel = 'En attente';
                    if (status == 'completed') {
                      statusColor = AppColors.greenAccent;
                      statusLabel = 'Validé';
                    } else if (status == 'rejected') {
                      statusColor = AppColors.redAccent;
                      statusLabel = 'Rejeté';
                    }

                    return Container(
                      margin: const EdgeInsets.only(bottom: 6),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${_formatNumber(p['amount_fcfa'] ?? 0)} FCFA', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                              Text('${p['payment_method'] == 'mtn_momo' ? 'MTN MoMo' : 'Orange Money'} • ${p['wallet_number']}', style: TextStyle(fontSize: 10, color: theme.colorScheme.onSurfaceVariant)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: statusColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(statusLabel, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusColor)),
                          ),
                        ],
                      ),
                    );
                  }),
                const SizedBox(height: 8),
              ],
            ),
          ),
        ],
      ),
    );
  }

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
                child: Icon(Icons.account_balance_wallet_rounded, size: 36, color: theme.colorScheme.primary),
              ),
              const SizedBox(height: 20),
              Text(
                'Revenus & Retraits',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: theme.colorScheme.onSurface),
              ),
              const SizedBox(height: 8),
              Text(
                'Connectez-vous pour consulter vos gains et effectuer des demandes de virement Mobile Money.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 13, height: 1.5, color: theme.colorScheme.onSurfaceVariant),
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
                    if (res == true) _load();
                  },
                  icon: const Icon(Icons.login_rounded, size: 18),
                  label: const Text('Se Connecter', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: theme.colorScheme.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
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
                    if (res == true) _load();
                  },
                  icon: const Icon(Icons.person_add_alt_1_rounded, size: 18),
                  label: const Text('Créer un compte', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: theme.colorScheme.onSurface,
                    side: BorderSide(color: theme.colorScheme.outline),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}