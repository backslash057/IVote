import 'package:flutter/material.dart';
import '../services/admin_service.dart';

class AdminPage extends StatefulWidget {
  const AdminPage({super.key});

  @override
  State<AdminPage> createState() => _AdminPageState();
}

class _AdminPageState extends State<AdminPage> {
  final _service = AdminService();
  late Future<Map<String, dynamic>> _overview;
  late Future<List<dynamic>> _users;
  late Future<List<dynamic>> _campaigns;
  late Future<List<dynamic>> _payouts;
  final _announcementTitle = TextEditingController();
  final _announcementMessage = TextEditingController();
  bool _broadcast = true;
  int? _recipientId;
  bool _sendingAnnouncement = false;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  void _reload() {
    _overview = _service.overview();
    _users = _service.users();
    _campaigns = _service.campaigns();
    _payouts = _service.payouts();
  }

  @override
  void dispose() {
    _announcementTitle.dispose();
    _announcementMessage.dispose();
    super.dispose();
  }

  Future<void> _statusUser(Map<String, dynamic> user) async {
    final status = user['status'] == 'active' ? 'suspended' : 'active';
    if (status == 'suspended') {
      final reason = await _reasonDialog('Motif de suspension');
      if (reason == null) return;
      await _service.updateUserStatus(
          (user['user_id'] as num).toInt(), status, reason);
    } else {
      await _service.updateUserStatus((user['user_id'] as num).toInt(), status,
          'Réactivation par le super-admin');
    }
    if (mounted) setState(_reload);
  }

