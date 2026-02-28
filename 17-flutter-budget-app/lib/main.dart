import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'screens/home_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('ko');
  runApp(const BudgetApp());
}

class BudgetApp extends StatelessWidget {
  const BudgetApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: '가계부',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        scaffoldBackgroundColor: const Color(0xFF0a0e17),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF6366f1),
          surface: Color(0xFF0d1120),
        ),
        fontFamily: '.SF Pro Text',
        snackBarTheme: const SnackBarThemeData(
          backgroundColor: Color(0xFF1a1d2e),
          contentTextStyle: TextStyle(color: Colors.white70),
        ),
      ),
      home: const HomeScreen(),
    );
  }
}
