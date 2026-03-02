import 'package:flutter/material.dart';
import 'screens/generate_screen.dart';
import 'screens/scan_screen.dart';

void main() {
  runApp(const QrApp());
}

class QrApp extends StatelessWidget {
  const QrApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'QR 생성/스캔',
      theme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: const Color(0xFF0f1117),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF6366f1),
          surface: Color(0xFF1c2130),
        ),
      ),
      home: const MainScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;

  final _screens = const [
    GenerateScreen(),
    ScanScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        backgroundColor: const Color(0xFF1c2130),
        selectedItemColor: const Color(0xFF6366f1),
        unselectedItemColor: const Color(0xFF4b5563),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.qr_code),
            label: 'QR 생성',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.qr_code_scanner),
            label: 'QR 스캔',
          ),
        ],
      ),
    );
  }
}
