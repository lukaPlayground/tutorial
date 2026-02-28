import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../db/database_helper.dart';
import '../models/transaction.dart' as model;

const _incomeCategories = ['월급', '부업', '용돈', '기타 수입'];
const _expenseCategories = ['식비', '교통', '쇼핑', '문화', '의료', '주거', '기타 지출'];

class AddTransactionScreen extends StatefulWidget {
  const AddTransactionScreen({super.key});

  @override
  State<AddTransactionScreen> createState() => _AddTransactionScreenState();
}

class _AddTransactionScreenState extends State<AddTransactionScreen> {
  String _type = 'expense';
  String _category = _expenseCategories.first;
  final _amountCtrl = TextEditingController();
  final _memoCtrl = TextEditingController();
  DateTime _date = DateTime.now();

  List<String> get _categories =>
      _type == 'income' ? _incomeCategories : _expenseCategories;

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.dark(
            primary: Color(0xFF6366f1),
            surface: Color(0xFF1a1d2e),
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _save() async {
    final amountStr = _amountCtrl.text.trim();
    if (amountStr.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('금액을 입력하세요')),
      );
      return;
    }
    final amount = int.tryParse(amountStr.replaceAll(',', ''));
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('올바른 금액을 입력하세요')),
      );
      return;
    }

    final tx = model.Transaction(
      type: _type,
      category: _category,
      amount: amount,
      memo: _memoCtrl.text.trim(),
      date: DateFormat('yyyy-MM-dd').format(_date),
    );

    await DatabaseHelper.instance.insert(tx);
    if (mounted) Navigator.pop(context, true);
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _memoCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isIncome = _type == 'income';
    final accentColor = isIncome ? const Color(0xFF10b981) : const Color(0xFFf87171);

    return Scaffold(
      backgroundColor: const Color(0xFF0a0e17),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0d1120),
        foregroundColor: Colors.white70,
        title: const Text('거래 추가', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
        elevation: 0,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: Colors.white.withAlpha(15), height: 1),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // 수입 / 지출 토글
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFF0d1120),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.white.withAlpha(20)),
            ),
            padding: const EdgeInsets.all(4),
            child: Row(
              children: [
                _TypeButton(label: '지출', value: 'expense', current: _type, color: const Color(0xFFf87171), onTap: () {
                  setState(() { _type = 'expense'; _category = _expenseCategories.first; });
                }),
                _TypeButton(label: '수입', value: 'income', current: _type, color: const Color(0xFF10b981), onTap: () {
                  setState(() { _type = 'income'; _category = _incomeCategories.first; });
                }),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // 금액
          _Label('금액'),
          const SizedBox(height: 8),
          TextField(
            controller: _amountCtrl,
            keyboardType: TextInputType.number,
            style: TextStyle(color: accentColor, fontSize: 22, fontWeight: FontWeight.w800),
            decoration: _inputDecoration('0').copyWith(
              prefixText: '₩  ',
              prefixStyle: TextStyle(color: accentColor, fontSize: 18, fontWeight: FontWeight.w700),
            ),
          ),
          const SizedBox(height: 20),

          // 카테고리
          _Label('카테고리'),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _categories.map((cat) {
              final selected = _category == cat;
              return GestureDetector(
                onTap: () => setState(() => _category = cat),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: selected ? accentColor.withAlpha(40) : const Color(0xFF0d1120),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: selected ? accentColor.withAlpha(160) : Colors.white.withAlpha(20),
                    ),
                  ),
                  child: Text(cat, style: TextStyle(
                    fontSize: 13, fontWeight: FontWeight.w600,
                    color: selected ? accentColor : Colors.white54,
                  )),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),

          // 날짜
          _Label('날짜'),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: _pickDate,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: const Color(0xFF0d1120),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.white.withAlpha(20)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today_outlined, size: 16, color: Colors.white38),
                  const SizedBox(width: 10),
                  Text(
                    DateFormat('yyyy년 M월 d일 (E)', 'ko').format(_date),
                    style: const TextStyle(fontSize: 14, color: Colors.white70),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // 메모
          _Label('메모 (선택)'),
          const SizedBox(height: 8),
          TextField(
            controller: _memoCtrl,
            style: const TextStyle(color: Colors.white70, fontSize: 14),
            decoration: _inputDecoration('간단한 메모'),
            maxLines: 2,
          ),
          const SizedBox(height: 32),

          // 저장 버튼
          SizedBox(
            height: 52,
            child: ElevatedButton(
              onPressed: _save,
              style: ElevatedButton.styleFrom(
                backgroundColor: isIncome ? const Color(0xFF10b981) : const Color(0xFF6366f1),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
              child: const Text('저장', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
            ),
          ),
        ],
      ),
    );
  }
}

class _TypeButton extends StatelessWidget {
  final String label, value, current;
  final Color color;
  final VoidCallback onTap;
  const _TypeButton({required this.label, required this.value, required this.current, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final selected = value == current;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 11),
          decoration: BoxDecoration(
            color: selected ? color.withAlpha(40) : Colors.transparent,
            borderRadius: BorderRadius.circular(9),
            border: selected ? Border.all(color: color.withAlpha(120)) : null,
          ),
          child: Center(
            child: Text(label, style: TextStyle(
              fontSize: 14, fontWeight: FontWeight.w700,
              color: selected ? color : Colors.white38,
            )),
          ),
        ),
      ),
    );
  }
}

Widget _Label(String text) => Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white38, letterSpacing: 0.5));

InputDecoration _inputDecoration(String hint) => InputDecoration(
  hintText: hint,
  hintStyle: const TextStyle(color: Colors.white24, fontSize: 14),
  filled: true,
  fillColor: const Color(0xFF0d1120),
  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.white.withAlpha(20))),
  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.white.withAlpha(20))),
  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFF6366f1))),
);
