class Transaction {
  final int? id;
  final String type; // 'income' | 'expense'
  final String category;
  final int amount;
  final String memo;
  final String date; // ISO 8601 (yyyy-MM-dd)

  Transaction({
    this.id,
    required this.type,
    required this.category,
    required this.amount,
    required this.memo,
    required this.date,
  });

  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'type': type,
      'category': category,
      'amount': amount,
      'memo': memo,
      'date': date,
    };
  }

  factory Transaction.fromMap(Map<String, dynamic> map) {
    return Transaction(
      id: map['id'] as int,
      type: map['type'] as String,
      category: map['category'] as String,
      amount: map['amount'] as int,
      memo: map['memo'] as String,
      date: map['date'] as String,
    );
  }
}
