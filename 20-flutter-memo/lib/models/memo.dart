import 'package:hive/hive.dart';

part 'memo.g.dart';

@HiveType(typeId: 0)
class Memo extends HiveObject {
  @HiveField(0)
  late String title;

  @HiveField(1)
  late String content;

  @HiveField(2)
  late int colorValue;

  @HiveField(3)
  late DateTime createdAt;

  @HiveField(4)
  late DateTime updatedAt;

  Memo({
    required this.title,
    required this.content,
    required this.colorValue,
    required this.createdAt,
    required this.updatedAt,
  });
}
