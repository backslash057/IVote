import 'package:flutter/material.dart';
import '../services/notification_service.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key});

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  final _service = NotificationService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchNotifications();
  }

  Future<void> _markAllRead() async {
    await _service.markAllRead();
    if (mounted) setState(() => _future = _service.fetchNotifications());
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
              onPressed: _markAllRead,
              tooltip: 'Tout marquer comme lu',
              icon: const Icon(Icons.done_all_rounded))
        ],
      ),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting)
            return const Center(child: CircularProgressIndicator());
          if (snapshot.hasError)
            return Center(
                child:
                    Text('${snapshot.error}'.replaceFirst('Exception: ', '')));
          final notifications = snapshot.data ?? const [];
          if (notifications.isEmpty)
            return Center(
                child: Text('Aucune notification.',
                    style:
                        TextStyle(color: theme.colorScheme.onSurfaceVariant)));
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: notifications.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final item = notifications[index];
              final unread = item['read_at'] == null;
              return ListTile(
                tileColor: unread
                    ? theme.colorScheme.primary.withOpacity(0.08)
                    : theme.colorScheme.surfaceContainerHighest,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14)),
                leading: Icon(unread
                    ? Icons.notifications_active_outlined
                    : Icons.notifications_none_outlined),
                title: Text(item['title'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text(item['message'] ?? ''),
                onTap: unread
                    ? () async {
                        await _service
                            .markRead((item['notification_id'] as num).toInt());
                        if (mounted)
                          setState(
                              () => _future = _service.fetchNotifications());
                      }
                    : null,
              );
            },
          );
        },
      ),
    );
  }
}
