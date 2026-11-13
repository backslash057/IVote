import 'package:flutter/material.dart';
import '../services/admin_service.dart';
import '../theme/app_theme.dart';
import '../widgets/skeleton_card.dart';

class AdminPage extends StatefulWidget {
  const AdminPage({super.key});

  @override
  State<AdminPage> createState() => _AdminPageState();
}

class _AdminPageState extends State<AdminPage>
    with SingleTickerProviderStateMixin {
  final _service = AdminService();
  late final TabController _tabController;

  late Future<Map<String, dynamic>> _overview;
  late Future<List<dynamic>> _users;
  late Future<List<dynamic>> _campaigns;
  late Future<List<dynamic>> _payouts;
  late Future<List<dynamic>> _settings;

  final _userSearchController = TextEditingController();
  final _campaignSearchController = TextEditingController();
  final _announcementTitle = TextEditingController();
  final _announcementMessage = TextEditingController();

  String _userFilter = 'all';
  String _userQuery = '';
  String _campaignFilter = 'all';
  String _campaignQuery = '';
  bool _broadcast = true;
  int? _recipientId;
  bool _sendingAnnouncement = false;

  final Set<String> _busy = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
    _assignFutures();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _userSearchController.dispose();
    _campaignSearchController.dispose();
    _announcementTitle.dispose();
    _announcementMessage.dispose();
    super.dispose();
  }

  void _assignFutures() {
    _overview = _service.overview();
    _users = _service.users();
    _campaigns = _service.campaigns();
    _payouts = _service.payouts();
    _settings = _service.settings();
  }

  void _reload() => setState(_assignFutures);

  String _clean(Object error) =>
      error.toString().replaceFirst('Exception: ', '');

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _refresh(Future<dynamic> future) async {
    _reload();
    try {
      await future;
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Administration IVote'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          tabs: const [
            Tab(text: 'Vue d’ensemble'),
            Tab(text: 'Organisateurs'),
            Tab(text: 'Campagnes'),
            Tab(text: 'Retraits'),
            Tab(text: 'Annonces'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildOverviewTab(),
          _buildUsersTab(),
          _buildCampaignsTab(),
          _buildPayoutsTab(),
          _buildAnnouncementsTab(),
        ],
      ),
    );
  }

  Widget _buildOverviewTab() {
    return RefreshIndicator(
      onRefresh: () => _refresh(_overview),
      child: FutureBuilder<Map<String, dynamic>>(
        future: _overview,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return ListView(
              padding: const EdgeInsets.all(16),
              children: const [
                SkeletonCard(),
                SizedBox(height: 16),
                SkeletonCard(),
              ],
            );
          }
          if (snapshot.hasError) return _errorState(snapshot.error!);
          final data = snapshot.data ?? {};
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.45,
                children: [
                  _statCard('Organisateurs', data['organizers'] ?? 0,
                      Icons.people_outline, AppColors.primaryBlueLight),
                  _statCard('Campagnes en ligne', data['published_campaigns'] ?? 0,
                      Icons.campaign_outlined, AppColors.greenAccent),
                  _statCard('Comptes suspendus', data['suspended_users'] ?? 0,
                      Icons.block_outlined, AppColors.orangeAccent),
                  _statCard('Campagnes suspendues',
                      data['suspended_campaigns'] ?? 0,
                      Icons.pause_circle_outline, AppColors.redAccent),
                  _statCard('Retraits à traiter', data['pending_payouts'] ?? 0,
                      Icons.account_balance_wallet_outlined,
                      AppColors.orangeAccent),
                  _statCard('Revenus bruts',
                      _formatFcfa(data['gross_revenue']), Icons.payments_outlined,
                      AppColors.greenAccent),
                ],
              ),
              const SizedBox(height: 20),
              _platformFeeCard(),
            ],
          );
        },
      ),
    );
  }

  Widget _buildUsersTab() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: TextField(
            controller: _userSearchController,
            onChanged: (value) => setState(() => _userQuery = value.toLowerCase()),
            decoration: InputDecoration(
              hintText: 'Rechercher un organisateur…',
              prefixIcon: const Icon(Icons.search),
              isDense: true,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              _filterChip('all', 'Tous', _userFilter,
                  (value) => setState(() => _userFilter = value)),
              _filterChip('active', 'Actifs', _userFilter,
                  (value) => setState(() => _userFilter = value)),
              _filterChip('suspended', 'Suspendus', _userFilter,
                  (value) => setState(() => _userFilter = value)),
              _filterChip('disabled', 'Désactivés', _userFilter,
                  (value) => setState(() => _userFilter = value)),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Expanded(
          child: _asyncList(
            future: _users,
            emptyMessage: 'Aucun organisateur.',
            builder: (items) {
              final filtered = items.where((user) {
                if (user['role'] != 'organizer') return false;
                if (_userFilter != 'all' && user['status'] != _userFilter) {
                  return false;
                }
                if (_userQuery.isNotEmpty) {
                  final name = (user['name'] ?? '').toString().toLowerCase();
                  final email = (user['email'] ?? '').toString().toLowerCase();
                  if (!name.contains(_userQuery) && !email.contains(_userQuery)) {
                    return false;
                  }
                }
                return true;
              }).toList();
              if (filtered.isEmpty) {
                return ListView(
                  children: [
                    _emptyState('Aucun organisateur ne correspond à ce filtre.')
                  ],
                );
              }
              return ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: filtered.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (context, index) => _userTile(filtered[index]),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildCampaignsTab() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: TextField(
            controller: _campaignSearchController,
            onChanged: (value) =>
                setState(() => _campaignQuery = value.toLowerCase()),
            decoration: InputDecoration(
              hintText: 'Rechercher une campagne ou un organisateur…',
              prefixIcon: const Icon(Icons.search),
              isDense: true,
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              _filterChip('all', 'Toutes', _campaignFilter,
                  (value) => setState(() => _campaignFilter = value)),
              _filterChip('published', 'En ligne', _campaignFilter,
                  (value) => setState(() => _campaignFilter = value)),
              _filterChip('suspended', 'Suspendues', _campaignFilter,
                  (value) => setState(() => _campaignFilter = value)),
              _filterChip('closed', 'Clôturées', _campaignFilter,
                  (value) => setState(() => _campaignFilter = value)),
              _filterChip('draft', 'Brouillons', _campaignFilter,
                  (value) => setState(() => _campaignFilter = value)),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Expanded(
          child: _asyncList(
            future: _campaigns,
            emptyMessage: 'Aucune campagne.',
            builder: (items) {
              final filtered = items.where((campaign) {
                if (_campaignFilter != 'all' &&
                    campaign['status'] != _campaignFilter) {
                  return false;
                }
                if (_campaignQuery.isNotEmpty) {
                  final title = (campaign['title'] ?? '').toString().toLowerCase();
                  final organizer =
                      (campaign['organizer_name'] ?? '').toString().toLowerCase();
                  if (!title.contains(_campaignQuery) &&
                      !organizer.contains(_campaignQuery)) {
                    return false;
                  }
                }
                return true;
              }).toList();
              if (filtered.isEmpty) {
                return ListView(
                  children: [
                    _emptyState('Aucune campagne ne correspond à ce filtre.')
                  ],
                );
              }
              return ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: filtered.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (context, index) =>
                    _campaignTile(filtered[index]),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildPayoutsTab() {
    return _asyncList(
      future: _payouts,
      emptyMessage: 'Aucune demande de retrait.',
      builder: (items) {
        final pending = items
            .where((payout) =>
                payout['status'] == 'pending' ||
                payout['status'] == 'under_review')
            .toList();
        final history = items.where((payout) {
          return payout['status'] != 'pending' &&
              payout['status'] != 'under_review';
        }).toList();
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (pending.isNotEmpty) ...[
              _sectionLabel('À traiter'),
              ...pending.map(_payoutTile),
              const SizedBox(height: 16),
            ],
            if (history.isNotEmpty) ...[
              _sectionLabel('Historique'),
              ...history.map(_payoutTile),
            ],
            if (pending.isEmpty && history.isEmpty)
              _emptyState('Aucune demande de retrait.'),
          ],
        );
      },
    );
  }

  Widget _buildAnnouncementsTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text('Annonces aux organisateurs',
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.w800)),
        const SizedBox(height: 4),
        Text('Envoi individuel ou à tous les organisateurs actifs.',
            style: TextStyle(
                fontSize: 12,
                color: Theme.of(context).colorScheme.onSurfaceVariant)),
        const SizedBox(height: 16),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: const Text('Envoyer à tous les organisateurs actifs'),
          value: _broadcast,
          onChanged: (value) => setState(() => _broadcast = value),
        ),
        if (!_broadcast)
          FutureBuilder<List<dynamic>>(
            future: _users,
            builder: (context, snapshot) {
              final organizers = (snapshot.data ?? [])
                  .map((item) => Map<String, dynamic>.from(item))
                  .where((user) =>
                      user['role'] == 'organizer' && user['status'] == 'active')
                  .toList();
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: DropdownButtonFormField<int>(
                  value: _recipientId,
                  decoration: const InputDecoration(
                    labelText: 'Destinataire',
                    border: OutlineInputBorder(),
                  ),
                  items: organizers
                      .map((user) => DropdownMenuItem<int>(
                            value: (user['user_id'] as num).toInt(),
                            child: Text('${user['name']} · ${user['email']}'),
                          ))
                      .toList(),
                  onChanged: (value) => setState(() => _recipientId = value),
                ),
              );
            },
          ),
        TextField(
          controller: _announcementTitle,
          maxLength: 150,
          decoration: const InputDecoration(
              labelText: 'Titre de l’annonce', border: OutlineInputBorder()),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: _announcementMessage,
          maxLines: 4,
          decoration: const InputDecoration(
              labelText: 'Message', border: OutlineInputBorder()),
        ),
        const SizedBox(height: 12),
        Align(
          alignment: Alignment.centerRight,
          child: FilledButton.icon(
            onPressed: _sendingAnnouncement ? null : _sendAnnouncement,
            icon: const Icon(Icons.send_outlined),
            label: Text(_sendingAnnouncement ? 'Envoi…' : 'Envoyer'),
          ),
        ),
      ],
    );
  }

  Widget _asyncList({
    required Future<List<dynamic>> future,
    required String emptyMessage,
    required Widget Function(List<Map<String, dynamic>> items) builder,
  }) {
    return RefreshIndicator(
      onRefresh: () => _refresh(future),
      child: FutureBuilder<List<dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) return _errorState(snapshot.error!);
          final items = (snapshot.data ?? [])
              .map((item) => Map<String, dynamic>.from(item))
              .toList();
          if (items.isEmpty) {
            return ListView(children: [_emptyState(emptyMessage)]);
          }
          return builder(items);
        },
      ),
    );
  }

  Widget _errorState(Object error) {
    final theme = Theme.of(context);
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        const SizedBox(height: 60),
        Icon(Icons.cloud_off_outlined,
            size: 48, color: theme.colorScheme.onSurfaceVariant),
        const SizedBox(height: 12),
        Text(_clean(error), textAlign: TextAlign.center),
        const SizedBox(height: 12),
        Center(
          child: FilledButton.icon(
            onPressed: _reload,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ),
      ],
    );
  }

  Widget _emptyState(String message) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
      child: Column(
        children: [
          Icon(Icons.inbox_outlined,
              size: 44, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(height: 10),
          Text(message, textAlign: TextAlign.center),
        ],
      ),
    );
  }

  Widget _sectionLabel(String label) => Padding(
        padding: const EdgeInsets.only(bottom: 8, top: 4),
        child: Text(label,
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
      );

  Widget _filterChip(
      String value, String label, String current, ValueChanged<String> onSelect) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: current == value,
        onSelected: (_) => onSelect(value),
      ),
    );
  }

  Widget _statCard(String label, dynamic value, IconData icon, Color color) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
                color: color.withOpacity(0.15),
                borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: color, size: 18),
          ),
          const Spacer(),
          Text('$value',
              style: theme.textTheme.titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
              maxLines: 1,
              overflow: TextOverflow.ellipsis),
          const SizedBox(height: 2),
          Text(label,
              style: TextStyle(
                  fontSize: 11, color: theme.colorScheme.onSurfaceVariant),
              maxLines: 2,
              overflow: TextOverflow.ellipsis),
        ],
      ),
    );
  }

  Widget _platformFeeCard() {
    final theme = Theme.of(context);
    return FutureBuilder<List<dynamic>>(
      future: _settings,
      builder: (context, snapshot) {
        String rateText = '—';
        if (snapshot.hasData) {
          for (final item in snapshot.data!) {
            final map = Map<String, dynamic>.from(item);
            if (map['setting_key'] == 'platform_fee_rate') {
              rateText = '${map['setting_value']} %';
            }
          }
        }
        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: theme.colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: theme.colorScheme.outline),
          ),
          child: Row(
            children: [
              const Icon(Icons.percent_rounded),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Frais de plateforme',
                        style: TextStyle(fontWeight: FontWeight.w700)),
                    Text('Commission prélevée sur chaque vote',
                        style: TextStyle(
                            fontSize: 12,
                            color: theme.colorScheme.onSurfaceVariant)),
                  ],
                ),
              ),
              Text(rateText,
                  style: theme.textTheme.titleMedium
                      ?.copyWith(fontWeight: FontWeight.w800)),
              IconButton(
                tooltip: 'Modifier',
                icon: const Icon(Icons.edit_outlined),
                onPressed: snapshot.hasData
                    ? () => _editPlatformFee(snapshot.data!)
                    : null,
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _userTile(Map<String, dynamic> user) {
    final theme = Theme.of(context);
    final key = 'user-${user['user_id']}';
    final busy = _busy.contains(key);
    final isActive = user['status'] == 'active';
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: theme.colorScheme.primary.withOpacity(0.15),
            child: Text(_initials(user['name']),
                style: TextStyle(
                    color: theme.colorScheme.primary,
                    fontWeight: FontWeight.w800)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(user['name'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis),
                    ),
                    const SizedBox(width: 8),
                    _statusBadge(_userStatusLabel(user['status']),
                        _userStatusColor(user['status'])),
                  ],
                ),
                const SizedBox(height: 2),
                Text(user['email'] ?? '',
                    style: TextStyle(
                        fontSize: 12,
                        color: theme.colorScheme.onSurfaceVariant),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
                if (user['created_at'] != null)
                  Text('Inscrit le ${_formatDate(user['created_at'])}',
                      style: TextStyle(
                          fontSize: 11,
                          color: theme.colorScheme.onSurfaceVariant)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (busy)
            const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2))
          else
            TextButton(
              onPressed: () => _statusUser(user),
              child: Text(isActive ? 'Suspendre' : 'Réactiver'),
            ),
        ],
      ),
    );
  }

  Widget _campaignTile(Map<String, dynamic> campaign) {
    final theme = Theme.of(context);
    final key = 'campaign-${campaign['campaign_id']}';
    final busy = _busy.contains(key);
    final status = (campaign['status'] ?? '').toString();
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(campaign['title'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ),
              const SizedBox(width: 8),
              _statusBadge(_campaignStatusLabel(status),
                  _campaignStatusColor(status)),
            ],
          ),
          const SizedBox(height: 4),
          Text('${campaign['organizer_name'] ?? ''} · ${campaign['organizer_email'] ?? ''}',
              style: TextStyle(
                  fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
              maxLines: 1,
              overflow: TextOverflow.ellipsis),
          if (campaign['suspension_reason'] != null)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text('Motif : ${campaign['suspension_reason']}',
                  style: TextStyle(
                      fontSize: 12, color: theme.colorScheme.onSurfaceVariant)),
            ),
          const SizedBox(height: 8),
          if (busy)
            const Align(
                alignment: Alignment.centerRight,
                child: SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2)))
          else
            Wrap(
              spacing: 8,
              alignment: WrapAlignment.end,
              children: [
                if (status == 'published')
                  OutlinedButton.icon(
                    onPressed: () => _moderateCampaign(campaign, 'suspend'),
                    icon: const Icon(Icons.visibility_off_outlined, size: 18),
                    label: const Text('Suspendre'),
                  ),
                if (status == 'suspended')
                  FilledButton.icon(
                    onPressed: () => _moderateCampaign(campaign, 'restore'),
                    icon: const Icon(Icons.restore_outlined, size: 18),
                    label: const Text('Restaurer'),
                  ),
                if (status == 'published')
                  OutlinedButton.icon(
                    onPressed: () => _moderateCampaign(campaign, 'close'),
                    icon: const Icon(Icons.lock_outline, size: 18),
                    label: const Text('Clôturer'),
                  ),
              ],
            ),
        ],
      ),
    );
  }

  Widget _payoutTile(Map<String, dynamic> payout) {
    final theme = Theme.of(context);
    final key = 'payout-${payout['payout_id']}';
    final busy = _busy.contains(key);
    final status = (payout['status'] ?? '').toString();
    final isPending = status == 'pending' || status == 'under_review';
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                    '${payout['organizer_name'] ?? ''} · ${payout['title'] ?? ''}',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ),
              const SizedBox(width: 8),
              _statusBadge(_payoutStatusLabel(status),
                  _payoutStatusColor(status)),
            ],
          ),
          const SizedBox(height: 4),
          Text(
              '${_formatFcfa(payout['amount_fcfa'])} · ${_methodLabel(payout['payment_method'])}',
              style: const TextStyle(fontWeight: FontWeight.w600)),
          Text('${payout['account_holder'] ?? ''} · ${payout['wallet_number'] ?? ''}',
              style: TextStyle(
                  fontSize: 12, color: theme.colorScheme.onSurfaceVariant)),
          if (payout['payment_reference'] != null &&
              '${payout['payment_reference']}'.isNotEmpty)
            Text('Référence : ${payout['payment_reference']}',
                style: TextStyle(
                    fontSize: 12, color: theme.colorScheme.onSurfaceVariant)),
          if (payout['processed_at'] != null)
            Text('Traité le ${_formatDate(payout['processed_at'])}',
                style: TextStyle(
                    fontSize: 12, color: theme.colorScheme.onSurfaceVariant)),
          if (isPending) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: busy ? null : () => _reviewPayout(payout, 'reject'),
                    icon: const Icon(Icons.close, size: 18),
                    label: const Text('Refuser'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: FilledButton.icon(
                    onPressed:
                        busy ? null : () => _reviewPayout(payout, 'approve'),
                    icon: const Icon(Icons.check, size: 18),
                    label: const Text('Valider'),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _statusBadge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(label,
          style: TextStyle(
              fontSize: 11, color: color, fontWeight: FontWeight.w700)),
    );
  }

  Future<void> _statusUser(Map<String, dynamic> user) async {
    final key = 'user-${user['user_id']}';
    final toSuspend = user['status'] == 'active';
    String? reason;
    if (toSuspend) {
      reason = await _reasonDialog('Motif de suspension');
      if (reason == null || reason.isEmpty) return;
    }
    setState(() => _busy.add(key));
    try {
      await _service.updateUserStatus(
        (user['user_id'] as num).toInt(),
        toSuspend ? 'suspended' : 'active',
        toSuspend ? reason! : 'Réactivation par le super-admin',
      );
      _snack(toSuspend ? 'Compte suspendu.' : 'Compte réactivé.');
      _reload();
    } catch (error) {
      _snack(_clean(error));
    } finally {
      if (mounted) setState(() => _busy.remove(key));
    }
  }

  Future<void> _moderateCampaign(
      Map<String, dynamic> campaign, String action) async {
    final key = 'campaign-${campaign['campaign_id']}';
    String reason = '';
    if (action == 'suspend') {
      final input = await _reasonDialog('Motif de suspension');
      if (input == null || input.isEmpty) return;
      reason = input;
    } else {
      final confirmed = await _confirmDialog(
        action == 'restore' ? 'Restaurer la campagne' : 'Clôturer la campagne',
        action == 'restore'
            ? 'Remettre cette campagne en ligne ?'
            : 'Clôturer définitivement cette campagne ?',
      );
      if (!confirmed) return;
    }
    setState(() => _busy.add(key));
    try {
      await _service.moderateCampaign(
          (campaign['campaign_id'] as num).toInt(), action, reason);
      _snack('Campagne mise à jour.');
      _reload();
    } catch (error) {
      _snack(_clean(error));
    } finally {
      if (mounted) setState(() => _busy.remove(key));
    }
  }

  Future<void> _reviewPayout(Map<String, dynamic> payout, String action) async {
    final key = 'payout-${payout['payout_id']}';
    String? reason;
    if (action == 'reject') {
      reason = await _reasonDialog('Motif du refus');
      if (reason == null || reason.isEmpty) return;
    } else {
      final confirmed = await _confirmDialog(
        'Valider le retrait',
        'Confirmer le transfert de ${_formatFcfa(payout['amount_fcfa'])} vers ${payout['wallet_number']} ?',
      );
      if (!confirmed) return;
    }
    setState(() => _busy.add(key));
    try {
      await _service.reviewPayout(
          (payout['payout_id'] as num).toInt(), action,
          reason: reason);
      _snack(action == 'reject' ? 'Demande refusée.' : 'Demande validée.');
      _reload();
    } catch (error) {
      _snack(_clean(error));
    } finally {
      if (mounted) setState(() => _busy.remove(key));
    }
  }

  Future<void> _editPlatformFee(List<dynamic> settings) async {
    final controller = TextEditingController();
    for (final item in settings) {
      final map = Map<String, dynamic>.from(item);
      if (map['setting_key'] == 'platform_fee_rate') {
        controller.text = '${map['setting_value']}';
      }
    }
    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Frais de plateforme'),
        content: TextField(
          controller: controller,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(
              labelText: 'Taux', suffixText: '%', border: OutlineInputBorder()),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler')),
          FilledButton(
              onPressed: () => Navigator.pop(context, controller.text.trim()),
              child: const Text('Enregistrer')),
        ],
      ),
    );
    if (result == null) return;
    final rate = double.tryParse(result.replaceAll(',', '.'));
    if (rate == null || rate < 0 || rate > 100) {
      _snack('Taux invalide (0 à 100).');
      return;
    }
    try {
      await _service.updatePlatformFee(rate);
      _reload();
      _snack('Frais de plateforme mis à jour.');
    } catch (error) {
      _snack(_clean(error));
    }
  }

  Future<void> _sendAnnouncement() async {
    if (_announcementTitle.text.trim().isEmpty ||
        _announcementMessage.text.trim().isEmpty ||
        (!_broadcast && _recipientId == null)) {
      _snack('Renseignez un destinataire, un titre et un message.');
      return;
    }
    setState(() => _sendingAnnouncement = true);
    try {
      await _service.sendAnnouncement(
        title: _announcementTitle.text.trim(),
        message: _announcementMessage.text.trim(),
        broadcast: _broadcast,
        recipientId: _recipientId,
      );
      _announcementTitle.clear();
      _announcementMessage.clear();
      _snack('Annonce envoyée.');
    } catch (error) {
      _snack(_clean(error));
    } finally {
      if (mounted) setState(() => _sendingAnnouncement = false);
    }
  }

  Future<String?> _reasonDialog(String title) {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLines: 3,
          autofocus: true,
          decoration: const InputDecoration(hintText: 'Motif obligatoire'),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler')),
          FilledButton(
              onPressed: () => Navigator.pop(context, controller.text.trim()),
              child: const Text('Confirmer')),
        ],
      ),
    );
  }

  Future<bool> _confirmDialog(String title, String message) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Confirmer')),
        ],
      ),
    );
    return result ?? false;
  }

  String _initials(dynamic name) {
    final parts = (name ?? '').toString().trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1))
        .toUpperCase();
  }

  String _formatFcfa(dynamic value) {
    final number = value is num ? value.toInt() : int.tryParse('$value') ?? 0;
    final digits = number.abs().toString();
    final buffer = StringBuffer(number < 0 ? '-' : '');
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) buffer.write(' ');
      buffer.write(digits[i]);
    }
    return '${buffer.toString()} FCFA';
  }

  String _formatDate(dynamic value) {
    final parsed = DateTime.tryParse('$value');
    if (parsed == null) return '$value';
    final local = parsed.toLocal();
    String two(int n) => n.toString().padLeft(2, '0');
    return '${two(local.day)}/${two(local.month)}/${local.year}';
  }

  String _methodLabel(dynamic method) => switch ('$method') {
        'mtn_momo' => 'MTN MoMo',
        'orange_money' => 'Orange Money',
        _ => '$method',
      };

  String _userStatusLabel(dynamic status) => switch ('$status') {
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'disabled' => 'Désactivé',
        _ => '$status',
      };

  Color _userStatusColor(dynamic status) => switch ('$status') {
        'active' => AppColors.greenAccent,
        'suspended' => AppColors.orangeAccent,
        'disabled' => AppColors.redAccent,
        _ => AppColors.foregroundSecondaryLight,
      };

  String _campaignStatusLabel(dynamic status) => switch ('$status') {
        'draft' => 'Brouillon',
        'pending_review' => 'En validation',
        'published' => 'En ligne',
        'suspended' => 'Suspendue',
        'closed' => 'Clôturée',
        'archived' => 'Archivée',
        _ => '$status',
      };

  Color _campaignStatusColor(dynamic status) => switch ('$status') {
        'published' => AppColors.greenAccent,
        'suspended' => AppColors.redAccent,
        'closed' => AppColors.foregroundSecondaryLight,
        'draft' => AppColors.orangeAccent,
        'pending_review' => AppColors.primaryBlueLight,
        _ => AppColors.foregroundSecondaryLight,
      };

  String _payoutStatusLabel(dynamic status) => switch ('$status') {
        'pending' => 'En attente',
        'under_review' => 'En cours',
        'approved' => 'Validé',
        'paid' => 'Payé',
        'rejected' => 'Refusé',
        'cancelled' => 'Annulé',
        _ => '$status',
      };

  Color _payoutStatusColor(dynamic status) => switch ('$status') {
        'pending' || 'under_review' => AppColors.orangeAccent,
        'approved' => AppColors.primaryBlueLight,
        'paid' => AppColors.greenAccent,
        'rejected' || 'cancelled' => AppColors.redAccent,
        _ => AppColors.foregroundSecondaryLight,
      };
}
