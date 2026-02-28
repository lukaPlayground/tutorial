import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/transaction.dart' as model;

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('budget.db');
    return _database!;
  }

  Future<Database> _initDB(String fileName) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, fileName);

    return await openDatabase(
      path,
      version: 1,
      onCreate: _createDB,
    );
  }

  Future<void> _createDB(Database db, int version) async {
    await db.execute('''
      CREATE TABLE transactions (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        type     TEXT NOT NULL,
        category TEXT NOT NULL,
        amount   INTEGER NOT NULL,
        memo     TEXT NOT NULL,
        date     TEXT NOT NULL
      )
    ''');
  }

  Future<int> insert(model.Transaction tx) async {
    final db = await database;
    return await db.insert('transactions', tx.toMap());
  }

  Future<List<model.Transaction>> getAll() async {
    final db = await database;
    final rows = await db.query('transactions', orderBy: 'date DESC, id DESC');
    return rows.map(model.Transaction.fromMap).toList();
  }

  Future<int> delete(int id) async {
    final db = await database;
    return await db.delete('transactions', where: 'id = ?', whereArgs: [id]);
  }
}
