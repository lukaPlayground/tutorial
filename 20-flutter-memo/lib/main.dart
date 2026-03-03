import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'models/memo.dart';
import 'screens/memo_list_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Hive.initFlutter();
  Hive.registerAdapter(MemoAdapter());
  await Hive.openBox<Memo>('memos');
  runApp(const MemoApp());
}

class MemoApp extends StatelessWidget {
  const MemoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: '메모장',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6366f1),
          brightness: Brightness.dark,
        ),
        useMaterial3: true,
      ),
      home: const MemoListScreen(),
    );
  }
}
