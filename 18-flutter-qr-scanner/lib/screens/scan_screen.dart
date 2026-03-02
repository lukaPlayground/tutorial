import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final MobileScannerController _scannerController = MobileScannerController();
  String? _scannedValue;
  bool _isScanning = true;
  final List<String> _history = [];

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (!_isScanning) return;
    final barcode = capture.barcodes.firstOrNull;
    if (barcode == null || barcode.rawValue == null) return;

    final value = barcode.rawValue!;
    setState(() {
      _scannedValue = value;
      _isScanning = false;
      if (!_history.contains(value)) {
        _history.insert(0, value);
        if (_history.length > 10) _history.removeLast();
      }
    });
  }

  void _resume() {
    setState(() {
      _isScanning = true;
      _scannedValue = null;
    });
  }

  Future<void> _copyToClipboard(String text) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('클립보드에 복사됐다'), duration: Duration(seconds: 1)),
    );
  }

  bool _isUrl(String text) =>
      text.startsWith('http://') || text.startsWith('https://');

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0f1117),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 12),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'QR 스캔',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 26,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _isScanning ? '카메라를 QR 코드에 가져다 대면 자동으로 인식한다.' : '스캔 완료. 결과를 확인하거나 다시 스캔할 수 있다.',
                      style: const TextStyle(color: Color(0xFF9ca3af), fontSize: 13),
                    ),
                  ],
                ),
              ),
            ),

            // 카메라 뷰
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 24),
              height: 260,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: _isScanning ? const Color(0xFF6366f1) : const Color(0xFF10b981),
                  width: 2,
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: Stack(
                  children: [
                    MobileScanner(
                      controller: _scannerController,
                      onDetect: _onDetect,
                    ),
                    if (!_isScanning)
                      Container(
                        color: Colors.black.withAlpha(180),
                        child: const Center(
                          child: Icon(Icons.check_circle, color: Color(0xFF10b981), size: 64),
                        ),
                      ),
                    // 스캔 가이드 오버레이
                    if (_isScanning)
                      Center(
                        child: Container(
                          width: 160,
                          height: 160,
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.white.withAlpha(80), width: 1.5),
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // 결과 또는 스캔 중 표시
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_scannedValue != null) ...[
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1c2130),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF10b981).withAlpha(80)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.qr_code_scanner, color: Color(0xFF10b981), size: 16),
                                const SizedBox(width: 6),
                                const Text('스캔 결과', style: TextStyle(color: Color(0xFF10b981), fontSize: 12, fontWeight: FontWeight.w600)),
                                const Spacer(),
                                if (_isUrl(_scannedValue!))
                                  const Padding(
                                    padding: EdgeInsets.only(right: 8),
                                    child: Text('URL', style: TextStyle(color: Color(0xFF6366f1), fontSize: 11, fontWeight: FontWeight.w600)),
                                  ),
                                GestureDetector(
                                  onTap: () => _copyToClipboard(_scannedValue!),
                                  child: const Icon(Icons.copy, color: Color(0xFF9ca3af), size: 18),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text(
                              _scannedValue!,
                              style: const TextStyle(color: Colors.white, fontSize: 14),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: _resume,
                        icon: const Icon(Icons.qr_code_scanner, size: 18),
                        label: const Text('다시 스캔'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6366f1),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ],

                    // 히스토리
                    if (_history.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      const Text('최근 스캔 기록', style: TextStyle(color: Color(0xFF9ca3af), fontSize: 13, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 8),
                      ..._history.map((item) => Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1c2130),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFF2d3748)),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                item,
                                style: const TextStyle(color: Color(0xFFd1d5db), fontSize: 13),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            GestureDetector(
                              onTap: () => _copyToClipboard(item),
                              child: const Icon(Icons.copy, color: Color(0xFF4b5563), size: 16),
                            ),
                          ],
                        ),
                      )),
                    ],
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
