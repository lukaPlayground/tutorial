import 'package:flutter/material.dart';
import 'services/notification_service.dart';
import 'timer_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await NotificationService.init();
  runApp(const TimerApp());
}

class TimerApp extends StatelessWidget {
  const TimerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: '타이머',
      theme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: const Color(0xFF0f1117),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF6366f1),
          surface: Color(0xFF1c2130),
        ),
      ),
      home: const TimerScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}
