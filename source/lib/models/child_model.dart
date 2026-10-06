class Child {
  final String id;
  final String name;
  final String gender; // 'male', 'female', 'other'
  final int birthYear;
  final String? avatarUrl;
  final String createdAt;

  Child({
    required this.id,
    required this.name,
    this.gender = 'male',
    required this.birthYear,
    this.avatarUrl,
    String? createdAt,
  }) : createdAt = createdAt ?? DateTime.now().toIso8601String();

  int get age => DateTime.now().year - birthYear;

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'name': name,
      'gender': gender,
      'birth_year': birthYear,
      'avatar_url': avatarUrl,
      'created_at': createdAt,
    };
  }

  factory Child.fromMap(Map<String, dynamic> map) {
    return Child(
      id: map['id'] as String,
      name: map['name'] as String,
      gender: map['gender'] as String? ?? 'male',
      birthYear: map['birth_year'] as int,
      avatarUrl: map['avatar_url'] as String?,
      createdAt: map['created_at'] as String?,
    );
  }

  Child copyWith({String? id, String? name, String? gender, int? birthYear, String? avatarUrl, String? createdAt}) {
    return Child(
      id: id ?? this.id,
      name: name ?? this.name,
      gender: gender ?? this.gender,
      birthYear: birthYear ?? this.birthYear,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
