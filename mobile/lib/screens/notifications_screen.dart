import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

class NotificationItem {
  final int id;
  final String title;
  final String body;
  final String type;
  final DateTime createdAt;
  final bool isRead;
  final Map<String, dynamic>? data;

  NotificationItem({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    required this.createdAt,
    this.isRead = false,
    this.data,
  });
}

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  List<NotificationItem> _notifications = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    setState(() => _isLoading = true);

    // TODO: Replace with actual API call
    // Simulated data for now
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      _notifications = [
        NotificationItem(
          id: 1,
          title: 'Nouvelle offre à proximité',
          body: '20% de réduction chez Restaurant Le Bon Goût',
          type: 'proximity_alert',
          createdAt: DateTime.now().subtract(const Duration(hours: 2)),
        ),
        NotificationItem(
          id: 2,
          title: 'QR Code généré',
          body: 'Votre QR code pour "Café gratuit" est prêt',
          type: 'qr_generated',
          createdAt: DateTime.now().subtract(const Duration(days: 1)),
          isRead: true,
        ),
        NotificationItem(
          id: 3,
          title: 'Félicitations !',
          body: 'Vous avez atteint le niveau Silver',
          type: 'level_up',
          createdAt: DateTime.now().subtract(const Duration(days: 3)),
          isRead: true,
        ),
      ];
      _isLoading = false;
    });
  }

  void _markAsRead(int id) {
    setState(() {
      final index = _notifications.indexWhere((n) => n.id == id);
      if (index != -1) {
        _notifications[index] = NotificationItem(
          id: _notifications[index].id,
          title: _notifications[index].title,
          body: _notifications[index].body,
          type: _notifications[index].type,
          createdAt: _notifications[index].createdAt,
          isRead: true,
          data: _notifications[index].data,
        );
      }
    });
  }

  void _deleteNotification(int id) {
    setState(() {
      _notifications.removeWhere((n) => n.id == id);
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Notification supprimée'),
        duration: Duration(seconds: 2),
      ),
    );
  }

  void _markAllAsRead() {
    setState(() {
      _notifications = _notifications
          .map((n) => NotificationItem(
                id: n.id,
                title: n.title,
                body: n.body,
                type: n.type,
                createdAt: n.createdAt,
                isRead: true,
                data: n.data,
              ))
          .toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    final unreadCount = _notifications.where((n) => !n.isRead).length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        backgroundColor: Colors.orange,
        actions: [
          if (unreadCount > 0)
            TextButton(
              onPressed: _markAllAsRead,
              child: const Text(
                'Tout marquer comme lu',
                style: TextStyle(color: Colors.white),
              ),
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_notifications.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.notifications_off,
              size: 80,
              color: Colors.grey[300],
            ),
            const SizedBox(height: 16),
            Text(
              'Aucune notification',
              style: TextStyle(
                fontSize: 18,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Vous serez notifié des nouvelles offres',
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey[500],
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadNotifications,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(vertical: 8),
        itemCount: _notifications.length,
        itemBuilder: (context, index) {
          return _buildNotificationItem(_notifications[index]);
        },
      ),
    );
  }

  Widget _buildNotificationItem(NotificationItem notification) {
    final dateFormatter = DateFormat('dd/MM/yyyy HH:mm');

    IconData icon;
    Color iconColor;

    switch (notification.type) {
      case 'proximity_alert':
        icon = Icons.location_on;
        iconColor = Colors.orange;
        break;
      case 'qr_generated':
        icon = Icons.qr_code_2;
        iconColor = Colors.blue;
        break;
      case 'level_up':
        icon = Icons.star;
        iconColor = Colors.amber;
        break;
      default:
        icon = Icons.notifications;
        iconColor = Colors.grey;
    }

    return Dismissible(
      key: Key('notification_${notification.id}'),
      direction: DismissDirection.endToStart,
      background: Container(
        color: Colors.red,
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        child: const Icon(
          Icons.delete,
          color: Colors.white,
          size: 32,
        ),
      ),
      onDismissed: (direction) {
        _deleteNotification(notification.id);
      },
      child: InkWell(
        onTap: () {
          if (!notification.isRead) {
            _markAsRead(notification.id);
          }
          // TODO: Navigate based on notification type
        },
        child: Container(
          color: notification.isRead ? Colors.white : Colors.orange.shade50,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: iconColor.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon,
                  color: iconColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            notification.title,
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: notification.isRead
                                  ? FontWeight.normal
                                  : FontWeight.bold,
                            ),
                          ),
                        ),
                        if (!notification.isRead)
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: Colors.orange,
                              shape: BoxShape.circle,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      notification.body,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[700],
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      dateFormatter.format(notification.createdAt),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[500],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
