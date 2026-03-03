import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';

import '../models/memo.dart';
import 'memo_edit_screen.dart';

// 색상 팔레트 (colorValue ↔ Color)
const _colorOptions = [
  Color(0xFF1e293b), // 슬레이트 (기본)
  Color(0xFF1e3a5f), // 블루
  Color(0xFF1a3a2a), // 그린
  Color(0xFF3b1f2b), // 핑크
  Color(0xFF2d1f3b), // 퍼플
  Color(0xFF3b2a1a), // 앰버
];

Color _colorFromValue(int value) =>
    _colorOptions.firstWhere(
      (c) => c.toARGB32() == value,
      orElse: () => _colorOptions[0],
    );

String _formatDate(DateTime dt) {
  final now = DateTime.now();
  final diff = now.difference(dt);
  if (diff.inMinutes < 1) return '방금 전';
  if (diff.inHours < 1) return '${diff.inMinutes}분 전';
  if (diff.inDays < 1) return '${diff.inHours}시간 전';
  if (diff.inDays < 7) return '${diff.inDays}일 전';
  return '${dt.year}.${dt.month.toString().padLeft(2, '0')}.${dt.day.toString().padLeft(2, '0')}';
}

class MemoListScreen extends StatefulWidget {
  const MemoListScreen({super.key});

  @override
  State<MemoListScreen> createState() => _MemoListScreenState();
}

class _MemoListScreenState extends State<MemoListScreen> {
  final _searchController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openEdit({Memo? memo}) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => MemoEditScreen(memo: memo)),
    );
  }

  Future<void> _deleteMemo(Memo memo) async {
    await memo.delete();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0f1117),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0f1117),
        title: const Text(
          '메모장',
          style: TextStyle(
            color: Colors.white,
            fontSize: 22,
            fontWeight: FontWeight.w700,
          ),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(52),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: TextField(
              controller: _searchController,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: '메모 검색...',
                hintStyle: TextStyle(color: Colors.white.withAlpha(77)),
                prefixIcon: Icon(Icons.search, color: Colors.white.withAlpha(77)),
                suffixIcon: _query.isNotEmpty
                    ? IconButton(
                        icon: Icon(Icons.clear, color: Colors.white.withAlpha(77)),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _query = '');
                        },
                      )
                    : null,
                filled: true,
                fillColor: const Color(0xFF1a1d27),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(vertical: 0),
              ),
              onChanged: (v) => setState(() => _query = v.toLowerCase()),
            ),
          ),
        ),
      ),
      body: ValueListenableBuilder(
        valueListenable: Hive.box<Memo>('memos').listenable(),
        builder: (context, Box<Memo> box, _) {
          final all = box.values.toList()
            ..sort((a, b) => b.updatedAt.compareTo(a.updatedAt));

          final memos = _query.isEmpty
              ? all
              : all.where((m) =>
                  m.title.toLowerCase().contains(_query) ||
                  m.content.toLowerCase().contains(_query)).toList();

          if (memos.isEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.note_alt_outlined,
                      size: 64, color: Colors.white.withAlpha(51)),
                  const SizedBox(height: 16),
                  Text(
                    _query.isEmpty ? '메모가 없습니다' : '검색 결과가 없습니다',
                    style: TextStyle(
                        color: Colors.white.withAlpha(102), fontSize: 16),
                  ),
                ],
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: memos.length,
            itemBuilder: (context, index) {
              final memo = memos[index];
              return _MemoCard(
                memo: memo,
                onTap: () => _openEdit(memo: memo),
                onDelete: () => _deleteMemo(memo),
              );
            },
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _openEdit(),
        backgroundColor: const Color(0xFF6366f1),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }
}

class _MemoCard extends StatelessWidget {
  final Memo memo;
  final VoidCallback onTap;
  final VoidCallback onDelete;

  const _MemoCard({
    required this.memo,
    required this.onTap,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final cardColor = _colorFromValue(memo.colorValue);
    return Dismissible(
      key: Key(memo.key.toString()),
      direction: DismissDirection.endToStart,
      background: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.red.withAlpha(51),
          borderRadius: BorderRadius.circular(16),
        ),
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        child: const Icon(Icons.delete_outline, color: Colors.redAccent),
      ),
      onDismissed: (_) => onDelete(),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Colors.white.withAlpha(13),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (memo.title.isNotEmpty) ...[
                Text(
                  memo.title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 6),
              ],
              if (memo.content.isNotEmpty)
                Text(
                  memo.content,
                  style: TextStyle(
                    color: Colors.white.withAlpha(179),
                    fontSize: 13,
                    height: 1.5,
                  ),
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
              const SizedBox(height: 10),
              Text(
                _formatDate(memo.updatedAt),
                style: TextStyle(
                  color: Colors.white.withAlpha(77),
                  fontSize: 11,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
