class ActivityLog {
  final String id;
  final String childId;
  final String? itemId;
  final String? topicId;
  final String actionType; // 'view_card', 'listen_pronounce', 'watch_video', 'complete_quiz'
  final int durationSeconds;
  final String timestamp;

  ActivityLog({
    required this.id,
    required this.childId,
    this.itemId,
    this.topicId,
    required this.actionType,
    this.durationSeconds = 0,
    String? timestamp,
  }) : timestamp = timestamp ?? DateTime.now().toIso8601String();

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'child_id': childId,
      'item_id': itemId,
      'topic_id': topicId,
      'action_type': actionType,
      'duration_seconds': durationSeconds,
      'timestamp': timestamp,
    };
  }

  factory ActivityLog.fromMap(Map<String, dynamic> map) {
    return ActivityLog(
      id: map['id'] as String,
      childId: map['child_id'] as String,
      itemId: map['item_id'] as String?,
      topicId: map['topic_id'] as String?,
      actionType: map['action_type'] as String,
      durationSeconds: map['duration_seconds'] as int? ?? 0,
      timestamp: map['timestamp'] as String? ?? DateTime.now().toIso8601String(),
    );
  }
}
