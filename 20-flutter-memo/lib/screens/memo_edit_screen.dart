import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';

import '../models/memo.dart';

const _colorOptions = [
  Color(0xFF1e293b),
  Color(0xFF1e3a5f),
  Color(0xFF1a3a2a),
  Color(0xFF3b1f2b),
  Color(0xFF2d1f3b),
  Color(0xFF3b2a1a),
];

const _colorLabels = ['기본', '블루', '그린', '핑크', '퍼플', '앰버'];

class MemoEditScreen extends StatefulWidget {
  final Memo? memo;
  const MemoEditScreen({super.key, this.memo});

  @override
  State<MemoEditScreen> createState() => _MemoEditScreenState();
}

class _MemoEditScreenState extends State<MemoEditScreen> {
  late final TextEditingController _titleCtrl;
  late final TextEditingController _contentCtrl;
  late int _selectedColorValue;
  bool get _isEditing => widget.memo != null;

  @override
  void initState() {
    super.initState();
    _titleCtrl = TextEditingController(text: widget.memo?.title ?? '');
    _contentCtrl = TextEditingController(text: widget.memo?.content ?? '');
    _selectedColorValue =
        widget.memo?.colorValue ?? _colorOptions[0].toARGB32();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _contentCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final title = _titleCtrl.text.trim();
    final content = _contentCtrl.text.trim();
    if (title.isEmpty && content.isEmpty) {
      Navigator.pop(context);
      return;
    }

    final box = Hive.box<Memo>('memos');
    final now = DateTime.now();

    if (_isEditing) {
      widget.memo!
        ..title = title
        ..content = content
        ..colorValue = _selectedColorValue
        ..updatedAt = now;
      await widget.memo!.save();
    } else {
      await box.add(Memo(
        title: title,
        content: content,
        colorValue: _selectedColorValue,
        createdAt: now,
        updatedAt: now,
      ));
    }
    if (mounted) Navigator.pop(context);
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1a1d27),
        title: const Text('메모 삭제', style: TextStyle(color: Colors.white)),
        content: const Text('이 메모를 삭제할까요?',
            style: TextStyle(color: Colors.white70)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('취소', style: TextStyle(color: Colors.white54)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('삭제', style: TextStyle(color: Colors.redAccent)),
          ),
        ],
      ),
    );
    if (confirmed == true && mounted) {
      await widget.memo!.delete();
      if (!mounted) return;
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bgColor = _colorOptions
        .firstWhere((c) => c.toARGB32() == _selectedColorValue,
            orElse: () => _colorOptions[0]);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: bgColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
          onPressed: _save,
        ),
        actions: [
          if (_isEditing)
            IconButton(
              icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
              onPressed: _delete,
            ),
          IconButton(
            icon: const Icon(Icons.check, color: Color(0xFF6366f1)),
            onPressed: _save,
          ),
        ],
      ),
      body: Column(
        children: [
          // 색상 선택 바
          SizedBox(
            height: 48,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _colorOptions.length,
              itemBuilder: (_, i) {
                final color = _colorOptions[i];
                final selected = color.toARGB32() == _selectedColorValue;
                return GestureDetector(
                  onTap: () => setState(() => _selectedColorValue = color.toARGB32()),
                  child: Container(
                    width: 36,
                    height: 36,
                    margin: const EdgeInsets.only(right: 8, top: 6),
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: selected
                            ? const Color(0xFF6366f1)
                            : Colors.white.withAlpha(51),
                        width: selected ? 2.5 : 1,
                      ),
                    ),
                    child: selected
                        ? const Icon(Icons.check,
                            size: 16, color: Color(0xFF6366f1))
                        : Tooltip(message: _colorLabels[i], child: const SizedBox()),
                  ),
                );
              },
            ),
          ),
          // 제목
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 4),
            child: TextField(
              controller: _titleCtrl,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.w700),
              decoration: InputDecoration(
                hintText: '제목',
                hintStyle:
                    TextStyle(color: Colors.white.withAlpha(77), fontSize: 20),
                border: InputBorder.none,
              ),
              maxLines: 1,
              textInputAction: TextInputAction.next,
            ),
          ),
          const Divider(color: Colors.white12, height: 1),
          // 내용
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
              child: TextField(
                controller: _contentCtrl,
                style: TextStyle(
                    color: Colors.white.withAlpha(204),
                    fontSize: 15,
                    height: 1.7),
                decoration: InputDecoration(
                  hintText: '내용을 입력하세요...',
                  hintStyle:
                      TextStyle(color: Colors.white.withAlpha(77), fontSize: 15),
                  border: InputBorder.none,
                ),
                maxLines: null,
                expands: true,
                textAlignVertical: TextAlignVertical.top,
                autofocus: !_isEditing,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
