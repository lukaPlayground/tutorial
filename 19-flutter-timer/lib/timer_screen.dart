import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/notification_service.dart';

// 프리셋 정의
class TimerPreset {
  final String label;
  final int seconds;
  const TimerPreset(this.label, this.seconds);
}

const _presets = [
  TimerPreset('5분', 5 * 60),
  TimerPreset('10분', 10 * 60),
  TimerPreset('25분', 25 * 60),   // 뽀모도로
  TimerPreset('30분', 30 * 60),
  TimerPreset('1시간', 60 * 60),
];

enum TimerState { idle, running, paused, done }

class TimerScreen extends StatefulWidget {
  const TimerScreen({super.key});

  @override
  State<TimerScreen> createState() => _TimerScreenState();
}

class _TimerScreenState extends State<TimerScreen>
    with SingleTickerProviderStateMixin {
  int _totalSeconds = 25 * 60;   // 기본값: 25분
  int _remainingSeconds = 25 * 60;
  TimerState _state = TimerState.idle;
  Timer? _timer;
  int _selectedPresetIndex = 2;  // 25분 기본 선택
  bool _notificationGranted = false;

  late AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 1),
    )..repeat(reverse: true);
    _requestNotificationPermission();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  Future<void> _requestNotificationPermission() async {
    final granted = await NotificationService.requestPermission();
    if (mounted) setState(() => _notificationGranted = granted);
  }

  void _selectPreset(int index) {
    if (_state == TimerState.running) return;
    setState(() {
      _selectedPresetIndex = index;
      _totalSeconds = _presets[index].seconds;
      _remainingSeconds = _presets[index].seconds;
      _state = TimerState.idle;
    });
    _timer?.cancel();
  }

  void _start() {
    setState(() => _state = TimerState.running);
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_remainingSeconds <= 0) {
        _onTimerDone();
        return;
      }
      setState(() => _remainingSeconds--);
    });
  }

  void _pause() {
    _timer?.cancel();
    setState(() => _state = TimerState.paused);
  }

  void _reset() {
    _timer?.cancel();
    setState(() {
      _remainingSeconds = _totalSeconds;
      _state = TimerState.idle;
    });
  }

  void _onTimerDone() {
    _timer?.cancel();
    setState(() {
      _remainingSeconds = 0;
      _state = TimerState.done;
    });
    HapticFeedback.heavyImpact();
    SystemSound.play(SystemSoundType.alert);
    NotificationService.showTimerDone(_presets[_selectedPresetIndex].label);
  }

  String get _timeString {
    final m = _remainingSeconds ~/ 60;
    final s = _remainingSeconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  double get _progress =>
      _totalSeconds == 0 ? 0 : _remainingSeconds / _totalSeconds;

  Color get _ringColor {
    if (_state == TimerState.done) return const Color(0xFF10b981);
    if (_progress > 0.5) return const Color(0xFF6366f1);
    if (_progress > 0.2) return const Color(0xFFf59e0b);
    return const Color(0xFFef4444);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0f1117),
      body: SafeArea(
        child: Column(
          children: [
            // 헤더
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
              child: Row(
                children: [
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '타이머',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 26,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        '집중하고 싶을 때 켜두는 타이머',
                        style: TextStyle(color: Color(0xFF9ca3af), fontSize: 13),
                      ),
                    ],
                  ),
                  const Spacer(),
                  if (!_notificationGranted)
                    GestureDetector(
                      onTap: _requestNotificationPermission,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFf59e0b).withAlpha(20),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFf59e0b).withAlpha(80)),
                        ),
                        child: const Text('🔔 알림 허용', style: TextStyle(color: Color(0xFFf59e0b), fontSize: 11)),
                      ),
                    ),
                ],
              ),
            ),

            // 프리셋 칩
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
              child: Row(
                children: List.generate(_presets.length, (i) {
                  final selected = i == _selectedPresetIndex;
                  return Expanded(
                    child: GestureDetector(
                      onTap: () => _selectPreset(i),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 150),
                        margin: const EdgeInsets.symmetric(horizontal: 3),
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: selected
                              ? const Color(0xFF6366f1)
                              : const Color(0xFF1c2130),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: selected
                                ? const Color(0xFF6366f1)
                                : const Color(0xFF2d3748),
                          ),
                        ),
                        child: Text(
                          _presets[i].label,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: selected ? Colors.white : const Color(0xFF9ca3af),
                            fontSize: 12,
                            fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ),

            // 원형 타이머
            Expanded(
              child: Center(
                child: SizedBox(
                  width: 260,
                  height: 260,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      // 배경 링
                      CustomPaint(
                        size: const Size(260, 260),
                        painter: _RingPainter(
                          progress: 1.0,
                          color: const Color(0xFF1c2130),
                          strokeWidth: 16,
                        ),
                      ),
                      // 진행 링
                      AnimatedBuilder(
                        animation: _pulseController,
                        builder: (context, _) => CustomPaint(
                          size: const Size(260, 260),
                          painter: _RingPainter(
                            progress: _progress,
                            color: _ringColor,
                            strokeWidth: 16,
                            glow: _state == TimerState.running,
                            glowOpacity: _pulseController.value * 0.3,
                          ),
                        ),
                      ),
                      // 중앙 텍스트
                      Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (_state == TimerState.done)
                            const Text('완료! 🎉', style: TextStyle(fontSize: 20, color: Color(0xFF10b981), fontWeight: FontWeight.bold))
                          else ...[
                            Text(
                              _timeString,
                              style: TextStyle(
                                fontSize: 52,
                                fontWeight: FontWeight.w200,
                                color: _state == TimerState.paused
                                    ? const Color(0xFF9ca3af)
                                    : Colors.white,
                                letterSpacing: 2,
                                fontFeatures: const [FontFeature.tabularFigures()],
                              ),
                            ),
                            Text(
                              _presets[_selectedPresetIndex].label,
                              style: const TextStyle(color: Color(0xFF4b5563), fontSize: 13),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),

            // 컨트롤 버튼
            Padding(
              padding: const EdgeInsets.fromLTRB(32, 0, 32, 40),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // 리셋
                  _CircleButton(
                    onTap: _reset,
                    icon: Icons.replay,
                    color: const Color(0xFF1c2130),
                    iconColor: const Color(0xFF9ca3af),
                    size: 52,
                  ),
                  const SizedBox(width: 24),
                  // 시작/일시정지
                  _CircleButton(
                    onTap: _state == TimerState.running ? _pause : _start,
                    icon: _state == TimerState.running
                        ? Icons.pause
                        : Icons.play_arrow,
                    color: _state == TimerState.done
                        ? const Color(0xFF10b981)
                        : const Color(0xFF6366f1),
                    iconColor: Colors.white,
                    size: 72,
                  ),
                  const SizedBox(width: 24),
                  // 플레이스홀더 (레이아웃 균형)
                  const SizedBox(width: 52),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// 원형 진행 링 painter
class _RingPainter extends CustomPainter {
  final double progress;
  final Color color;
  final double strokeWidth;
  final bool glow;
  final double glowOpacity;

  const _RingPainter({
    required this.progress,
    required this.color,
    required this.strokeWidth,
    this.glow = false,
    this.glowOpacity = 0,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - strokeWidth) / 2;

    final paint = Paint()
      ..color = color
      ..strokeWidth = strokeWidth
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    if (glow && glowOpacity > 0) {
      final glowPaint = Paint()
        ..color = color.withAlpha((glowOpacity * 255).toInt())
        ..strokeWidth = strokeWidth + 8
        ..style = PaintingStyle.stroke
        ..strokeCap = StrokeCap.round
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 8);
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        -math.pi / 2,
        -2 * math.pi * progress,
        false,
        glowPaint,
      );
    }

    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      -math.pi / 2,
      -2 * math.pi * progress,
      false,
      paint,
    );
  }

  @override
  bool shouldRepaint(_RingPainter old) =>
      old.progress != progress ||
      old.color != color ||
      old.glowOpacity != glowOpacity;
}

// 원형 버튼
class _CircleButton extends StatelessWidget {
  final VoidCallback onTap;
  final IconData icon;
  final Color color;
  final Color iconColor;
  final double size;

  const _CircleButton({
    required this.onTap,
    required this.icon,
    required this.color,
    required this.iconColor,
    required this.size,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: color,
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(
              color: color.withAlpha(80),
              blurRadius: 16,
              spreadRadius: 2,
            ),
          ],
        ),
        child: Icon(icon, color: iconColor, size: size * 0.42),
      ),
    );
  }
}