  Future<String?> _reasonDialog(String title) async {
    final controller = TextEditingController();
    return showDialog<String>(
        context: context,
        builder: (context) => AlertDialog(
                title: Text(title),
                content: TextField(
                    controller: controller,
                    maxLines: 3,
                    decoration:
                        const InputDecoration(hintText: 'Motif obligatoire')),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Annuler')),
                  FilledButton(
                      onPressed: () =>
                          Navigator.pop(context, controller.text.trim()),
                      child: const Text('Confirmer'))
                ]));
  }

  Future<void> _reviewPayout(Map<String, dynamic> payout, String action) async {
    final reason =
        action == 'reject' ? await _reasonDialog('Motif du refus') : null;
    if (action == 'reject' && (reason == null || reason.isEmpty)) return;
    try {
      await _service.reviewPayout((payout['payout_id'] as num).toInt(), action,
          reason: reason);
      if (mounted) setState(_reload);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(error.toString().replaceFirst('Exception: ', ''))));
      }
    }
  }

  Future<void> _sendAnnouncement() async {
    if (_announcementTitle.text.trim().isEmpty ||
        _announcementMessage.text.trim().isEmpty ||
        (!_broadcast && _recipientId == null)) return;
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
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Annonce envoyée.')));
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(error.toString().replaceFirst('Exception: ', ''))));
      }
    } finally {
      if (mounted) setState(() => _sendingAnnouncement = false);
    }
  }

  String _campaignStatusLabel(String status) => switch (status) {
        'draft' => 'Brouillon',
        'pending_review' => 'En validation',
        'published' => 'En ligne',
        'suspended' => 'Suspendue',
        'closed' => 'Clôturée',
        'archived' => 'Archivée',
        _ => status,
      };

  Future<void> _moderateCampaign(
      Map<String, dynamic> campaign, String action) async {
    final reason =
        action == 'suspend' ? await _reasonDialog('Motif de suspension') : null;
    if (action == 'suspend' && (reason == null || reason.isEmpty)) return;
    try {
      await _service.moderateCampaign(
        (campaign['campaign_id'] as num).toInt(),
        action,
        reason ?? '',
      );
      if (mounted) setState(_reload);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(error.toString().replaceFirst('Exception: ', ''))));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Administration IVote')),
      body: RefreshIndicator(
        onRefresh: () async {
          setState(_reload);
          await _overview;
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            FutureBuilder<Map<String, dynamic>>(
              future: _overview,
              builder: (context, snapshot) {
                final data = snapshot.data ?? {};
                return Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    'organizers',
                    'published_campaigns',
                    'suspended_users',
                    'pending_payouts'
                  ].map((key) => _stat(key, data[key] ?? 0)).toList(),
                );
              },
            ),
            const SizedBox(height: 24),
            const Text('Organisateurs',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            FutureBuilder<List<dynamic>>(
              future: _users,
              builder: (context, snapshot) {
                final users = (snapshot.data ?? [])
                    .where((item) => item['role'] == 'organizer');
                return Column(
                  children: users.map<Widget>((item) {
                    final user = Map<String, dynamic>.from(item);
                    return ListTile(
                      title: Text(user['name'] ?? ''),
                      subtitle: Text(user['email'] ?? ''),
                      trailing: TextButton(
                          onPressed: () => _statusUser(user),
                          child: Text(user['status'] == 'active'
                              ? 'Suspendre'
                              : 'Réactiver')),
                    );
                  }).toList(),
                );
              },
            ),
            const SizedBox(height: 24),
            const Text('Campagnes',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            FutureBuilder<List<dynamic>>(
              future: _campaigns,
              builder: (context, snapshot) {
                return Column(
                  children: (snapshot.data ?? []).map<Widget>((item) {
                    final campaign = Map<String, dynamic>.from(item);
                    return ListTile(
                      title: Text(campaign['title'] ?? ''),
                      subtitle: Text(
                          '${campaign['organizer_name'] ?? ''} · ${_campaignStatusLabel(campaign['status'] ?? '')}${campaign['suspension_reason'] == null ? '' : ' · ${campaign['suspension_reason']}'}'),
                      trailing: campaign['status'] == 'published'
                          ? IconButton(
                              icon: const Icon(Icons.visibility_off_outlined),
                              tooltip: 'Suspendre',
                              onPressed: () =>
                                  _moderateCampaign(campaign, 'suspend'),
                            )
                          : campaign['status'] == 'suspended'
                              ? IconButton(
                                  icon: const Icon(Icons.restore_outlined),
                                  tooltip: 'Restaurer',
                                  onPressed: () =>
                                      _moderateCampaign(campaign, 'restore'))
                              : null,
                    );
                  }).toList(),
                );
              },
            ),
            const SizedBox(height: 24),
            const Text('Retraits en attente',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            FutureBuilder<List<dynamic>>(
              future: _payouts,
              builder: (context, snapshot) {
                final pending = (snapshot.data ?? []).where((item) =>
                    item['status'] == 'pending' ||
                    item['status'] == 'under_review');
                if (pending.isEmpty) {
                  return const ListTile(
                      title: Text('Aucun retrait en attente.'));
                }
                return Column(
                  children: pending.map<Widget>((item) {
                    final payout = Map<String, dynamic>.from(item);
                    return ListTile(
                      title: Text(
                          '${payout['organizer_name']} · ${payout['title']}'),
                      subtitle: Text(
                          '${payout['amount_fcfa']} FCFA · ${payout['payment_method']} · ${payout['wallet_number']}'),
                      trailing: Wrap(children: [
                        IconButton(
                            tooltip: 'Valider',
                            icon: const Icon(Icons.check_circle_outline,
                                color: Colors.green),
                            onPressed: () => _reviewPayout(payout, 'approve')),
                        IconButton(
                            tooltip: 'Refuser',
                            icon: const Icon(Icons.cancel_outlined,
                                color: Colors.red),
                            onPressed: () => _reviewPayout(payout, 'reject')),
                      ]),
                    );
                  }).toList(),
                );
              },
            ),
            const SizedBox(height: 24),
            const Text('Annonces aux organisateurs',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
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
                  final organizers = (snapshot.data ?? []).where((item) =>
                      item['role'] == 'organizer' &&
                      item['status'] == 'active');
                  return DropdownButtonFormField<int>(
                    value: _recipientId,
                    decoration:
                        const InputDecoration(labelText: 'Destinataire'),
                    items: organizers
                        .map((item) => DropdownMenuItem<int>(
                              value: (item['user_id'] as num).toInt(),
                              child: Text('${item['name']} · ${item['email']}'),
                            ))
                        .toList(),
                    onChanged: (value) => setState(() => _recipientId = value),
                  );
                },
              ),
            TextField(
                controller: _announcementTitle,
                decoration:
                    const InputDecoration(labelText: 'Titre de l’annonce')),
            TextField(
                controller: _announcementMessage,
                maxLines: 4,
                decoration: const InputDecoration(labelText: 'Message')),
            const SizedBox(height: 12),
            Align(
                alignment: Alignment.centerRight,
                child: FilledButton.icon(
                    onPressed: _sendingAnnouncement ? null : _sendAnnouncement,
                    icon: const Icon(Icons.send_outlined),
                    label:
                        Text(_sendingAnnouncement ? 'Envoi...' : 'Envoyer'))),
          ],
        ),
      ),
    );
  }

  Widget _stat(String label, dynamic value) =>
      Chip(label: Text('${label.replaceAll('_', ' ')} : $value'));
}
