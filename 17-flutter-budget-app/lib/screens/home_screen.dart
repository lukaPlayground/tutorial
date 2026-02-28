import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../db/database_helper.dart';
import '../models/transaction.dart' as model;
import 'add_transaction_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<model.Transaction> _transactions = [];
  final _moneyFmt = NumberFormat('#,###', 'ko');

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final data = await DatabaseHelper.instance.getAll();
    setState(() => _transactions = data);
  }

  int get _totalIncome => _transactions
      .where((t) => t.type == 'income')
      .fold(0, (sum, t) => sum + t.amount);

  int get _totalExpense => _transactions
      .where((t) => t.type == 'expense')
      .fold(0, (sum, t) => sum + t.amount);

  int get _balance => _totalIncome - _totalExpense;

  Future<void> _delete(model.Transaction tx) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1a1d2e),
        title: const Text('삭제', style: TextStyle(color: Colors.white, fontSize: 16)),
        content: Text('${tx.category} ${_moneyFmt.format(tx.amount)}원을 삭제할까요?',
            style: const TextStyle(color: Colors.white60, fontSize: 13)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('취소', style: TextStyle(color: Colors.white38))),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('삭제', style: TextStyle(color: Color(0xFFf87171)))),
        ],
      ),
    );
    if (confirmed == true) {
      await DatabaseHelper.instance.delete(tx.id!);
      _load();
    }
  }

  // 날짜별 그룹핑
  Map<String, List<model.Transaction>> get _grouped {
    final map = <String, List<model.Transaction>>{};
    for (final tx in _transactions) {
      map.putIfAbsent(tx.date, () => []).add(tx);
    }
    return map;
  }

  @override
  Widget build(BuildContext context) {
    final grouped = _grouped;
    final dates = grouped.keys.toList()..sort((a, b) => b.compareTo(a));

    return Scaffold(
      backgroundColor: const Color(0xFF0a0e17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0d1120),
        title: const Text('가계부', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Colors.white)),
        elevation: 0,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: Colors.white.withAlpha(15), height: 1),
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          final added = await Navigator.push<bool>(
            context,
            MaterialPageRoute(builder: (_) => const AddTransactionScreen()),
          );
          if (added == true) _load();
        },
        backgroundColor: const Color(0xFF6366f1),
        foregroundColor: Colors.white,
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          // 요약 카드
          _SummaryCard(balance: _balance, income: _totalIncome, expense: _totalExpense, fmt: _moneyFmt),

          // 거래 내역
          Expanded(
            child: _transactions.isEmpty
                ? const Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.receipt_long_outlined, size: 48, color: Colors.white12),
                        SizedBox(height: 12),
                        Text('거래 내역이 없다\n+ 버튼으로 추가해보자', textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.white24, fontSize: 14, height: 1.6)),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
                    itemCount: dates.length,
                    itemBuilder: (_, i) {
                      final date = dates[i];
                      final items = grouped[date]!;
                      final parsed = DateTime.parse(date);
                      final dayTotal = items.fold<int>(0, (sum, t) =>
                          t.type == 'income' ? sum + t.amount : sum - t.amount);

                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // 날짜 헤더
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Row(
                              children: [
                                Text(
                                  DateFormat('M월 d일 (E)', 'ko').format(parsed),
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white38),
                                ),
                                const Spacer(),
                                Text(
                                  '${dayTotal >= 0 ? '+' : '-'}${_moneyFmt.format(dayTotal.abs())}원',
                                  style: TextStyle(
                                    fontSize: 12, fontWeight: FontWeight.w700,
                                    color: dayTotal >= 0 ? const Color(0xFF10b981) : const Color(0xFFf87171),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          // 아이템
                          ...items.map((tx) => _TransactionTile(tx: tx, fmt: _moneyFmt, onDelete: () => _delete(tx))),
                        ],
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final int balance, income, expense;
  final NumberFormat fmt;
  const _SummaryCard({required this.balance, required this.income, required this.expense, required this.fmt});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1a1d2e), Color(0xFF0d1120)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withAlpha(15)),
      ),
      child: Column(
        children: [
          const Text('잔액', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.white38, letterSpacing: 0.5)),
          const SizedBox(height: 6),
          Text(
            '${balance < 0 ? '-' : ''}${fmt.format(balance.abs())}원',
            style: TextStyle(
              fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -1,
              color: balance >= 0 ? Colors.white : const Color(0xFFf87171),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _SumItem(label: '수입', amount: income, fmt: fmt, color: const Color(0xFF10b981)),
              Container(width: 1, height: 32, color: Colors.white.withAlpha(15)),
              _SumItem(label: '지출', amount: expense, fmt: fmt, color: const Color(0xFFf87171)),
            ],
          ),
        ],
      ),
    );
  }
}

class _SumItem extends StatelessWidget {
  final String label;
  final int amount;
  final NumberFormat fmt;
  final Color color;
  const _SumItem({required this.label, required this.amount, required this.fmt, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(width: 6, height: 6, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
              const SizedBox(width: 5),
              Text(label, style: const TextStyle(fontSize: 11, color: Colors.white38, fontWeight: FontWeight.w600)),
            ],
          ),
          const SizedBox(height: 4),
          Text('${fmt.format(amount)}원',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: color)),
        ],
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  final model.Transaction tx;
  final NumberFormat fmt;
  final VoidCallback onDelete;
  const _TransactionTile({required this.tx, required this.fmt, required this.onDelete});

  @override
  Widget build(BuildContext context) {
    final isIncome = tx.type == 'income';
    final color = isIncome ? const Color(0xFF10b981) : const Color(0xFFf87171);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
      decoration: BoxDecoration(
        color: const Color(0xFF0d1120),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withAlpha(12)),
      ),
      child: Row(
        children: [
          // 카테고리 아이콘
          Container(
            width: 38, height: 38,
            decoration: BoxDecoration(color: color.withAlpha(30), shape: BoxShape.circle),
            child: Center(
              child: Text(_categoryEmoji(tx.category), style: const TextStyle(fontSize: 17)),
            ),
          ),
          const SizedBox(width: 12),
          // 카테고리 + 메모
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tx.category, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Colors.white)),
                if (tx.memo.isNotEmpty)
                  Text(tx.memo, style: const TextStyle(fontSize: 12, color: Colors.white38), maxLines: 1, overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          // 금액
          Text(
            '${isIncome ? '+' : '-'}${fmt.format(tx.amount)}원',
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: color),
          ),
          const SizedBox(width: 8),
          // 삭제
          GestureDetector(
            onTap: onDelete,
            child: const Icon(Icons.close, size: 16, color: Colors.white24),
          ),
        ],
      ),
    );
  }

  String _categoryEmoji(String category) {
    const map = {
      '월급': '💰', '부업': '💼', '용돈': '🎁', '기타 수입': '📈',
      '식비': '🍚', '교통': '🚌', '쇼핑': '🛍', '문화': '🎬',
      '의료': '💊', '주거': '🏠', '기타 지출': '📦',
    };
    return map[category] ?? '💳';
  }
}
